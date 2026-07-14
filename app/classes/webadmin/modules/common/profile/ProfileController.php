<?php

declare(strict_types=1);

namespace webadmin\modules\common\profile;

use vakata\http\Uri as Url;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\views\Views;
use vakata\certificate\Certificate;
use vakata\config\Config;
use vakata\files\FileStorageInterface;
use vakata\jwt\JWT;
use vakata\session\SessionInterface;

class ProfileController
{
    private ProfileService $service;
    protected FileStorageInterface $fs;

    public function __construct(ProfileService $service, FileStorageInterface $fs)
    {
        $this->service = $service;
        $this->fs = $fs;
    }
    public function getIndex(Response $res, Views $views, JWT $token, SessionInterface $sess): Response
    {
        $views->addFolder('profile', __DIR__ . '/views');

        $totp = $this->service->getTOTP($sess->get('_new_totp'));
        $sess->set('_new_totp', $totp['secret']);

        return $res->setBody(
            $views->render(
                'profile::index',
                [
                    'codes'        => $this->service->getCodes(),
                    'totps'        => $this->service->getTOTPs(),
                    'devices'      => $this->service->getKnownDevices(),
                    'sessions'     => $this->service->getSessions(),
                    'certificates' => $this->service->getCertificates(),
                    'tokens'       => $this->service->getTokens(),
                    'password'     => $this->service->getPassword(),
                    'totp'         => $totp,
                    'locales'      => $this->service->getLocales(),
                    'user'         => $this->service->getUserData(),
                    'certificate'  => $token->getClaim('SSL_CLIENT_M_SERIAL'),
                    'forceTFA'     => $this->service->forceTFA()
                ]
            )
        );
    }

