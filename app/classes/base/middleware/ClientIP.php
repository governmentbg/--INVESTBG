<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\http\Request;
use vakata\http\Response;

class ClientIP
{
    public static function ip(): ?string
    {
        $ip = null;
        if (static::validIP($_SERVER['REMOTE_ADDR'] ?? '')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $sources = [
            'HTTP_X_FORWARDED_FOR' => [
                '10.0.0.0/8',
                '127.0.0.0/8',
                '172.16.0.0/12',
                '192.0.0.0/29',
                '192.168.0.0/16',
                '::1/128',
                'fc00::/7',
                'fd00::/7'
            ],
            'HTTP_X_REAL_IP' => [
                '10.0.0.0/8',
                '127.0.0.0/8',
                '172.16.0.0/12',
                '192.0.0.0/29',
                '192.168.0.0/16',
                '::1/128',
                'fc00::/7',
                'fd00::/7'
            ],
            'HTTP_CF_CONNECTING_IP' => [
                '173.245.48.0/20',
                '103.21.244.0/22',
                '103.22.200.0/22',
                '103.31.4.0/22',
                '141.101.64.0/18',
                '108.162.192.0/18',
                '190.93.240.0/20',
                '188.114.96.0/20',
                '197.234.240.0/22',
                '198.41.128.0/17',
                '162.158.0.0/15',
                '104.16.0.0/13',
                '104.24.0.0/14',
                '172.64.0.0/13',
                '131.0.72.0/22',
                '2400:cb00::/32',
                '2606:4700::/32',
                '2803:f800::/32',
                '2405:b500::/32',
                '2405:8100::/32',
                '2a06:98c0::/29',
                '2c0f:f248::/32'
            ]
        ];
        foreach ($sources as $src => $masks) {
            if (!isset($_SERVER[$src]) || !is_string($_SERVER[$src])) {
                continue;
            }
            $tmp = trim(array_reverse(explode(',', $_SERVER[$src]))[0]);
            if ($ip && $tmp && static::validIP($tmp) && static::checkIP($ip, $masks)) {
                $ip = $tmp;
                break;
            }
        }
        return $ip;
    }
    /**
     * @param array<string> $masks
     * @return boolean
     */
    public static function check(array $masks): bool
    {
        $ip = static::ip();
        if (!isset($ip)) {
            return false;
        }
        return static::checkIP($ip, $masks);
    }

    public static function validIP(string $ip): bool
    {
        return $ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false;
    }

    /**
     * @param string $ip
     * @param array<string> $masks
     * @return boolean
     */
    public static function checkIP(string $ip, array $masks): bool
    {
        $method = substr_count($ip, ':') > 1 ? 'checkIP6' : 'checkIP4';
        foreach ($masks as $mask) {
            if (static::$method($ip, $mask)) {
                return true;
            }
        }
        return false;
    }

    public static function checkIP4(string $ip, string $mask): bool
    {
        if (!filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            return false;
        }
        if (str_contains($ip, ':') || str_contains($mask, ':')) {
            return false;
        }

        if (str_contains($mask, '/')) {
            [$address, $netmask] = explode('/', $mask, 2);
            if ('0' === $netmask) {
                return filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) !== false;
            }
            $netmask = (int)$netmask;
            if ($netmask < 0 || $netmask > 32) {
                return false;
            }
        } else {
            $address = $mask;
            $netmask = 32;
        }

        if (false === ip2long($address)) {
            return false;
        }

        return 0 === substr_compare(sprintf('%032b', ip2long($ip)), sprintf('%032b', ip2long($address)), 0, $netmask);
    }

    public static function checkIP6(string $ip, string $mask): bool
    {
        if (str_contains($ip, '.') || str_contains($mask, '.')) {
            return false;
        }

        if (!filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
            return false;
        }

        if (str_contains($mask, '/')) {
            [$address, $netmask] = explode('/', $mask, 2);

            if ('0' === $netmask) {
                return (bool) unpack('n*', (string)@inet_pton($address));
            }
            $netmask = (int)$netmask;
            if ($netmask < 1 || $netmask > 128) {
                return false;
            }
        } else {
            $address = $ip;
            $netmask = 128;
        }

        $bytesAddr = unpack('n*', (string)@inet_pton($address));
        $bytesTest = unpack('n*', (string)@inet_pton($ip));

        if (!$bytesAddr || !$bytesTest) {
            return false;
        }

        for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; ++$i) {
            $left = $netmask - 16 * ($i - 1);
            $left = ($left <= 16) ? $left : 16;
            $msk = ~(0xFFFF >> $left) & 0xFFFF;
            if (($bytesAddr[$i] & $msk) != ($bytesTest[$i] & $msk)) {
                return false;
            }
        }

        return true;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $req->withAttribute('client-ip', static::ip());
        return $next($req)->withHeader('X-Client-IP', $req->getAttribute('client-ip') ?? '');
    }
}
