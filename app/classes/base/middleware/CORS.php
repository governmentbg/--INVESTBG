<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;

class CORS
{
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        if (!$req->isCors()) {
            return $next($req);
        }
        $headers = [];
        if ($req->hasHeader('Access-Control-Request-Headers')) {
            $headers = array_map(
                'trim',
                array_filter(explode(',', $req->getHeaderLine('Access-Control-Request-Headers')))
            );
        }
        $headers[] = 'Authorization';
        $headers = array_unique($headers);
        if ($req->getMethod() === 'OPTIONS') {
            return (new Response())
                ->withHeader('Access-Control-Allow-Origin', $req->getHeaderLine('Origin'))
                ->withHeader('Access-Control-Max-Age', '3600')
                ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,HEAD,DELETE')
                ->withHeader('Access-Control-Allow-Headers', implode(', ', $headers))
                ->withHeader('Access-Control-Allow-Credentials', 'false');
        }
        // only authorized CORS requests!
        if (!preg_match('(token|basic|bearer|oauth)i', $req->getHeaderLine('Authorization'))) {
            throw new \Exception("CORS No authorization", 403);
        }
        return $next($req)
            ->withHeader('Access-Control-Allow-Origin', $req->getHeaderLine('Origin'))
            ->withHeader('Access-Control-Max-Age', '3600')
            ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,HEAD,DELETE')
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $headers))
            ->withHeader('Access-Control-Allow-Credentials', 'false');
    }
}
