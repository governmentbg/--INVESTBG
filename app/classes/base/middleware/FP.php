<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;

/** @SuppressWarnings("PHPMD.ShortClassName") */
class FP
{
    /** @var array<string,string|array<string>> $fp */
    protected array $fp;

    /**
     * @param array<string,string|array<string>> $fp
     */
    public function __construct(array $fp = [])
    {
        $defaults = [
            'ambient-light-sensor' => "'none'",
            'autoplay' => "'none'",
            'accelerometer' => "'none'",
            'camera' => "'none'",
            'display-capture' => "'none'",
            'document-domain' => "'none'",
            'encrypted-media' => "'none'",
            'fullscreen' => "'none'",
            'geolocation' => "'none'",
            'gyroscope' => "'none'",
            'magnetometer' => "'none'",
            'microphone' => "'none'",
            'midi' => "'none'",
            'payment' => "'none'",
            // 'speaker' => "'none'",
            'sync-xhr' => "'none'",
            'usb' => "'none'",
            'vr' => "'none'",
            'wake-lock' => "'none'",
        ];
        $this->fp = array_merge($defaults, $fp);
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $res = $next($req);
        if (count($this->fp)) {
            $value = '';
            foreach ($this->fp as $k => $v) {
                $value .= $k . ' ' . implode(' ', is_array($v) ? $v : [$v]) . '; ';
            }
            $res = $res->withHeader('Feature-Policy', $value);
        }
        return $res;
    }
}
