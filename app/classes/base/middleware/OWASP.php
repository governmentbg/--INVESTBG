<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;

class OWASP
{
    protected ?string $xss;

    public function __construct(?string $xss = null)
    {
        $this->xss = $xss;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $res = $next($req)
            ->withHeader('X-UA-Compatible', 'IE=edge') // IE specific
            ->withHeader('X-Download-Options', 'noopen') // IE specific
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('Referrer-Policy', 'same-origin')
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->withHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->withHeader('Cross-Origin-Embedder-Policy', 'require-corp')
            ->withHeader(
                'X-XSS-Protection',
                '1' . ($this->xss ? '; report=' . $req->getUrl()->linkTo($this->xss, [], true) : '')
            );
        $contentType = $res->getHeaderLine('Content-Type');
        if (
            strpos($contentType, 'jscript') !== false ||
            strpos($contentType, 'javascript') !== false ||
            strpos($contentType, 'ecmascript') !== false ||
            strpos($contentType, 'text/css') !== false
        ) {
            $res = $res->withHeader('X-Content-Type-Options', 'nosniff'); // IE specific
        }
        return $res;
    }
}
