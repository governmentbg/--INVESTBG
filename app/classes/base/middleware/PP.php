<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;

/** @SuppressWarnings("PHPMD.ShortClassName") */
class PP
{
    /** @var array<string,string|array<string>> $pp */
    protected array $pp;

    /**
     * @param array<string,string|array<string>> $pp
     */
    public function __construct(array $pp = [])
    {
        $defaults = [
            'camera' => 'none',
            'display-capture' => 'none',
            'fullscreen' => 'none',
            'geolocation' => 'none',
            'microphone' => 'none',
            'usb' => 'none',
            'web-share' => 'none',
            // ALL BELOW ARE EXPERIMENTAL
            'accelerometer' => 'none',
            'ambient-light-sensor' => 'none',
            'autoplay' => 'none',
            'battery' => 'none',
            'document-domain' => 'none',
            'encrypted-media' => 'none',
            'execution-while-not-rendered' => 'self',
            'execution-while-out-of-viewport' => 'self',
            'gamepad' => 'none',
            'gyroscope' => 'none',
            'hid' => 'none',
            'identity-credentials-get' => 'none',
            'idle-detection' => 'none',
            'local-fonts' => 'none',
            'magnetometer' => 'none',
            'midi' => 'none',
            'otp-credentials' => 'none',
            'payment' => 'none',
            'picture-in-picture' => 'none',
            'publickey-credentials-create' => 'none',
            'publickey-credentials-get' => 'none',
            'screen-wake-lock' => 'none',
            'serial' => 'none',
            'speaker-selection' => 'none',
            'storage-access' => 'none',
            'xr-spatial-tracking' => 'none'
        ];
        $this->pp = array_merge($defaults, $pp);
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $res = $next($req);
        if (count($this->pp)) {
            $temp = [];
            foreach ($this->pp as $k => $v) {
                $temp[] = $k . '=' . (is_array($v) ? '(' . implode(' ', $v) . ')' : $v);
            }
            $res = $res->withHeader('Permissions-Policy', implode(', ', $temp));
        }
        return $res;
    }
}
