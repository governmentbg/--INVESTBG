<?php

declare(strict_types=1);

namespace webadmin\middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\random\Generator;
use vakata\session\SessionInterface;
use vakata\user\User;
use vakata\jwt\JWT;

class CSRF
{
    protected string $signatureKey;
    protected string $encryptionKey;
    /** @var array<string,scalar|null> $claims */
    protected array $claims;
    protected int $timeout;

    /**
     * @param string $signatureKey
     * @param string $encryptionKey
     * @param array<string,scalar|null> $claims
     * @param integer $timeout
     */
    public function __construct(string $signatureKey, string $encryptionKey, array $claims = [], int $timeout = 7200)
    {
        $this->signatureKey = $signatureKey;
        $this->encryptionKey = $encryptionKey;
        $this->claims = $claims;
        $this->timeout = $timeout;
    }

    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        if (preg_match('(token|basic|bearer|oauth)i', $req->getHeaderLine('Authorization'))) {
            return $next($req);
        }

        $sess = $req->getAttribute('session');
        $rand = null;
        $used = [];
        if ($sess instanceof SessionInterface && $sess->isStarted()) {
            if (!$sess->get('_csrf_token_random')) {
                $sess->set('_csrf_token_random', Generator::string());
            }
            $rand = $sess->get('_csrf_token_random');
            $used = $sess->get('_csrf_token_nonces', []);
        }
        $user = $req->getAttribute('user');
        $usid = null;
        if ($sess instanceof User && $user->getID()) {
            $usid = $user->getID();
        }

        // Step 1: origin / referer headers check
        // only check on state-changing methods and if no token is supplied as header and is not AJAX
        if (!$req->isAjax() && in_array($req->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            if (!$req->hasHeader('Origin') && !$req->hasHeader('Referer')) {
                throw new \Exception('CSRF No referer or origin', 400);
            }
            if (
                $req->hasHeader('Origin') &&
                parse_url($req->getHeaderLine('Origin'), PHP_URL_HOST) &&
                parse_url($req->getHeaderLine('Origin'), PHP_URL_HOST) != $req->getUri()->getHost()
            ) {
                throw new \Exception('CSRF Invalid origin', 400);
            }
            if (
                $req->hasHeader('Referer') &&
                parse_url($req->getHeaderLine('Referer'), PHP_URL_HOST) &&
                parse_url($req->getHeaderLine('Referer'), PHP_URL_HOST) != $req->getUri()->getHost()
            ) {
                throw new \Exception('CSRF Invalid referer', 400);
            }
        }

        // Step 2.1: check for csrf token presense and verify
        if (!$req->isAjax()) {
            if (in_array($req->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
                try {
                    $csrfToken = JWT::fromString($req->getPost('_csrf_token', ''), $this->encryptionKey);
                } catch (\Exception $e) {
                    throw new \Exception('CSRF Invalid token', 400);
                }
                $claims = $this->claims;
                if ($rand) {
                    $claims = [ 'rand'  => $rand ];
                }
                if ($usid) {
                    $claims = [ 'usid'  => $usid ];
                }
                if (
                    !$csrfToken->isSigned() ||
                    !$csrfToken->verify($this->signatureKey, 'HS256') ||
                    !$csrfToken->isValid($claims)
                ) {
                    throw new \Exception('CSRF Invalid token data', 403);
                }
                // prevent replay attacks if session is available
                $nonce = $csrfToken->getClaim('nonce');
                if (in_array($nonce, $used)) {
                    throw new \Exception('CSRF token already used', 403);
                }
                $used[] = $nonce;
                $used = array_slice($used, -100);
                if ($sess instanceof SessionInterface) {
                    $sess->del('_csrf_token_nonces');
                    $sess->set('_csrf_token_nonces', $used);
                }
            }
        }

        $res = $next($req);

        // get again
        if (!$sess->get('_csrf_token_random')) {
            $sess->set('_csrf_token_random', Generator::string());
        }
        $rand = $sess->get('_csrf_token_random');
        $used = $sess->get('_csrf_token_nonces', []);

        // Step 2.2: append a token to every html POST form
        if (
            !$res->hasCallback() &&
            (!$res->hasHeader('Content-Type') || strpos($res->getHeaderLine('Content-Type'), 'html') !== false)
        ) {
            $body = (string)$res->getBody();
            $body = preg_replace_callback(
                '(<form[^>]+?method="post"[^>]*>)ui',
                function (array $matches) use ($rand, $usid) {
                    $claims = $this->claims;
                    if ($rand) {
                        $claims = [ 'rand'  => $rand ];
                    }
                    if ($usid) {
                        $claims = [ 'usid'  => $usid ];
                    }
                    $claims['nonce'] = Generator::string(16);

                    $csrfToken = new JWT($claims, 'HS256');
                    $csrfToken = $csrfToken
                        ->setIssuedAt(time())
                        ->setExpiration(time() + $this->timeout)
                        ->sign($this->signatureKey)
                        ->toString($this->encryptionKey);
                    return $matches[0] . '<input type="hidden" name="_csrf_token" value="' . $csrfToken . '" />';
                },
                $body
            );
            $res = $res->setBody($body ?? '');
        }
        return $res;
    }
}
