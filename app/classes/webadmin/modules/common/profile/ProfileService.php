<?php

declare(strict_types=1);

namespace webadmin\modules\common\profile;

use vakata\database\DBInterface;
use vakata\user\UserManagementInterface;
use vakata\user\User;
use vakata\user\Provider;
use vakata\authentication\totp\TOTP;
use vakata\authentication\password\PasswordDatabase;
use vakata\authentication\password\PasswordExceptionTooCommon;
use vakata\authentication\password\PasswordExceptionSamePassword;
use vakata\authentication\password\PasswordExceptionEasyPassword;
use vakata\authentication\password\PasswordExceptionShortPassword;
use vakata\authentication\password\PasswordExceptionMatchesUsername;
use vakata\authentication\password\PasswordExceptionContainsUsername;
use vakata\authentication\password\PasswordException;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use vakata\config\Config;
use vakata\jwt\JWT;
use vakata\random\Generator as random;

class ProfileService
{
    protected DBInterface $dbc;
    protected User $user;
    protected UserManagementInterface $usrm;
    protected ?PasswordDatabase $auth;
    protected Config $config;

    public function __construct(
        DBInterface $dbc,
        User $user,
        UserManagementInterface $usrm,
        Config $config,
        ?PasswordDatabase $auth = null
    ) {
        $this->dbc = $dbc;
        $this->user = $user;
        $this->usrm = $usrm;
        $this->auth = $auth;
        $this->config = $config;
    }
    protected function getProvider(string $name): array
    {
        return array_filter($this->user->getProviders(), function ($provider) use ($name) {
            return $provider->getProvider() === $name && $provider->enabled();
        });
    }
    public function getKnownDevices(): array
    {
        return $this->getProvider('TFADeviceToken');
    }
    public function getSessions(): array
    {
        $temp = json_decode($this->user->get('sessions') ?? '', true) ?? [];
        return is_array($temp) ? $temp : [];
    }
    public function getCertificates(): array
    {
        return $this->getProvider('Certificate');
    }
    public function getCodes(): array
    {
        return $this->getProvider('RecoveryCode');
    }
    public function getTOTPs(): array
    {
        return $this->getProvider('TOTP');
    }
    public function forceTFA(): bool
    {
        return $this->config->get('FORCE_TFA') &&
            !count($this->getTOTPs()) &&
            !count($this->getCertificates()) &&
            !count($this->getCodes());
    }
    public function getTokens(): array
    {
        return array_map(function (Provider $v) {
            $v->setData((new JWT([
                    'iss'      => $this->config->get('APPNAME_CLEAN'),
                    'provider' => 'Token',
                    'id'       => $v->getID(),
                    'name'     => $this->user->get('name'),
                    'mail'     => $this->user->get('mail'),
                    'tfa'      => 'OK',
                    'iat'      => $v->getCreated()
                ]))
                ->sign($this->config->get('SIGNATUREKEY'))
                ->toString($this->config->get('ENCRYPTIONKEY')));
            return $v;
        }, $this->getProvider('Token'));
    }
    public function getPassword(): ?Provider
    {
        return array_values($this->getProvider('PasswordDatabase'))[0] ??
            array_values($this->getProvider('Password'))[0] ??
            null;
    }
    public function getTOTP(?string $secret = null): array
    {
        if (!$secret) {
            $secret = TOTP::generateSecret();
        }
        $totp = new TOTP($secret, [ 'title' => $this->config->get('APPNAME') ]);
        return [
            'secret' => $secret,
            'qr' => (new QRCode(
                new QROptions([
                    'version'      => 5,
                    'outputType'   => QRCode::OUTPUT_MARKUP_HTML,
                    'eccLevel'     => QRCode::ECC_L,
                ])
            ))->render($totp->getSecretUri())
        ];
    }
    public function getLocales(): array
    {
        return explode(',', $this->config->get('LANGUAGES'));
    }
    public function getUserData(): array
    {
        return [
            'name'   => $this->user->get('name'),
            'mail'   => $this->user->get('mail'),
            'avatar' => $this->user->get('avatar'),
            'tfa'    => $this->user->get('tfa') || $this->config->get('FORCE_TFA'),
            'tfaF'   => $this->config->get('FORCE_TFA')
        ];
    }
    public function setUserData(array $data): void
    {
        foreach ($data as $k => $v) {
            switch ($k) {
                case 'name':
                    if (!strlen($v)) {
                        throw new ProfileException('profile.postdata.invalidname');
                    }
                    $this->user->set('name', $v);
                    break;
                case 'mail':
                    if (!strlen($v) || filter_var($v, FILTER_VALIDATE_EMAIL) === false) {
                        throw new ProfileException('profile.postdata.invalidmail');
                    }
                    if (
                        $this->dbc->val(
                            "SELECT usr FROM users WHERE mail = ? AND usr <> ?",
                            [$v, $this->user->getID()]
                        )
                    ) {
                        throw new ProfileException('profile.postdata.usedmail');
                    }
                    $this->user->set('mail', $v);
                    break;
                case 'avatar':
                    $this->user->set('avatar', (int)$v ? (int)$v : null);
                    break;
                case 'avatar_data':
                    $this->user->set('avatar_data', strlen($v) ? $v : null);
                    break;
                case 'tfa':
                    $this->user->set((string)$k, (int)$v);
                    break;
                default:
                    break;
            }
        }
        $this->usrm->saveUser($this->user);
    }
    public function setPassword(string $current, string $password): void
    {
        $provider = $this->getPassword();
        if (!$this->auth || !$provider) {
            throw new ProfileException();
        }
        try {
            $this->auth->authenticate([
                'username' => $provider->getID(),
                'password' => $current
            ]);
        } catch (\Exception $e) {
            throw new ProfileException();
        }
        try {
            $this->auth->changePassword($provider->getID(), $password);
        } catch (PasswordExceptionTooCommon $e) {
            throw new ProfileException('common.login.common');
        } catch (PasswordExceptionSamePassword $e) {
            throw new ProfileException('common.login.same');
        } catch (PasswordExceptionEasyPassword $e) {
            throw new ProfileException('common.login.easy');
        } catch (PasswordExceptionShortPassword $e) {
            throw new ProfileException('common.login.short');
        } catch (PasswordExceptionMatchesUsername $e) {
            throw new ProfileException('common.login.containsusername');
        } catch (PasswordExceptionContainsUsername $e) {
            throw new ProfileException('common.login.containsusername');
        } catch (PasswordException $e) {
            throw new ProfileException('profile.postpassword.meetrequirements');
        }
    }
    protected function addProvider(string $type, string $id, string $name = '', ?string $data = null): void
    {
        $user = null;
        try {
            $user = $this->usrm->getUserByProviderID($type, $id);
        } catch (\Exception) {
        }
        if ($user) {
            throw new ProfileException('profile.providerexists');
        }
        $this->user->addProvider(new Provider($type, $id, mb_substr($name, 0, 500), $data));
        $this->usrm->saveUser($this->user);
    }
    public function deleteProvider(string $type, string $id): void
    {
        foreach ($this->user->getProviders() as $provider) {
            if ($provider->getProvider() === $type && $provider->getID() == $id && $provider->enabled()) {
                $this->user->deleteProvider($provider);
            }
        }
        $this->usrm->saveUser($this->user);
    }
    public function addToken(string $name): void
    {
        $ok = false;
        $token = '';
        do {
            try {
                $token = random::string(64, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
                $this->usrm->getUserByProviderID('Token', $token);
            } catch (\Exception) {
                $ok = true;
            }
        } while (!$ok);
        $this->addProvider('Token', $token, mb_substr($name, 0, 500));
    }
    public function addCertificate(string $certificate, ?string $data = null): void
    {
        $user = null;
        try {
            $user = $this->usrm->getUserByProviderID('Certificate', $certificate);
        } catch (\Exception) {
        }
        if ($user) {
            throw new ProfileException('profile.certificateexists');
        }
        $this->addProvider('Certificate', $certificate, '', $data);
    }
    public function addTotp(string $name, string $code, string $secret): void
    {
        try {
            $provider = $this->getTOTP();
            $totp = new TOTP($secret, [ 'title' => $this->config->get('APPNAME') ]);
            $totp->authenticate([ 'totp' => $code ]);
        } catch (\Exception $e) {
            throw new ProfileException('profile.posttotp.wrongcode');
        }
        $provider = new Provider('TOTP', $secret, mb_substr($name, 0, 500));
        $this->user->addProvider($provider);
        $this->usrm->saveUser($this->user);
    }
    public function enableTfa(): void
    {
        $this->setUserData([ 'tfa' => 1 ]);
    }
    public function disableTfa(): void
    {
        $this->setUserData([ 'tfa' => 0 ]);
    }
    public function generateCodes(): void
    {
        foreach ($this->getCodes() as $provider) {
            $this->user->deleteProvider($provider);
        }
        for ($i = 0; $i < 12; $i++) {
            $ok = false;
            $token = '';
            do {
                try {
                    $token = random::string(12, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
                    $this->usrm->getUserByProviderID('RecoveryCode', $token);
                } catch (\Exception) {
                    $ok = true;
                }
            } while (!$ok);
            $this->addProvider('RecoveryCode', $token);
        }
        $this->usrm->saveUser($this->user);
    }
    public function deleteSessions(array $sessions): void
    {
        $currSessions = json_decode($this->user->get('sessions') ?? '', true) ?? [];
        foreach ($sessions as $session) {
            if (isset($currSessions[$session])) {
                unset($currSessions[$session]);
            }
        }
        $this->user->set('sessions', json_encode($currSessions));
        $this->usrm->saveUser($this->user);
    }
}
