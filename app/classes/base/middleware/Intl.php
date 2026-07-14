<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\intl\Intl as Translations;

class Intl
{
    protected Translations $intl;
    protected string $cookieName;

    public function __construct(
        Translations $intl,
        string $cookieName = '_LOCALE'
    ) {
        $this->intl = $intl;
        $this->cookieName = $cookieName;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $available = $this->intl->getLanguages();
        $lng = strtolower(
            $req->getCookieParams()[$this->cookieName] ??
            $req->getPreferredResponseLanguage('bg', $available)
        );
        $locale = in_array($lng, $available) ? $lng : ($available[0] ?? null);
        $this->intl->setLanguage($locale);
        $req->withAttribute(
            'locale',
            $locale ? $this->intl->get('_locale.code.short', [], (string)$locale) : null
        );
        return $next($req);
    }
}
