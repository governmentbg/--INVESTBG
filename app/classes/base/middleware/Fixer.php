<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;

class Fixer
{
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        if (in_array(trim($req->getUrl()->getRealPath(), '/'), ['csp-report', 'ect-report', 'xss-report'])) {
            return new Response(200);
        }
        // json in raw POST
        if (
            !$req->getParsedBody() &&
            in_array($req->getMethod(), [ 'POST', 'PUT', 'PATCH', 'DELETE', 'COPY', 'LOCK', 'UNLOCK' ], true) &&
            stripos($req->getHeaderLine('Content-Type'), 'application/json') === 0
        ) {
            try {
                $json = trim((string)$req->getBody());
                $req->withParsedBody($json ? json_decode($json, true) : []);
            } catch (\Exception) {
            }
        }
        $res = $next($req);
        // prevent caching unless explicitly set (fixes CSRF tokens and the back button)
        if (!$res->hasCache()) {
            $res = $res->noCache();
        }
        // correct the status code if a Location header is present
        if ($res->hasHeader('Location') && $res->getStatusCode() === 200) {
            $res = $res->withStatus(303); // most frameworks use 302, but 303 is actually the correct status code
        }
        // remove body on head requests
        if ($req->getMethod() === 'HEAD') {
            $res = $res->setBody('');
        }
        return $res;
    }
}