    public function postData(Request $req, Response $res, Url $url, SessionInterface $sess): Response
    {
        try {
            $this->service->setUserData($req->getPost());
        } catch (ProfileException $e) {
            $sess->set('error', $e->getMessage());
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $sess->set('success', 'profile.postdata.success');
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postPassword(
        Request $req,
        Response $res,
        Url $url,
        SessionInterface $sess,
        Config $config
    ): Response {
        if (!$req->getPost('old_password')) {
            $sess->set('error', 'profile.postpassword.entercurrent');
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        if (!$req->getPost('new_password1') || !$req->getPost('new_password2')) {
            $sess->set('error', 'profile.postpassword.enternew');
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        if ($req->getPost('new_password1') !== $req->getPost('new_password2')) {
            $sess->set('error', 'profile.postpassword.nomatch');
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        try {
            $this->service->setPassword(
                $req->getPost('old_password'),
                $req->getPost('new_password1')
            );
        } catch (ProfileException $e) {
            if (!$e->getMessage()) {
                return $res->withStatus(303)->withHeader('Location', $url->linkTo($config->get('LOGIN_URL')));
            }
            $sess->set('error', $e->getMessage());
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $sess->set('success', 'profile.postpassword.success');
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postCertificate(Request $req, Response $res, Url $url, SessionInterface $sess): Response
    {
        if ($req->getPost('certificatefile')) {
            $certificate = $this->fs->get($req->getPost('certificatefile'))->content(true);
        } else {
            $certificate = $req->getPost('certificatetext');
        }
        if (!is_string($certificate) || !strlen($certificate)) {
            $sess->set('error', 'profile.nocertificate');
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        try {
            $cert = Certificate::fromString($certificate);
        } catch (\Throwable $e) {
            $sess->set('error', 'profile.invalidcertificate');
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        try {
            $this->service->addCertificate(
                $cert->getSerialNumber() . ' / ' . $cert->getAuthorityKeyIdentifier(),
                $certificate
            );
        } catch (ProfileException $e) {
            $sess->set('error', $e->getMessage());
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $sess->set('success', 'profile.postcertificates.success');
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postCertificates(Request $req, Response $res, Url $url, SessionInterface $sess): Response
    {
        $certificates = $req->getPost('certificates');
        if (!is_array($certificates)) {
            $certificates = [];
        }
        try {
            foreach ($certificates as $certificate) {
                $this->service->deleteProvider('Certificate', $certificate);
            }
            $require = $req->getPost('requireCertificate', '0', 'int');
            $certificates = $this->service->getCertificates();
            if (!count($certificates) && (int)$require) {
                throw new ProfileException('profile.requirecertificate.nocertificates');
            }
            $this->service->setUserData([ 'cert' => $require ]);
        } catch (ProfileException $e) {
            $sess->set('error', $e->getMessage());
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $sess->set('success', 'profile.postcertificates.success');
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postLocale(Request $req, Response $res, Url $url, Config $config): Response
    {
        $locale = $req->getPost('locale');
        $locales = $this->service->getLocales();
        if (is_string($locale) && in_array($locale, $locales)) {
            $res = $res
                ->withCookie(
                    $config->get('APPNAME_CLEAN') . '_LOCALE',
                    $locale,
                    'Path=' . $url->linkTo() . '; Expires=' . date('r', time() + 3 * 365 * 24 * 3600) . '; HttpOnly; ' .
                    'SameSite=Lax' . ($req->getUrl()->getScheme() === 'https' ? '; Secure' : '')
                );
        }
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postTfa(Request $req, Response $res, Url $url, JWT $token, SessionInterface $sess): Response
    {
        try {
            if ((int)$req->getPost('tfa')) {
                $this->service->enableTfa();
                $token->setClaim('tfa', 'OK');
            } else {
                $this->service->disableTfa();
            }
        } catch (ProfileException $e) {
            $sess->set('error', $e->getMessage());
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $sess->set('success', 'profile.posttfa.' . ((int)$req->getPost('tfa') ? 'activated' : 'deactivated'));
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postDevices(Request $req, Response $res, Url $url, SessionInterface $sess): Response
    {
        $devices = $req->getPost('devices');
        if (!is_array($devices)) {
            $devices = [];
        }
        try {
            foreach ($devices as $device) {
                $this->service->deleteProvider('TFADeviceToken', $device);
            }
        } catch (ProfileException $e) {
            $sess->set('error', $e->getMessage());
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $sess->set('success', 'profile.postdevices.success');
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postSessions(Request $req, Response $res, Url $url, SessionInterface $sess): Response
    {
        $sessions = $req->getPost('sessions');
        if (!is_array($sessions)) {
            $sessions = [];
        }
        try {
            $this->service->deleteSessions($sessions);
        } catch (ProfileException $e) {
            $sess->set('error', $e->getMessage());
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $sess->set('success', 'profile.postsessions.success');
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postToken(Request $req, Response $res, Url $url, SessionInterface $sess): Response
    {
        if (!$req->getPost('token')) {
            $sess->set('error', 'profile.posttoken.entername');
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        try {
            $this->service->addToken($req->getPost('token'));
        } catch (ProfileException $e) {
            $sess->set('error', $e->getMessage());
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $sess->set('success', 'profile.posttoken.success');
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postTokens(Request $req, Response $res, Url $url, SessionInterface $sess): Response
    {
        $tokens = $req->getPost('tokens');
        if (!is_array($tokens)) {
            $tokens = [];
        }
        try {
            foreach ($tokens as $token) {
                $this->service->deleteProvider('Token', $token);
            }
        } catch (ProfileException $e) {
            $sess->set('error', $e->getMessage());
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $sess->set('success', 'profile.posttokens.success');
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postTotp(Request $req, Response $res, SessionInterface $sess): Response
    {
        try {
            $this->service->addTotp($req->getPost('name'), $req->getPost('code'), $sess->get('_new_totp'));
            $sess->del('_new_totp');
        } catch (\Throwable $e) {
            return $res->withStatus(400);
        }
        return $res->withStatus(200);
    }
    public function postTotps(Request $req, Response $res, Url $url, SessionInterface $sess): Response
    {
        $keys = $req->getPost('totps');
        if (!is_array($keys)) {
            $keys = [];
        }
        try {
            foreach (array_filter($keys) as $key) {
                $this->service->deleteProvider('TOTP', $key);
            }
        } catch (ProfileException $e) {
            $sess->set('error', $e->getMessage());
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $sess->set('success', 'profile.posttotps.success');
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postCodes(Response $res, Url $url, SessionInterface $sess): Response
    {
        try {
            $this->service->generateCodes();
        } catch (ProfileException $e) {
            $sess->set('error', $e->getMessage());
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        $sess->set('success', 'profile.codes.success');
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
}
