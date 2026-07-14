<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\views\Views;
use vakata\http\Request;
use vakata\http\Response;
use vakata\random\Generator;

class CSP
{
    /** @var array<string,string|array<string>> $csp */
    protected array $csp;
    protected Views $views;
    protected ?string $report = null;

    /**
     * @param array<string,string|array<string>> $csp
     */
    public function __construct(Views $views, array $csp = [], ?string $report = null)
    {
        $this->views = $views;
        $this->csp = count($csp) ? $csp : [ 'default-src' => "'self'" ];
        $this->report = $report;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $nonce = $req->isAjax() && !$req->isCors() && $req->hasHeader('X-CSPNonce') ?
            $req->getHeaderLine('X-CSPNonce') :
            Generator::string();
        $this->views->addData([ 'cspNonce' => $nonce ]);
        $res = $next($req);
        if (count($this->csp)) {
            if ($this->report) {
                $this->csp['report-uri'] = $req->getUrl()->linkTo($this->report, [], true);
            }
            $value = '';
            foreach ($this->csp as $k => $v) {
                $value .= $k . ' ' . implode(' ', is_array($v) ? $v : [$v]) . '; ';
            }
            $res = $res->withHeader('Content-Security-Policy', str_replace('{__NONCE__}', $nonce, $value));
        }
        return $res;
    }
}
