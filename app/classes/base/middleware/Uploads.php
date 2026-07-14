<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\files\FileStorage;
use vakata\image\Image;
use vakata\image\ImageException;
use Laminas\Diactoros\Stream;
use vakata\files\File;
use vakata\files\FileStorageInterface;
use base\components\files\Files;
use vakata\files\FileNotFoundException;

class Uploads
{
    protected Files $files;
    protected string $path;
    protected string $temp;
    protected string $server;
    protected string $sendfile;
    protected int $maximagesize;
    protected FileStorageInterface $tempfiles;

    public function __construct(
        Files $files,
        string $path = 'upload',
        ?string $temp = null,
        string $sendfile = '',
        int $maximagesize = 0
    ) {
        $this->files = $files;
        $this->path = $path;
        $this->temp = rtrim($temp ?? sys_get_temp_dir(), '/\\');
        $this->sendfile = rtrim($sendfile, '/\\');
        $this->maximagesize = $maximagesize;

        $this->server = '';
        if ($this->sendfile) {
            $temp = explode(':', $this->sendfile, 2);
            $this->server = $temp[0];
            $this->sendfile = $temp[1] ?? '';
            if (!in_array($this->server, ['apache','nginx','caddy'])) {
                $this->sendfile = '';
            }
        }

        if (!is_dir($this->temp . '/uploads/')) {
            mkdir($this->temp . '/uploads/', 0777);
        }
        $this->tempfiles = new FileStorage($this->temp, 'uploads', $this->temp);

        if ($this->sendfile) {
            $this->temp = $this->sendfile;
        }
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $url   = $req->getUrl();

        $slug = $this->path;
        if ($url->getSegment(0) === $slug) {
            if (($req->getMethod() === 'GET' || $req->getMethod() === 'HEAD') && (int)$url->getSegment(1)) {
                @$req->getAttribute('session')?->close();
                try {
                    $file = $this->files->fromLink($url->getRealPath(true), $req->getQuery());
                } catch (\RuntimeException) {
                    throw new \RuntimeException('File not found', 404);
                }
                if ($req->getQuery('info')) {
                    return new Response(
                        200,
                        json_encode([
                            'id'       => $file->id(),
                            'name'     => $file->name(),
                            'hash'     => $file->hash(),
                            'size'     => $file->size(),
                            'uploaded' => $file->uploaded(),
                            'settings' => $file->settings(),
                            'url'      => $this->files->toLink($file),
                            'thumb'    => $this->files->toLink($file, [ 'w' => 128, 'h' => 128 ])
                        ]) ?: throw new \RuntimeException(),
                        [ 'Content-Type' => 'application/json' ]
                    );
                }
                $sf = $file->id(); // sendfile id
                $nv = false; // is this a newly generated / refreshed version
                if (($req->getQueryParams()['w'] ?? 0) || ($req->getQueryParams()['h'] ?? 0)) {
                    $version = $req->getQuery('w', '0', 'int') . 'x' . $req->getQuery('h', '0', 'int');
                    try {
                        $file = $this->files->get($file->id(), $version);
                    } catch (FileNotFoundException) {
                        try {
                            $nv = true;
                            $replace = Image::fromPath($file->path() ?? throw new \RuntimeException())
                                ->thumbnail(
                                    min(4096, (int)($req->getQueryParams()['w'] ?? 0)),
                                    min(4096, (int)($req->getQueryParams()['h'] ?? 0)),
                                    isset($file->settings()['thumbnail']) ?
                                        $file->settings()['thumbnail'] : []
                                )
                                ->toString();
                            $file = $this->files->version($file->id(), $version, $replace);
                        } catch (ImageException) {
                            throw new FileNotFoundException();
                        }
                    }
                    $sf .= '.' . $file->id();
                }
                $sf .= '.' . $file->hash();
                $name = $file->name();
                $extension = substr($name, (int)strrpos($name, '.') + 1);
                $disposition = in_array(
                    strtolower($extension),
                    ['txt','png','jpg','gif','jpeg','html','htm','mp3','mp4','svg']
                ) ? 'inline' : 'attachment';
                $res = (new Response(200, null, [
                    'Last-Modified' => gmdate(
                        'D, d M Y H:i:s',
                        $file->uploaded()
                    ) . ' GMT',
                    'ETag' => $file->hash(),
                    // counter PHP session cache limiter
                    'Cache-Control' => 'private',
                    'Content-Disposition' => $disposition . '; ' .
                            'filename="' . preg_replace('([^a-z0-9.-]+)i', '_', $name) . '"; ' .
                            'filename*=UTF-8\'\'' . rawurlencode($name) . '; ' .
                            'size=' . (string)$file->size()
                ]));
                $res->setContentTypeByExtension($extension);

                // hand off to web server is configured
                if ($this->sendfile) {
                    if ($nv && is_file($this->sendfile . '/' . $sf)) {
                        @unlink($this->sendfile . '/' . $sf);
                    }
                    if (!is_file($this->sendfile . '/' . $sf)) {
                        $sl = false;
                        if ($file->isLocal()) {
                            $sl = symlink((string)$file->path(), $this->sendfile . '/' . $sf);
                        }
                        if (!$sl) {
                            copy($file->path() ?: throw new \RuntimeException(), $this->sendfile . '/' . $sf);
                        }
                    }
                    if ($this->server === 'apache') {
                        return $res
                            ->withHeader('X-Sendfile', $this->sendfile . '/' . $sf);
                    } elseif ($this->server === 'caddy') {
                        return $res
                            ->withHeader('X-Accel-Redirect', $sf);
                    } else {
                        return $res
                            ->withHeader('X-Accel-Redirect', '/' . basename($this->sendfile) . '/' . $sf);
                    }
                }

                // sendfile not enabled - server using PHP
                $res
                    ->withHeader('Accept-Ranges', 'bytes')
                    ->withHeader('Content-Length', (string)$file->size());
                $range = $req->getHeaderLine('range');
                if (!empty($range)) {
                    try {
                        if (!preg_match('@^bytes=\d*-\d*(,\d*-\d*)*$@', $range)) {
                            throw new \Exception('Invalid range');
                        }
                        $range = current(explode(',', substr($range, 6)));
                        list($seekBeg, $seekEnd) = explode('-', $range, 2);
                        $seekBeg = max((int)$seekBeg, 0);
                        $seekEnd = !(int)$seekEnd ? ($file->size() - 1) : min((int)$seekEnd, ($file->size() - 1));
                        if ($seekBeg > $seekEnd) {
                            throw new \Exception('Invalid range');
                        }
                        $res
                            ->withHeader(
                                'Content-Range',
                                'bytes ' . $seekBeg . '-' . $seekEnd . '/' . $file->size()
                            )
                            ->withHeader('Content-Length', (string)($seekEnd - $seekBeg + 1))
                            ->withStatus(206);
                    } catch (\Exception $e) {
                        return (new Response(416))
                            ->withHeader('Content-Range', 'bytes */' . $file->size());
                    }
                }
                return $res->withBody(new Stream($file->content()));
            }
            if ($req->getMethod() === 'POST') {
                $post = (array)($req->getParsedBody() ?? []);
                $res = (new Response());
                if ((isset($post['thumbnail']) || isset($post['crop'])) && isset($post['id']) && $post['id']) {
                    $file = $this->files->get($post['id']);

                    // save crop
                    if (
                        isset($post['crop']) &&
                        ($post['crop'] = json_decode($post['crop'], true)) &&
                        is_array($post['crop']) &&
                        count($post['crop'])
                    ) {
                        $img = \vakata\image\Image::fromPath((string)$file->path());
                        foreach ($post['crop'] as $c) {
                            $w = $img->width();
                            $h = $img->height();
                            $nw = (int)($c['options']['width'] * $w);
                            $nh = (int)($c['options']['height'] * $h);
                            $x = (int)($c['options']['left'] * $w);
                            $y = (int)($c['options']['top'] * $h);
                            $img->crop($nw, $nh, $x, $y);
                            $img = new \vakata\image\Image($img->toString());
                        }
                        $img = (string)$img;
                        $file->setUploaded(time());
                        $handle = fopen('php://temp', 'r+');
                        if (!$handle) {
                            throw new \Exception('Could not store crop');
                        }
                        fwrite($handle, $img);
                        rewind($handle);
                        $temp = fstat($handle);
                        if ($temp && isset($temp['size']) && (int)$temp['size'] > 0) {
                            $file->setSize($temp['size']);
                        }
                        $file->setHash(md5($img));
                        $file = $this->files->storage()->set($file, $handle);
                    }

                    // save thumbnail
                    if (
                        isset($post['thumbnail']) &&
                        ($post['thumbnail'] = json_decode($post['thumbnail'], true)) &&
                        is_array($post['thumbnail'])
                    ) {
                        $settings = $file->settings();
                        $settings['thumbnail'] = $post['thumbnail'];
                        $file->setSettings($settings);
                        $file = $this->files->storage()->set($file);
                    }
                } elseif (isset($post['settings']) && isset($post['id']) && $post['id']) {
                    $file = $this->files->get($post['id']);
                    $file->setSettings(json_decode($post['settings'], true));
                    $file = $this->files->storage()->set($file);
                } else {
                    if ($req->getPost('chunk', 0, 'int') > 0) {
                        // chunk requests are not logged and as such - do not count towards the rate limit
                        $res->withHeader('X-Log', 'no');
                    }
                    if ($req->getPost('temp')) {
                        $file = $this->tempfiles->fromPSRRequest($req, 'file');
                        return $res
                            ->setBody(json_encode([
                                'id'       => $file->id(),
                                'name'     => $file->name(),
                                'hash'     => $file->hash(),
                                'size'     => $file->size(),
                                'uploaded' => $file->uploaded(),
                                'settings' => $file->settings(),
                                'url'      => '',
                                'thumb'    => ''
                            ]) ?: throw new \RuntimeException())
                            ->withHeader('Content-Type', 'application/json');
                    }
                    $file = $this->files->storage()->fromPSRRequest($req, 'file');
                    if (
                        $file->isComplete() &&
                        ($pth = $file->path()) &&
                        in_array($file->ext(), [ 'jpg', 'jpeg', 'bitmap', 'bmp', 'png', 'webp' ]) // do not process gif
                    ) {
                        try {
                            $img = Image::fromPath($pth);
                            if ($this->maximagesize) {
                                $img->resizeLongEdge($this->maximagesize, false);
                            }
                            $temp = tempnam($this->temp, 'resize_');
                            if ($temp === false) {
                                throw new \RuntimeException();
                            }
                            file_put_contents(
                                $temp,
                                $img->toString()
                            );
                            $file = $this->files->storage()->set(new File(
                                $file->id(),
                                $file->name(),
                                md5_file($temp) ?: '',
                                $file->uploaded(),
                                filesize($temp) ?: 0,
                                $file->settings(),
                                true,
                                $file->path()
                            ), fopen($temp, 'r'));
                            @unlink($temp);
                        } catch (\Exception) {
                        }
                    }
                }
                return $res
                    ->setBody(json_encode([
                        'id'       => $file->id(),
                        'name'     => $file->name(),
                        'hash'     => $file->hash(),
                        'size'     => $file->size(),
                        'uploaded' => $file->uploaded(),
                        'settings' => $file->settings(),
                        'url'      => $this->files->toLink($file),
                        'thumb'    => $this->files->toLink($file, [ 'w' => 128, 'h' => 128 ])
                    ]) ?: throw new \RuntimeException())
                    ->withHeader('Content-Type', 'application/json');
            }
        }
        return $next($req);
    }
}
