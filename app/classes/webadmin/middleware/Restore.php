<?php

declare(strict_types=1);

namespace webadmin\middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\views\Views;
use vakata\user\UserManagementInterface;
use vakata\authentication\AuthenticationInterface;
use vakata\authentication\password\PasswordExceptionTooCommon;
use vakata\authentication\password\PasswordExceptionSamePassword;
use vakata\authentication\password\PasswordExceptionEasyPassword;
use vakata\authentication\password\PasswordExceptionShortPassword;
use vakata\authentication\password\PasswordExceptionMatchesUsername;
use vakata\authentication\password\PasswordExceptionContainsUsername;
use vakata\authentication\password\PasswordException;
use vakata\mail\driver\SenderInterface;
use vakata\mail\Mail;
use vakata\intl\Intl;
use vakata\jwt\JWT;
use vakata\random\Generator;
use vakata\authentication\Manager;
use vakata\authentication\password\PasswordDatabase;
use vakata\user\Provider;

class Restore
{
    protected UserManagementInterface $usrm;
    protected string $path;
    protected Views $view;
    protected string $signatureKey;
    protected string $encryptionKey;
    protected string $appname;
    protected int $timeout;
    protected SenderInterface $mailer;
    protected Intl $intl;
    protected ?PasswordDatabase $auth = null;

