<?php

declare(strict_types=1);

namespace webadmin\components\router;

use RuntimeException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Throwable;
use vakata\http\Request;
use vakata\http\Response;

class Router
{
    protected RouteCollection $routes;

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }
    public function add(array|string $methods, string $url, callable $callback, ?string $name = null): static
    {
        $this->routes->add(
            $name ?? md5($url . implode('_', (is_array($methods) ? $methods : [ $methods ]))),
            (new Route($url, [ 'callback' => $callback ]))
                ->setMethods($methods)
        );

        return $this;
    }
    public function get(string $url, callable $callback, ?string $name = null): static
    {
        return $this->add('get', $url, $callback, $name);
    }
    public function post(string $url, callable $callback, ?string $name = null): static
    {
        return $this->add('post', $url, $callback, $name);
    }
    public function delete(string $url, callable $callback, ?string $name = null): static
    {
        return $this->add('delete', $url, $callback, $name);
    }
    public function put(string $url, callable $callback, ?string $name = null): static
    {
        return $this->add('put', $url, $callback, $name);
    }
    public function head(string $url, callable $callback, ?string $name = null): static
    {
        return $this->add('head', $url, $callback, $name);
    }
    public function options(string $url, callable $callback, ?string $name = null): static
    {
        return $this->add('options', $url, $callback, $name);
    }
    public function patch(string $url, callable $callback, ?string $name = null): static
    {
        return $this->add('patch', $url, $callback, $name);
    }
    public function run(Request $request): Response
    {
        $url = $request->getUrl();

        $context = new RequestContext(
            $url->getBasePath(true),
            $request->getMethod(),
            $url->getHost(),
            $url->getScheme(),
            $url->getPort() ?? 80,
            $url->getPort() ?? 443,
            $url->getPath(),
            $url->getQuery()
        );

        $matcher = new UrlMatcher($this->routes, $context);

        try {
            /** @var array{callback:callable,_route:string} */
            $found = $matcher->match($request->getUrl()->getRealPath(true));
        } catch (Throwable $e) {
            throw new RuntimeException('Method not found', 404);
        }

        return call_user_func($found['callback'], $request);
    }
}
