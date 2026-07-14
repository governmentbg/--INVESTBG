<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;

class HTTPS
{
    protected int $expectCTmaxAge;
    protected int $STSmaxAge;
    protected bool $enforceExpectCT;
    protected ?string $report;

    public function __construct(
        int $expectCTmaxAge = 30,
        int $STSmaxAge = 30,
        bool $enforceExpectCT = false,
        ?string $report = null
    ) {
        $this->expectCTmaxAge = $expectCTmaxAge;
        $this->STSmaxAge = $STSmaxAge;
        $this->enforceExpectCT = $enforceExpectCT;
        $this->report = $report;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        if ($req->getUrl()->getScheme() !== 'https') {
            return (new Response())
                ->withStatus(308)
                ->withHeader('Location', (string)$req->getUrl()->withScheme('https'));
        }
        return $next($req)
            ->withHeader(
                'Expect-CT',
                implode(', ', array_filter([
                    'max-age=' . $this->expectCTmaxAge,
                    ($this->enforceExpectCT ? 'enforce' : null),
                    ($this->report ? 'report-uri=' . $req->getUrl()->linkTo($this->report, [], true) : null),
                ]))
            )
            ->withHeader(
                'Strict-Transport-Security',
                'max-age=' . $this->STSmaxAge
            );
    }
}
