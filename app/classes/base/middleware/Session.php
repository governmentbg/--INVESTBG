<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\session\SessionInterface;

class Session
{
    protected SessionInterface $session;
    protected bool $autostart;
    protected int $timeout;
    protected int $regenerate;
    protected string $cookie;

    public function __construct(
        SessionInterface $session,
        bool $autostart = false,
        string $cookie = 'PHPSESSID',
        int $timeout = 1800,
        int $regenerate = 0
    ) {
        $this->session = $session;
        $this->autostart = $autostart;
        $this->cookie = $cookie;
        $this->timeout = $timeout;
        $this->regenerate = $regenerate;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $req->withAttribute('session', $this->session);
        $sessid = $req->getCookieParams()[$this->cookie] ?? '';
        if ($sessid) {
            $this->session->id($sessid);
        }
        if (
            php_sapi_name() !== 'cli' &&
            !preg_match('(token|basic|bearer|oauth)i', $req->getHeaderLine('Authorization')) &&
            $this->autostart
        ) {
            $this->session->start();
        }
        $res = $next($req);
        if ($this->session->isStarted() && $this->session->id()) {
            if ($this->session->get('_SESSID_REGENERATED') === null) {
                $this->session->set('_SESSID_REGENERATED', time());
            }
            if ($this->regenerate && (int)$this->session->get('_SESSID_REGENERATED') + $this->regenerate < time()) {
                $this->session->regenerate(true);
                $this->session->set('_SESSID_REGENERATED', time());
            }
            if ($sessid !== $this->session->id()) {
                $res = $res->withAddedHeader(
                    'Set-Cookie',
                    implode(
                        '',
                        [
                            $this->cookie . '=' . $this->session->id() . '; ',
                            'Path=' . $req->getUrl()->getBasePath() . '; ',
                            'Expires=' . date('r', time() + $this->timeout * 2) . '; ',
                            'HttpOnly',
                            ($req->getUrl()->getScheme() === 'https' ? '; Secure' : '')
                        ]
                    )
                );
            }
            $this->session->close();
        }
        return $res;
    }
}
