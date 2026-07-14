<?php

declare(strict_types=1);

namespace base\middleware;

use Laminas\Diactoros\Stream;
use vakata\http\Request;
use vakata\http\Response;

class Gzip
{
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $res = $next($req);
        $acc = $req->getHeaderLine('Accept-Encoding');
        $avf = stream_get_filters();
        if (
            !ini_get('zlib.output_compression') &&
            !$res->hasHeader("Content-Encoding") &&
            (in_array('zlib.*', $avf) || in_array('zlib.deflate', $avf)) &&
            (strpos($acc, '*') !== false || strpos($acc, 'gzip') !== false)
        ) {
            $stream = $res->getBody()->detach();
            if ($stream) {
                stream_filter_append(
                    $stream,
                    'zlib.deflate',
                    STREAM_FILTER_READ,
                    [ 'level' => 6, 'window' => 30, 'memory' => 6 ]
                );
                $res = $res->withBody(new Stream($stream));
                return $res
                    ->withoutHeader('Content-Length')
                    ->withHeader('Content-Encoding', 'gzip');
            }
        }
        return $res;
    }
}
