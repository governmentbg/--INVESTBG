<?php

declare(strict_types=1);

namespace webadmin\middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\authentication\password\PasswordExceptionMustChange;
use vakata\authentication\password\PasswordExceptionTooCommon;
use vakata\authentication\password\PasswordExceptionSamePassword;
use vakata\authentication\password\PasswordExceptionEasyPassword;
use vakata\authentication\password\PasswordExceptionShortPassword;
use vakata\authentication\password\PasswordExceptionMatchesUsername;
use vakata\authentication\password\PasswordExceptionContainsUsername;
use vakata\authentication\password\PasswordException;
use vakata\authentication\oauth\OAuthExceptionRedirect;
use vakata\authentication\AuthenticationException;
use vakata\authentication\AuthenticationExceptionNotSupported;
use vakata\authentication\password\PasswordDatabase;
use vakata\views\Views;
use vakata\authentication\Manager;
use vakata\session\SessionInterface;

class Auth
{
    protected Manager $auth;
    protected string $path;
    protected Views $view;
    /** @var string[] $links */
    protected array $links;
    protected bool $basic;
    protected bool $logoutDestroysSession;

    /**
     * @param Manager $auth
     * @param Views $view
     * @param string $path
     * @param array<string> $links
     * @param boolean $basic
     * @param boolean $logoutDestroysSession
     */
    public function __construct(
        Manager $auth,
        Views $view,
        string $path,
        array $links = [],
        bool $basic = false,
        bool $logoutDestroysSession = true
    ) {
        $this->auth = $auth;
        $this->path = $path;
        $this->view = $view;
        $this->links = $links;
        $this->basic = $basic;
        $this->logoutDestroysSession = $logoutDestroysSession;
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

        // basic auth
        if (!$this->basic && strpos(strtolower(trim($req->getHeaderLine('Authorization'))), 'basic') === 0) {
            return (new Response(400))->setBody('Please use tokens, basic auth is disabled');
        }

        // if the path is not LOGIN
        if (trim($url->getRealPath(), '/') !== $this->path) {
            // if there is no user and no OAuth error param - try any login methods
            if (!$user && !$req->getQuery('error') && !$req->getPost('error')) {
                try {
                    $pbody = $req->getParsedBody();
                    if (!is_array($pbody)) {
                        $pbody = [];
                    }
                    $claims = $this->auth->authenticate(
                        array_merge(
                            $req->getQueryParams(),
                            $pbody,
                            [ 'url' => $req->getUrl()->self(true) ]
                        )
                    )->toArray();
                    $token->setClaims($claims);
                    return (new Response(303))->withHeader('Location', $req->getUrl()->linkTo(''));
                } catch (OAuthExceptionRedirect $e) {
                    return (new Response(303))->withHeader('Location', $e->getMessage());
                } catch (AuthenticationExceptionNotSupported) {
                } catch (AuthenticationException $e) {
                    return (new Response(303))
                        ->withHeader('X-Log', 'Invalid login')
                        ->withHeader('Location', $req->getUrl()->linkTo($this->path, [ 'error' => 'wrong']));
                } catch (\Exception) {
                }
            }
            // no supported auth methods were found
            if (!$user) {
                // there is no user - save the requested location in session and redirect to LOGIN (or send 403)
                $sess = $req->getAttribute('session');
                if (
                    $sess !== null &&
                    !in_array($url->getSegment(0), [$this->path]) &&
                    trim($url->getRealPath(), '/') !== '' &&
                    $req->getMethod() === 'GET' &&
                    !$req->isAjax()
                ) {
                    $sess->set('_LOGIN_REDIRECT', (string)$url);
                }
                // if there was an Auth header - it obviously failed - send 403
                if (preg_match('(token|basic|bearer|oauth)i', $req->getHeaderLine('Authorization'))) {
                    return (new Response())->withStatus(403);
                }
                // redirect to LOGIN
                $res = (new Response(303))
                    ->withHeader(
                        'Location',
                        $url->linkTo($this->path) .
                        ($req->getAttribute('prev') !== null ?
                            '?error=enter' :
                            ($req->getAttribute('valid_token') !== null ? '?error=missing' : '')
                        )
                    );
                if ($req->getAttribute('prev') !== null) {
                    $res = $res->withHeader('X-Log', 'Session timeout');
                }
                return $res;
            }
            // we are not on the login screen and there is a valid USER
            if (!$token->getClaim('impersonate') && $user->get('disabled')) {
                // prevent disabled users from continuing
                return preg_match('(token|basic|bearer|oauth)i', $req->getHeaderLine('Authorization')) ?
                    (new Response())->withStatus(403) :
                    (new Response())->withHeader('Location', $url->linkTo($this->path, ['error' => 'disabled']));
            }
            $sessions = json_decode($user->get('sessions') ?? '', true) ?? [];
            if (
                $token->hasClaim('loginid') &&
                !isset($sessions[$token->getClaim('loginid')]) &&
                !$user->get('impersonated')
            ) {
                // prevent killed sessions
                return preg_match('(token|basic|bearer|oauth)i', $req->getHeaderLine('Authorization')) ?
                    (new Response())->withStatus(403) :
                    (new Response())
                        ->withStatus(303)
                        ->withHeader('Location', $url->linkTo($this->path, ['error' => 'killed']));
            }
            // redirect user back to original target
            $sess = $req->getAttribute('session');
            if ($sess !== null) {
                $redirect = $sess->get('_LOGIN_REDIRECT');
                if ($redirect) {
                    $sess->del('_LOGIN_REDIRECT');
                    return (new Response(303))->withHeader('Location', $url->linkTo(trim($redirect, '/')));
                }
            }
            return $next($req);
        }

        // if the PATH is LOGIN and accessed using GET - render login form
        if ($req->getMethod() === 'GET' || $req->getMethod() === 'HEAD') {
            if ($user && $token->getClaim('impersonate')) {
                $token->setClaim('impersonate', null);
                return (new Response(303))
                    ->withHeader('X-Log', 'Logout')
                    ->withHeader('Location', $url->linkTo(''));
            }
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
                'missing' => 'common.login.missing',
                'logout' => 'common.login.logout'
            ];
            $message = $messages[$req->getQueryParams()['error'] ?? ''] ?? null;
            $res = (new Response());
            $token->setClaim('provider', null);
            $token->setClaim('id', null);
            if ($user) {
                $sess = $req->getAttribute('session');
                if ($this->logoutDestroysSession && $sess !== null && $sess instanceof SessionInterface) {
                    $sess->destroy();
                }
                if ($message === null) {
                    $message = 'common.login.logout';
                    $res = $res->withHeader('X-Log', 'Logout');
                }
                return $res->withStatus(303)->withHeader(
                    'Location',
                    (string)$req->getUrl()->get($this->path, ['error' => array_search($message, $messages)])
                );
            }
            $res = $res->setBody(
                $this->view->render(
                    'webadmin::login/login',
                    [
                        'error'  => $message,
                        'auth'   => $this->auth->getProviders(),
                        'token'  => false,
                        'links'  => $this->links,
                        'change' => in_array(
                            $req->getQueryParams()['error'] ?? '',
                            [ 'change', 'match', 'common', 'same', 'easy', 'short', 'username']
                        )
                    ]
                )
            );
            return $res;
        } else {
            // if the PATH is LOGIN and accessed using POST - process passed data
            $token->setClaim('provider', null);
            $token->setClaim('id', null);
            try {
                $pbody = $req->getParsedBody();
                if (!is_array($pbody)) {
                    $pbody = [];
                }
                $claim = $this->auth->authenticate(
                    array_merge(
                        $req->getQueryParams(),
                        $pbody,
                        [ 'url' => $req->getUrl()->self(true) ],
                    )
                );
                $token->setClaims($claim->toArray());
                return (new Response(303))->withHeader('Location', $url->linkTo(''));
            } catch (PasswordExceptionMustChange $e) {
                $post = (array)($req->getParsedBody() ?? []);
                $username  = $post['username'] ?? null;
                $password1 = $post['password1'] ?? null;
                $password2 = $post['password2'] ?? null;
                if ($username !== null && $password1 && $password2) {
                    if ($password1 !== $password2) {
                        return (new Response(303))
                            ->withHeader('X-Log', 'Passwords do not match')
                            ->withHeader('Location', $url->linkTo($this->path, [ 'error' => 'match']));
                    }
                    foreach ($this->auth->getProviders() as $a) {
                        if ($a instanceof PasswordDatabase) {
                            try {
                                $a->changePassword($username, $password1);
                            } catch (PasswordExceptionTooCommon $e) {
                                return (new Response(303))
                                    ->withHeader('X-Log', 'Password too common')
                                    ->withHeader('Location', $url->linkTo($this->path, [ 'error' => 'common']));
                            } catch (PasswordExceptionSamePassword $e) {
                                return (new Response(303))
                                    ->withHeader('X-Log', 'New password is the same as the old one')
                                    ->withHeader('Location', $url->linkTo($this->path, [ 'error' => 'same']));
                            } catch (PasswordExceptionEasyPassword $e) {
                                return (new Response(303))
                                    ->withHeader('X-Log', 'Password too easy')
                                    ->withHeader('Location', $url->linkTo($this->path, [ 'error' => 'easy']));
                            } catch (PasswordExceptionShortPassword $e) {
                                return (new Response(303))
                                    ->withHeader('X-Log', 'Password is too short')
                                    ->withHeader('Location', $url->linkTo($this->path, [ 'error' => 'short']));
                            } catch (PasswordExceptionMatchesUsername $e) {
                                return (new Response(303))
                                    ->withHeader('X-Log', 'Password contains username')
                                    ->withHeader(
                                        'Location',
                                        $url->linkTo($this->path, [ 'error' => 'username'])
                                    );
                            } catch (PasswordExceptionContainsUsername $e) {
                                return (new Response(303))
                                    ->withHeader('X-Log', 'Password contains username')
                                    ->withHeader(
                                        'Location',
                                        $url->linkTo($this->path, [ 'error' => 'username'])
                                    );
                            } catch (PasswordException $e) {
                                return (new Response(303))
                                    ->withHeader('X-Log', 'Try again')
                                    ->withHeader(
                                        'Location',
                                        $url->linkTo($this->path, [ 'error' => 'tryagain'])
                                    );
                            }

                            $claim = $a->authenticate([
                                'username' => $username,
                                'password' => $password1
                            ]);
                            $token->setClaims($claim->toArray());
                            return (new Response(303))->withHeader('Location', $url->linkTo(''));
                        }
                    }
                }
                return (new Response(303))
                    ->withHeader('X-Log', 'Must change password')
                    ->withHeader('Location', $url->linkTo($this->path, [ 'error' => 'change']));
            } catch (AuthenticationException $e) {
                return (new Response(303))
                    ->withHeader('X-Log', 'Invalid username or password')
                    ->withHeader('Location', $url->linkTo($this->path, [ 'error' => 'wrong']));
            } catch (\Exception $e) {
                return (new Response(303))
                    ->withHeader('X-Log', 'Try again')
                    ->withHeader('Location', $url->linkTo($this->path, [ 'error' => 'tryagain']));
            }
        }
    }
}
