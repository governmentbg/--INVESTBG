<?php

declare(strict_types=1);

namespace webadmin\middleware;

use vakata\certificate\Certificate;
use vakata\http\Request;
use vakata\http\Response;

class Cert
{
    protected string $path;
    protected ?string $storage = null;

    public function __construct(string $path, ?string $storage = null)
    {
        $this->path = $path;
        $this->storage = $storage;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        if (
            trim($req->getUrl()->getRealPath(), '/') !== $this->path ||
            !in_array($req->getMethod(), [ 'GET', 'OPTIONS' ])
        ) {
            return $next($req);
        }
        if ($req->getUrl()->getScheme() !== 'https') {
            return (new Response(303))
                ->withHeader('Location', preg_replace('(^http:)', 'https:', $req->getUrl()->self(true)) ?? '');
        }
        $cert = '';
        $file = '';
        if (isset($_SERVER['SSL_CLIENT_VERIFY']) && $_SERVER['SSL_CLIENT_VERIFY'] === 'SUCCESS') {
            if (isset($_SERVER['SSL_CLIENT_M_SERIAL'])) {
                $cert = strtoupper(ltrim(trim($_SERVER['SSL_CLIENT_M_SERIAL']), '0'));
                if ($this->storage !== null) {
                    $file = $cert . '_' . md5($_SERVER['SSL_CLIENT_CERT']);
                    if (!is_file($this->storage . '/' . $file)) {
                        file_put_contents($this->storage . '/' . $file, $_SERVER['SSL_CLIENT_CERT']);
                    }
                }
            }
        }

        if ($req->isCors() || $req->isAjax() || $req->getMethod() === 'OPTIONS') {
            return (new Response(200, null, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET',
                'Access-Control-Allow-Headers' => 'X-Requested-With'
            ]))
                ->setBody(
                    $req->getMethod() === 'OPTIONS' ?
                        '' :
                        ($req->getQuery('full') ? $_SERVER['SSL_CLIENT_CERT'] : $cert)
                );
        }

        try {
            $token = $req->getAttribute('token');
            $data = Certificate::fromString($_SERVER['SSL_CLIENT_CERT']);
            $cert = $data->getSerialNumber() . ' / ' . $data->getAuthorityKeyIdentifier();
            $token->setClaim('SSL_CLIENT_M_SERIAL', $cert);
            if ($file) {
                $token->setClaim('SSL_CLIENT_M_SERIAL_FILE', $file);
            }
        } catch (\Throwable) {
        }
        return (new Response(303))
            ->withHeader('Location', $req->getUrl()->get(''));
    }
}
