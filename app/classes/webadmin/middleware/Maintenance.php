<?php

declare(strict_types=1);

namespace webadmin\middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\user\User;

class Maintenance
{
    protected string $group;
    protected string $login;

    public function __construct(string $group, string $login)
    {
        $this->group = $group;
        $this->login = $login;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $user = $req->getAttribute('user');
        if ($user instanceof User && !$user->inGroup($this->group)) {
            return (new Response(303))
                ->withHeader('Location', $req->getUrl()->linkTo($this->login))
                ->withHeader('X-Log', 'Maintenance mode activated');
        }
        return $next($req);
    }
}
