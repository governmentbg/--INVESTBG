<?php

declare(strict_types=1);

namespace tests;

use zni\App;
use ArrayIterator;
use vakata\session\Native as Session;

trait HTTPTrait
{
    protected $request = null;
    protected $response = null;
    protected $cookies = [];
    protected $csrf = null;
    protected $stack = [];

    protected function exec(): void
    {
        static $app;
        if (!$app) {
            $app = App::init();
        }
        if (!count($this->stack)) {
            //file_put_contents(__DIR__ . '/dump.log', '');
            // prevent parsing request from globals
            $app->di()->register(\vakata\http\Request::fromString(
                'GET / HTTP/1.1' . "\r\n" .
                'Host: localhost' . "\r\n"
            ));
            $app->url()->setBasePath('/')->withHost('localhost');
            $app->auth()->addProvider(new \vakata\authentication\password\Password([ 'mockauth' => 'mockauth' ]));
            // mock usermanagement - to allow mockauth:mockauth credentials
            $app->di()->register(
                new MockUserManagementDatabase(
                    $app->db(),
                    [
                        'tableUsers'             => 'users',
                        'tableProviders'         => 'user_providers',
                        'tableGroups'            => 'grps',
                        'tablePermissions'       => 'permissions',
                        'tableGroupsPermissions' => 'group_permissions',
                        'tableUserGroups'        => 'user_groups'
                    ],
                    [],
                    $app->cache()
                )
            );
            // mock session
            $app->di()->register(new Session());
            $_SESSION = [];
            $_SESSION['testing'] = true;
            $this->stack = iterator_to_array($app->stack());
        }
        $this->request->getUrl()->setBasePath('/')->withHost('localhost');
        $this->response = $app->run(new ArrayIterator($this->stack), $this->request);
    }
    protected function clear(): self
    {
        $this->request = null;
        $this->response = null;
        $this->cookies = [];
        $this->csrf = null;
        return $this;
    }
    protected function get(string $url, bool $follow = false): self
    {
        $cookies = [];
        foreach ($this->cookies as $k => $v) {
            $cookies[] = $k . '=' . $v;
        }
        $url = str_replace('http://localhost/', '', $url);
        //file_put_contents(__DIR__ . '/dump.log', 'GET /'.ltrim($url, '/') . "\r\n", FILE_APPEND);
        $this->request = \vakata\http\Request::fromString(
            'GET /' . ltrim($url, '/') . ' HTTP/1.1' . "\r\n" .
            'Host: localhost' . "\r\n" .
            ($this->request !== null ? 'Referer: ' . $this->request->getUrl() . "\r\n" : '') .
            'Cookie: ' . implode('; ', $cookies) . "\r\n"
        );
        $this->exec();
        if ($this->response instanceof \vakata\http\Response) {
            foreach ($this->response->getHeader('Set-Cookie') as $cookie) {
                $cookie = explode('=', explode(';', $cookie)[0], 2);
                $this->cookies[$cookie[0]] = $cookie[1] ?? '';
            }
            $body = (string)$this->response->getBody();
            if (strpos($body, 'name="_csrf_token"')) {
                $this->csrf = explode('"', explode('value="', explode('name="_csrf_token"', $body, 2)[1], 2)[1], 2)[0];
            }
        }
        if ($follow && $this->response->hasHeader('Location')) {
            return $this->get($this->response->getHeaderLine('Location'), true);
        }
        return $this;
    }
    protected function post($url = null, array $data = []): self
    {
        if (is_array($url)) {
            $data = $url;
            $url = null;
        }
        if ($url === null) {
            $url = '/' . $this->request->getUrl()->getRealPath();
        }
        $url = str_replace('http://localhost/', '', $url);
        $cookies = [];
        foreach ($this->cookies as $k => $v) {
            $cookies[] = $k . '=' . $v;
        }
        $data['_csrf_token'] = $this->csrf;
        $data = http_build_query($data);
        $this->request = \vakata\http\Request::fromString(
            'POST /' . ltrim($url, '/') . ' HTTP/1.1' . "\r\n" .
            'Host: localhost' . "\r\n" .
            ($this->request !== null ? 'Referer: ' . $this->request->getUrl() . "\r\n" : '') .
            'Cookie: ' . implode('; ', $cookies) . "\r\n" .
            'Content-Type: application/x-www-form-urlencoded' . "\r\n" .
            'Content-Length: ' . strlen($data) . ";\r\n" .
            "\r\n" .
            $data
        );
        //file_put_contents(__DIR__ . '/dump.log', 'POST /'.ltrim($url, '/') . "\r\n", FILE_APPEND);
        $this->exec();
        if ($this->response instanceof \vakata\http\Response) {
            foreach ($this->response->getHeader('Set-Cookie') as $cookie) {
                $cookie = explode('=', explode(';', $cookie)[0], 2);
                $this->cookies[$cookie[0]] = $cookie[1] ?? '';
            }
            $body = (string)$this->response->getBody();
            if (strpos($body, 'name="_csrf_token"')) {
                $this->csrf = explode('"', explode('value="', explode('name="_csrf_token"', $body, 2)[1], 2)[1], 2)[0];
            }
        }
        return $this;
    }
    protected function follow(): self
    {
        if (!$this->response) {
            throw new \Exception('No response available to follow');
        }
        if (!$this->response->hasHeader('Location')) {
            throw new \Exception('No Location header set');
        }
        return $this->get($this->response->getHeaderLine('Location'));
    }
    protected function assertStatus($status, $message = ''): self
    {
        $this->assertEquals($status, $this->response->getStatusCode(), $message);
        return $this;
    }
    protected function assertHeader($name, $value): self
    {
        $this->assertEquals($value, $this->response->getHeaderLine($name));
        return $this;
    }
    protected function assertBodyContains($needle): self
    {
        $this->assertStringContainsString($needle, (string)$this->response->getBody());
        return $this;
    }
    protected function assertLocation($url): self
    {
        $this->assertEquals(trim($url, '/'), trim($this->request->getUrl()->getRealPath(), '/'));
        return $this;
    }
}
