<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\cache\CacheInterface;
use Laminas\Diactoros\Response\Serializer as ResponseSerializer;

class Cache
{
    protected CacheInterface $cache;
    protected int $ttl;
    protected int $max;
    /** @var callable $key */
    protected $key;

    public function __construct(
        CacheInterface $cache,
        int $ttl = 60,
        int $max = 0,
        ?callable $key = null
    ) {
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->max = $max;
        $this->key = $key ?? function (Request $req): string {
            return sha1($req->getMethod() . ' ' . (string)$req->getUrl());
        };
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        if (
            !in_array($req->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE']) ||
            !($key = call_user_func($this->key, $req))
        ) {
            return $next($req);
        }
        if ($cached = $this->cache->get($key)) {
            $tmp = ResponseSerializer::fromString($cached)->withHeader('X-Cache-Hit', $key);
            return new Response(
                $tmp->getStatusCode(),
                (string)$tmp->getBody(),
                $tmp->getHeaders()
            );
        }
        $res = $next($req);
        $data = null;
        $cache = true;
        if ($res->hasHeader('X-No-Cache')) {
            $cache = false;
        }
        if ($cache && $res->getStatusCode() < 200) {
            $cache = false;
        }
        if ($cache && $res->getStatusCode() >= 400) {
            $cache = false;
        }
        if ($cache && $this->max) {
            $size = $res->getBody()->getSize();
            if ($size > $this->max) {
                $cache = false;
            }
        }
        if ($cache) {
            $data = ResponseSerializer::toString($res);
            if ($this->max && strlen($data) > $this->max) {
                $cache = false;
            }
        }
        if ($cache && isset($data)) {
            $this->cache->set(
                $key,
                $data,
                $res->hasHeader('X-Cache-For') ? (int)$res->getHeaderLine('X-Cache-For') : $this->ttl
            );
        }
        return $res
            ->withoutHeader('X-Cache-For')
            ->withoutHeader('X-No-Cache');
    }
}