    public function __construct(
        UserManagementInterface $usrm,
        AuthenticationInterface $auth,
        Views $view,
        string $path,
        string $signatureKey,
        string $encryptionKey,
        string $appname,
        SenderInterface $mailer,
        Intl $intl,
        int $timeout = 7200
    ) {
        $this->usrm = $usrm;
        $this->path = $path;
        $this->view = $view;
        $this->signatureKey = $signatureKey;
        $this->encryptionKey = $encryptionKey;
        $this->appname = $appname;
        $this->intl = $intl;
        $this->mailer = $mailer;
        $this->timeout = $timeout;

        if ($auth instanceof PasswordDatabase) {
            $this->auth = $auth;
        }
        if ($auth instanceof Manager) {
            foreach ($auth->getProviders() as $a) {
                if ($a instanceof PasswordDatabase) {
                    $this->auth = $a;
                }
            }
        }
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $url = $req->getUrl();
        $user = $req->getAttribute('user');
        $token = $req->getAttribute('token');
        $intl = $this->intl;

        if ($user !== null || trim($url->getRealPath(), '/') !== $this->path) {
            return $next($req);
        }
        if (!$this->auth) {
            throw new \Exception('No password provider');
        }

        $hasToken = false;
        $p = [];
        if ($req->getQuery('token')) {
            try {
                $t = JWT::fromString((string)$req->getQuery('token'), $this->encryptionKey);
                $c = [ 'iss' => $this->appname, 'purpose' => 'change' ];
                if (!$t->isSigned() || !$t->verify($this->signatureKey, 'HS256') || !$t->isValid($c)) {
                    return (new Response(303))
                        ->withHeader('Location', $url->linkTo($this->path, ['error' => 'invalid']));
                }
                $c = $t->getClaims();
                $u = $this->usrm->getUserByProviderID($c['provider'], $c['provider_id']);
                $p = array_values(array_filter($u->getProviders(), function ($p) use ($c) {
                    return $p->getProvider() === $c['provider'] && $p->getID() === $c['provider_id'] && $p->enabled();
                }));
                if ((string)$c['user'] !== $u->getID() || !count($p) || $p[0]->getCreated() !== $c['created']) {
                    return (new Response(303))
                        ->withHeader('Location', $url->linkTo($this->path, ['error' => 'invalid']));
                }
                if ($u->get('disabled')) {
                    return (new Response(303))
                        ->withHeader('Location', $url->linkTo($this->path, ['error' => 'disabled']));
                }
                $hasToken = true;
            } catch (\Exception $e) {
                return (new Response(303))
                    ->withHeader('Location', $url->linkTo($this->path, ['error' => 'expired']));
            }
        }

        if ($req->getMethod() === 'GET') {
            $messages = [
                'token' => 'common.login.token',
                'enter' => 'common.login.enter',
                'disabled' => 'common.login.disabled',
                'killed' => 'common.login.killed',
                'change' => 'common.login.change',
                'expired' => 'common.login.expired',
                'match' => 'common.login.match',
                'common' => 'common.login.common',
                'same' => 'common.login.same',
                'easy' => 'common.login.easy',
                'short' => 'common.login.short',
                'username' => 'common.login.containsusername',
                'wrong' => 'common.login.wrong',
                'tryagain' => 'common.login.tryagain',
                'restore_invalid' => 'common.login.restore_invalid'
            ];
            $message = $messages[$req->getQuery('error')] ?? null;
            return (new Response())->setBody(
                $this->view->render(
                    'webadmin::login/forgot',
                    [
                        'sent' => (int)$req->getQuery('sent') > 0,
                        'error' => $message,
                        'change' => $hasToken
                    ]
                )
            );
        }
        if ($req->getMethod() === 'POST') {
            if ($hasToken) {
                $username  = $p[0]->getID();
                $password1 = $req->getPost('password1');
                $password2 = $req->getPost('password2');
                if ($password1 && $password2) {
                    if ($password1 !== $password2) {
                        return (new Response(303))
                            ->withHeader('X-Log', 'Passwords do not match')
                            ->withHeader(
                                'Location',
                                $url->linkTo($this->path, [ 'token' => $req->getQuery('token'), 'error' => 'match'])
                            );
                    }
                    try {
                        $this->auth->changePassword($username, $password1);
                    } catch (PasswordExceptionTooCommon $e) {
                        return (new Response(303))
                            ->withHeader('X-Log', 'Password too common')
                            ->withHeader(
                                'Location',
                                $url->linkTo($this->path, [ 'token' => $req->getQuery('token'), 'error' => 'common'])
                            );
                    } catch (PasswordExceptionSamePassword $e) {
                        return (new Response(303))
                            ->withHeader('X-Log', 'New password is the same as the old one')
                            ->withHeader(
                                'Location',
                                $url->linkTo($this->path, [ 'token' => $req->getQuery('token'), 'error' => 'same'])
                            );
                    } catch (PasswordExceptionEasyPassword $e) {
                        return (new Response(303))
                            ->withHeader('X-Log', 'Password too easy')
                            ->withHeader(
                                'Location',
                                $url->linkTo($this->path, [ 'token' => $req->getQuery('token'), 'error' => 'easy'])
                            );
                    } catch (PasswordExceptionShortPassword $e) {
                        return (new Response(303))
                            ->withHeader('X-Log', 'Password is too short')
                            ->withHeader(
                                'Location',
                                $url->linkTo($this->path, [ 'token' => $req->getQuery('token'), 'error' => 'short'])
                            );
                    } catch (PasswordExceptionMatchesUsername $e) {
                        return (new Response(303))
                            ->withHeader('X-Log', 'Password contains username')
                            ->withHeader(
                                'Location',
                                $url->linkTo($this->path, [ 'token' => $req->getQuery('token'), 'error' => 'username'])
                            );
                    } catch (PasswordExceptionContainsUsername $e) {
                        return (new Response(303))
                            ->withHeader('X-Log', 'Password contains username')
                            ->withHeader(
                                'Location',
                                $url->linkTo($this->path, [ 'token' => $req->getQuery('token'), 'error' => 'username'])
                            );
                    } catch (PasswordException $e) {
                        return (new Response(303))
                            ->withHeader('X-Log', 'Try again')
                            ->withHeader(
                                'Location',
                                $url->linkTo($this->path, [ 'token' => $req->getQuery('token'), 'error' => 'tryagain'])
                            );
                    }

                    $claim = $this->auth->authenticate([
                        'username' => $username,
                        'password' => $password1
                    ]);
                    $token->setClaims($claim->toArray());
                    return (new Response(303))->withHeader('Location', $url->linkTo(''));
                }
            } else {
                $mail = $req->getPost('mail');
                if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                    return (new Response(303))
                        ->withHeader('Location', $url->linkTo($this->path, [ 'error' => 'restore_invalid' ]));
                }
                try {
                    $u = $this->usrm->searchUsers(['mail' => $mail]);
                    if (!count($u)) {
                        throw new \Exception();
                    }
                    $p = [];
                    /** @phpstan-ignore-next-line */
                    $p = array_values(array_filter($u[0]->getProviders(), function (Provider $p) {
                        return $p->getProvider() === 'PasswordDatabase' && $p->enabled();
                    }));
                    if (!count($p)) {
                        throw new \Exception();
                    }
                    $t = new JWT(
                        [
                            'iss'   => $this->appname,
                            'purpose' => 'change',
                            'provider' => 'PasswordDatabase',
                            'provider_id' => $p[0]->getID(),
                            'user' => $u[0]->getID(),
                            'nonce' => Generator::string(),
                            'created' => $p[0]->getCreated()
                        ],
                        'HS256'
                    );
                    $t = $t
                        ->setIssuedAt(time())
                        ->setExpiration(time() + $this->timeout)
                        ->sign($this->signatureKey)
                        ->toString($this->encryptionKey);
                    $host = parse_url($url->linkTo('', [], true), PHP_URL_HOST);
                    if (!is_string($host) || !strlen($host)) {
                        $host = 'local';
                    }
                    $this->mailer->send(
                        (new Mail(
                            'noreply@' .  $host,
                            $intl('common.login.restore_subject'),
                            $intl('common.login.restore_body') .
                            '<br />' .
                            $url->linkTo($this->path, ['token' => $t], true)
                        ))
                            ->setTo($mail)
                    );
                } catch (\Exception) {
                    // ignore any errors - do not give away user info
                }
                return (new Response(303))
                        ->withHeader('Location', $url->linkTo($this->path, [ 'sent' => '1' ]));
            }
        }
        return (new Response(303))
            ->withHeader('Location', $url->linkTo($this->path));
    }
}
