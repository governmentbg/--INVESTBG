<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;

class Minify
{
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $res = $next($req);
        if (
            !$res->hasCallback() &&
            (!$res->hasHeader('Content-Type') || strpos($res->getHeaderLine('Content-Type'), 'html') !== false) &&
            (
                !$res->hasHeader('Content-Disposition') ||
                strpos($res->getHeaderLine('Content-Disposition'), 'attachment') === false
            )
        ) {
            $body = (string)$res->getBody();
            if (strpos($body, '<pre') === false) {
                $res->setBody(preg_replace([ '/^([\t ])+/m', '/([\t ])+$/m' ], '', $body) ?? '');
            }
        }
        return $res;
    }
}
