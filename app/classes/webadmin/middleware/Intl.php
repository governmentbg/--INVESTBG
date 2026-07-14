<?php

declare(strict_types=1);

namespace webadmin\middleware;

use base\middleware\Intl as BaseIntl;
use Closure;
use vakata\http\Request;
use vakata\http\Response;
use vakata\intl\Intl as Translations;

class Intl extends BaseIntl
{
    protected ?Closure $missing;

    /**
     * @param Translations $intl
     * @param string $cookieName
     * @param ?Closure $missing
     */
    public function __construct(
        Translations $intl,
        string $cookieName = '_LOCALE',
        ?Closure $missing = null
    ) {
        parent::__construct($intl, $cookieName);
        $this->missing = $missing;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $res = parent::__invoke($req, $next);
        $locale = $req->getAttribute('locale');
        if ($this->missing && $locale) {
            $m = [];
            foreach ($this->intl->getUsed() as $k => $v) {
                $k = (string)$k;
                if ($k === '') {
                    continue;
                }
                if (($v === null || mb_strtolower($k) === mb_strtolower($v))) {
                    $m[] = $k;
                }
            }
            $this->missing->__invoke($locale, $m);
        }
        return $res;
    }
}
