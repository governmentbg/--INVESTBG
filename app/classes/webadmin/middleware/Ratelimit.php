<?php

declare(strict_types=1);

namespace webadmin\middleware;

use vakata\cache\CacheInterface;
use vakata\http\Request;
use vakata\http\Response;

class Ratelimit
{
    protected CacheInterface $cache;
    protected int $requests;
    protected int $seconds;

    public function __construct(CacheInterface $cache, int $requests = 10, int $seconds = 2)
    {
        $this->cache = $cache;
        $this->requests = $requests;
        $this->seconds = $seconds;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        if ($req->getAttribute('client-ip')) {
            $key = "ratelimit_" . $req->getAttribute('client-ip');
            $tmp = $this->cache->get($key);
            if (!$tmp) {
                $tmp = [ 's' => time(), 'c' => 0 ];
            }
            if ($tmp['c'] < PHP_INT_MAX) {
                $tmp['c']++;
            }
            $this->cache->set($key, $tmp, $this->seconds);
            if ($tmp['c'] > 1 && $tmp['c'] / ((time() - $tmp['s']) + 1) > $this->requests / $this->seconds) {
                throw new \Exception("Rate limit exceeded", 429);
            }
        }
        return $next($req);
    }
}
