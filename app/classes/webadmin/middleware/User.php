<?php

declare(strict_types=1);

namespace webadmin\middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\user\UserManagementInterface;
use vakata\user\UserException;
use vakata\jwt\JWT;
use Laminas\Diactoros\HeaderSecurity;
use vakata\user\User as VUser;

class User
{
    protected UserManagementInterface $usrm;
    protected string $signatureKey;
    protected string $encryptionKey;
    protected string $appname;
    protected int $timeout;
    protected ?string $path;
    protected bool $updateUsed;
    protected string $tokenClaims;

    public function __construct(
        string $signatureKey,
        string $encryptionKey,
        string $appname,
        UserManagementInterface $usrm,
        int $timeout = 1200,
        ?string $path = null,
        bool $updateUsed = true,
        string $tokenClaims = ''
    ) {
        $this->usrm = $usrm;
        $this->signatureKey = $signatureKey;
        $this->encryptionKey = $encryptionKey;
        $this->appname = $appname;
        $this->timeout = $timeout;
        $this->path = $path;
        $this->updateUsed = $updateUsed;
        $this->tokenClaims = $tokenClaims;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $token   = null;
        $user    = null;
        $prev    = null;
        /** @var ?\vakata\session\SessionInterface $session */
        $session = $req->getAttribute('session');
        $context = '';
        $valid_token = null;

        try {
            // authorization header
            if (preg_match('(token|bearer|oauth)i', $req->getHeaderLine('Authorization'))) {
                $temp = explode(' ', trim($req->getHeaderLine('Authorization')), 2);
                if (count($temp) === 2) {
                    switch (strtolower($temp[0])) {
                        case 'token':
                        case 'oauth':
                        case 'bearer':
                            $token = JWT::fromString($temp[1], $this->encryptionKey);
                            break;
                        default:
                            break;
                    }
                }
            } else {
                // token from cookie
                $cookies = $req->getCookieParams();
                if (isset($cookies[$this->appname . '_TOKEN'])) {
                    $token = JWT::fromString($cookies[$this->appname . '_TOKEN'], $this->encryptionKey);
                }
            }
            // process token
            if ($token) {
                $context .= 'TOKEN: ' . $token->getClaim('iss', '') . ' ' .
                    ($token->getClaim('iss', '') === $this->appname ? 'OK' : 'FAIL');
                $claims = ['iss' => $this->appname ];
                $initialClaims = $token->getClaims();
                if ($token->hasClaim('sess') && $token->getClaim('sess', '')) {
                    $context .= ' / ' . $token->getClaim('sess', '') . ' ' .
                        ($token->getClaim('sess', '') === $session?->id() ? 'OK' : 'FAIL');
                    $claims['sess'] = $session?->id() ?? '';
                }
                if ($token->hasClaim('ip')) {
                    $context .= ' / ' . $token->getClaim('ip', '') . ' ' .
                        ($token->getClaim('ip', '') === $req->getAttribute('client-ip') ? 'OK' : 'FAIL');
                    $claims['ip'] = $req->getAttribute('client-ip');
                }
                if ($token->hasClaim('ua')) {
                    $context .= ' / ' . $token->getClaim('ua', '') . ' ' .
                        ($token->getClaim('ua', '') === md5($req->getHeaderLine('User-Agent')) ? 'OK' : 'FAIL');
                    $claims['ua'] = md5($req->getHeaderLine('User-Agent'));
                }
                if (
                    $token->isSigned() &&
                    $token->verify($this->signatureKey, 'HS256') &&
                    $token->isValid($claims) &&
                    isset($initialClaims['provider']) &&
                    isset($initialClaims['id'])
                ) {
                    $context .= ' / VALID / ' . $initialClaims['provider'] . ' / ' . $initialClaims['id'];
                    $valid_token = $initialClaims;
                    try {
                        $user = $this->usrm->getUserByProviderID(
                            $initialClaims['provider'],
                            $initialClaims['id'],
                            $this->updateUsed && !$token->hasClaim('login')
                        );
                        if (!$token->hasClaim('login')) {
                            $token->setClaim('login', time());
                        }
                        if (
                            !preg_match('(token|bearer|oauth)i', $req->getHeaderLine('Authorization')) &&
                            !$token->hasClaim('loginid')
                        ) {
                            $loginid = md5(time() . '/' . ($session?->id() ?? ''));
                            $sessions = json_decode($user->get('sessions') ?? '', true) ?? [];
                            $sessions[$loginid] = [
                                'cr' => time(),
                                'up' => time(),
                                'ip' => $req->getAttribute('client-ip'),
                                'ua' => $req->getHeaderLine('User-Agent')
                            ];
                            foreach ($sessions as $sess => $sessionData) {
                                if (!isset($sessionData['up']) || time() - $sessionData['up'] > $this->timeout) {
                                    unset($sessions[$sess]);
                                }
                            }
                            $sessions = array_slice($sessions, -100);
                            $user->set('sessions', json_encode($sessions));
                            $token->setClaim('loginid', $loginid);
                            $token->setClaim('loginid_time', time());
                            $this->usrm->saveUser($user);
                        }
                        if (
                            $token->hasClaim('loginid_time') &&
                            time() - $token->getClaim('loginid_time') > $this->timeout
                        ) {
                            $sessions = json_decode($user->get('sessions') ?? '', true) ?? [];
                            $loginid = $token->getClaim('loginid');
                            if (isset($sessions[$loginid])) {
                                $sessions[$loginid]['up'] = time();
                            }
                            foreach ($sessions as $sess => $sessionData) {
                                if (!isset($sessionData['up']) || time() - $sessionData['up'] > $this->timeout) {
                                    unset($sessions[$sess]);
                                }
                            }
                            $sessions = array_slice($sessions, -100);
                            $user->set('sessions', json_encode($sessions));
                            $token->setClaim('loginid_time', time());
                            $this->usrm->saveUser($user);
                        }
                        $context .= ' - FOUND';
                    } catch (UserException $e) {
                        $context .= ' - MISSING';
                    }
                } else {
                    if (
                        $token->isSigned() &&
                        $token->verify($this->signatureKey, 'HS256') &&
                        isset($initialClaims['provider']) &&
                        isset($initialClaims['id'])
                    ) {
                        $context .= ' / INVALID / ' . $initialClaims['provider'] . ' / ' . $initialClaims['id'];
                        $valid = true;
                        foreach ($claims as $k => $v) {
                            if (!isset($initialClaims[$k]) || $initialClaims[$k] != $v) {
                                $valid = false;
                                break;
                            }
                        }
                        if ($valid) {
                            try {
                                $prev = $this->usrm->getUserByProviderID(
                                    $initialClaims['provider'],
                                    $initialClaims['id'],
                                    false
                                );
                                if (!($prev instanceof VUser)) {
                                    $prev = null;
                                }
                            } catch (UserException) {
                            }
                        }
                    }
                    $token = null;
                }
            }
            // impersonation
            if (
                $user &&
                $token &&
                $token->getClaim('impersonate') &&
                $user->get('disabled') == false &&
                $user->hasPermission('users/impersonate', true)
            ) {
                try {
                    $user = $this->usrm->getUser((string)$token->getClaim('impersonate'));
                    if (!($user instanceof VUser)) {
                        throw new \Exception();
                    }
                    $user->impersonated = true;
                } catch (\Exception) {
                }
            }
        } catch (\Exception) {
            $user = null;
        }
        if (!$token || !$user || !($user instanceof VUser)) {
            $newClaims = [
                'iss'  => $this->appname
            ];
            foreach (array_filter(explode(',', $this->tokenClaims)) as $c) {
                switch ($c) {
                    case 'ip':
                        $newClaims['ip'] = $req->getAttribute('client-ip');
                        break;
                    case 'ua':
                        $newClaims['ua'] = md5($req->getHeaderLine('User-Agent'));
                        break;
                    case 'sess':
                        $newClaims['sess'] = $session?->id() ?? '';
                        break;
                }
            }
            $token = new JWT($newClaims, 'HS256');
            $user = null;
        }

        if ($req->getMethod() === 'GET' && trim($req->getUrl()->getRealPath(), '/') === $this->path && $req->isAjax()) {
            return (new Response(200))
                ->setBody(json_encode([ 'user' => !$user ? false : true ], JSON_THROW_ON_ERROR))
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-User', $user !== null ? $user->getID() : ($prev !== null ? $prev->getID() : ''))
                ->withHeader(
                    'X-Username',
                    $user !== null ?
                        $user->name . ($user->impersonated ? ' *' : '') :
                        ($prev !== null ? $prev->name : '')
                );
        }

        $userID = $user !== null ? $user->getID() : ($prev !== null ? $prev->getID() : '');
        $userName = $user !== null ?
            $user->name . ($user->impersonated ? ' *' : '') :
            ($prev !== null ? $prev->name : '');

        $req
            ->withAttribute('user', $user)
            ->withAttribute('valid_token', $valid_token)
            ->withAttribute('prev', $prev)
            ->withAttribute('token', $token);
        $res = $next($req)
            ->withHeader('X-User', $userID)
            ->withHeader('X-Username', $userName);
        if ($context) {
            if (strpos($context, 'INVALID') && !$res->hasHeader('X-Log')) {
                $res = $res->withAddedHeader('X-Log', 'Token invalid');
            }
            $res = $res->withAddedHeader('X-Context', HeaderSecurity::filter($context));
        }
        if ($token->hasClaim('SSL_CLIENT_M_SERIAL')) {
            $res = $res->withAddedHeader(
                'X-Context',
                'SSL_CLIENT_M_SERIAL: ' . $token->getClaim('SSL_CLIENT_M_SERIAL')
            );
        }
        if ($token->hasClaim('SSL_CLIENT_M_SERIAL_FILE')) {
            $res = $res->withAddedHeader(
                'X-Context',
                'SSL_CLIENT_M_SERIAL_FILE: ' . $token->getClaim('SSL_CLIENT_M_SERIAL_FILE')
            );
        }

        $claims = $token->getClaims();
        if ($user && !isset($claims['provider']) && !isset($claims['id'])) {
            // looks like a logout
            if ($token->hasClaim('loginid')) {
                $sessions = json_decode($user->get('sessions') ?? '', true) ?? [];
                if (isset($sessions[$token->getClaim('loginid')])) {
                    unset($sessions[$token->getClaim('loginid')]);
                }
                $user->set('sessions', json_encode($sessions));
                $this->usrm->saveUser($user);
            }
        }

        if (
            (!$res->hasHeader('X-User') || !$res->getHeaderLine('X-User')) &&
            isset($claims['provider']) && isset($claims['id'])
        ) {
            try {
                $temp = $this->usrm->getUserByProviderID($claims['provider'], $claims['id'], false);
                if (!($temp instanceof VUser)) {
                    throw new \Exception();
                }
                $res = $res
                    ->withHeader('X-User', $temp->getID())
                    ->withHeader('X-Username', $temp->name);
            } catch (\Exception) {
            }
        }

        unset($claims['iat']);
        unset($claims['exp']);
        if (!isset($initialClaims)) {
            $initialClaims = [];
        }
        unset($initialClaims['iat']);
        unset($initialClaims['exp']);

        $shouldRegenerate = $claims != $initialClaims;
        if (in_array('sess', explode(',', $this->tokenClaims))) {
            $token->setClaim('sess', $claims['sess'] = $session?->id() ?? '');
        }

        // renew when the token changes or at least once a minute
        if (
            !preg_match('(token|basic|bearer|oauth)i', $req->getHeaderLine('Authorization')) &&
            (time() - $token->getClaim('iat', time()) > 300 || $claims != $initialClaims)
        ) {
            if ($claims != $initialClaims && $session && $session->isStarted()) {
                if ($shouldRegenerate) {
                    $session->regenerate(true);
                }
                if (in_array('sess', explode(',', $this->tokenClaims))) {
                    $token->setClaim('sess', $session->id());
                }
            }
            $res = $res->withAddedHeader(
                'Set-Cookie',
                $this->appname . '_TOKEN=' . urlencode(
                    $token
                        ->setIssuedAt(time())
                        ->setExpiration(time() + $this->timeout)
                        ->sign($this->signatureKey)
                        ->toString($this->encryptionKey)
                ) . '; Path=' . $req->getUrl()->getBasePath() . '; HttpOnly; SameSite=Lax' .
                ($req->getUrl()->getScheme() === 'https' ? '; Secure' : '')
            );
        }
        return $res;
    }
}
