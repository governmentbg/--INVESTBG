<?php

declare(strict_types=1);

namespace webadmin\middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\authentication\totp\TOTP as TOTPProvider;
use vakata\user\UserManagementInterface;
use vakata\user\UserException;
use vakata\user\User;
use vakata\user\Provider;
use vakata\random\Generator;
use vakata\views\Views;

class TFA
{
    protected UserManagementInterface $usrm;
    protected string $path;
    protected Views $view;
    protected string $appname;
    protected bool $force;
    protected bool $remember;

    public function __construct(
        UserManagementInterface $usrm,
        Views $view,
        string $appname,
        string $path,
        bool $force = false,
        bool $remember = true
    ) {
        $this->usrm = $usrm;
        $this->path = $path;
        $this->view = $view;
        $this->force = $force;
        $this->appname = $appname;
        $this->remember = $remember;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $url   = $req->getUrl();
        $user  = $req->getAttribute('user');
        $token = $req->getAttribute('token');

        // continue is no user, user without TFA, TFA already passed or when impersonating
        if (
            !$user ||
            (!$user->get("tfa") && !$this->force) ||
            $token->getClaim("tfa") === 'OK' ||
            $token->getClaim('impersonate')
        ) {
            return $next($req);
        }

        // collect all TFA providers for the user
        $providers = [];
        foreach ($user->getProviders() as $provider) {
            if ($provider->enabled()) {
                switch ($provider->getProvider()) {
                    case 'TOTP':
                        $providers['totp'] = true;
                        break;
                    case 'RecoveryCode':
                        $providers['recovery'] = true;
                        break;
                    case 'Certificate':
                        $providers['certificate'] = true;
                        break;
                }
            }
        }

        // continue if no TFA providers are registered
        if (!count($providers)) {
            return $next($req);
        }

        // check for "remember this device" cookie
        try {
            $device = $req->getCookieParams()[$this->appname . "_DEVICE"] ?? null;
            if ($this->remember && $device && $user === $this->usrm->getUserByProviderID('TFADeviceToken', $device)) {
                $token->setClaim('tfa', 'OK');
                return $next($req);
            }
        } catch (UserException) {
        }

        // try certificate
        if ($token->hasClaim('SSL_CLIENT_M_SERIAL')) {
            try {
                if (
                    $user === $this->usrm->getUserByProviderID(
                        'Certificate',
                        $token->getClaim('SSL_CLIENT_M_SERIAL'),
                        true
                    )
                ) {
                    $token->setClaim('tfa', 'OK');
                    return $next($req);
                }
            } catch (UserException) {
            }
        }

        // process TFA
        if (trim($url->getRealPath(), '/') === $this->path) {
            if ($req->getMethod() === 'GET') {
                return (new Response())
                    ->expireCookie($this->appname . '_DEVICE', 'Path=' . $url->getBasePath() . '; HttpOnly')
                    ->setBody(
                        $this->view->render('webadmin::login/tfa', [
                            'error' => $req->getQuery('error') ?
                                'common.login.wrongcode' :
                                (!$user->get('tfa') ? 'common.login.enabletotp' : ''),
                            "name" => mb_substr($req->getHeaderLine("User-Agent"), 0, 500),
                            'providers' => $providers,
                            'certerror' => $token->hasClaim('SSL_CLIENT_M_SERIAL'),
                            'remember' => $this->remember
                        ])
                    );
            }
            if ($req->getMethod() === 'POST') {
                $found = false;
                if ($req->getPost('totp')) {
                    $code = str_replace(' ', '', (string)$req->getPost('totp'));
                    if (strlen($code) !== 6) {
                        foreach ($user->getProviders() as $provider) {
                            if (
                                $provider->getProvider() === 'RecoveryCode' &&
                                $provider->enabled() &&
                                $provider->getUsed() === null &&
                                $provider->getID() === $code
                            ) {
                                $provider->setUsed('now');
                                $this->usrm->saveUser($user);
                                $found = true;
                            }
                        }
                        if (!$found) {
                            return (new Response(303))
                                ->withHeader('Location', $url->linkTo($this->path, ['error' => 'recovery']));
                        }
                    } else {
                        foreach ($user->getProviders() as $provider) {
                            if ($provider->getProvider() === 'TOTP' && $provider->enabled()) {
                                try {
                                    $totp = new TOTPProvider($provider->getID(), [ 'title' => $this->appname ]);
                                    $totp->authenticate(['totp' => $code]);
                                    $found = true;
                                    break;
                                } catch (\Throwable) {
                                }
                            }
                        }
                        if (!$found) {
                            return (new Response(303))
                                ->withHeader('Location', $url->linkTo($this->path, ['error' => 'code']));
                        }
                    }
                }
                if ($found) {
                    $res = new Response();
                    if ($req->getPost('remember')) {
                        $device = Generator::string(32);
                        if ($user instanceof User) {
                            $user->addProvider(
                                new Provider('TFADeviceToken', $device, mb_substr($req->getPost("name", ''), 0, 500))
                            );
                            $this->usrm->saveUser($user);
                        }
                        $res = $res->withAddedHeader(
                            'Set-Cookie',
                            implode(
                                '',
                                [
                                    $this->appname . '_DEVICE=' . urlencode($device) . '; ',
                                    'Path=' . $url->getBasePath() . '; ',
                                    'Expires=' . date('r', time() + 3 * 365 * 24 * 3600) . '; ',
                                    'HttpOnly',
                                    ($url->getScheme() === 'https' ? '; Secure' : '')
                                ]
                            )
                        );
                    } else {
                        $res = $res->expireCookie(
                            $this->appname . '_DEVICE',
                            'Path=' . $url->getBasePath() . '; HttpOnly'
                        );
                    }
                    $token->setClaim('tfa', 'OK');
                    $sess = $req->getAttribute('session');
                    if ($sess !== null) {
                        $redirect = $sess->get('_TFA_REDIRECT');
                        if ($redirect) {
                            $sess->del('_TFA_REDIRECT');
                            return $res->withStatus(303)->withHeader('Location', $url->linkTo(trim($redirect, '/')));
                        }
                    }
                    return $res->withStatus(303)->withHeader('Location', $url->linkTo(''));
                } else {
                    return (new Response(303))
                                    ->withHeader('Location', $url->linkTo($this->path, ['error' => 'code']));
                }
            }
        }

        // redirect to TFA page (optionally save current destination)
        $sess = $req->getAttribute('session');
        if ($sess !== null) {
            $sess->set('_TFA_REDIRECT', $url->getRealPath());
        }
        return (new Response(303))->withHeader('Location', $url->linkTo($this->path));
    }
}
