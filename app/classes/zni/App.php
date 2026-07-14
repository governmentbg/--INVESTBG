<?php

declare(strict_types=1);

namespace zni;

use vakata\user\User;
use vakata\http\Request;
use vakata\config\Config;
use vakata\http\Response;
use vakata\user\Provider;
use base\middleware\ClientIP;
use vakata\user\UserException;
use zni\authentication\StampIT;
use webadmin\App as WebadminApp;
use vakata\authentication\Manager;
use vakata\authentication\Credentials;
use vakata\authentication\oauth\OAuthExceptionRedirect;

class App extends WebadminApp
{
    public static function init(): self
    {
        /** @psalm-suppress InvalidArgument */
        return new self(
            (require __DIR__ . '/../../../.env.php') ?? Config::parseEnvFile(__DIR__ . '/../../../.env')
        );
    }

    public function defaults(): array
    {
        return array_merge(
            parent::defaults(),
            [
                'CORRECTION_DAYS'  => 30,
                'CORRECTION_ATTEMPT' => 2,
                'EMPL_REPORT_CORRECTION_DAYS' => 14,
                'FROM_EMAIL' => '',
                'MAX_FILE_SIZE' => 10000000,
                'GROUP_INV_ADMIN' => 3,
                'UNSUPPORTED_DAYS' => 180,
                'MAX_COMPANY_USERS' => 5,
                'YEAR_TO_GENERATE' => 25,
                'MAX_YEAR_TO_BGN' => 2025,
                'CURRENCY_EUR_RATE'   => 1.95583
            ]
        );
    }

    public function core(Request $req): Response
    {
        $this->mail();
        return parent::core($req);
    }

    public function auth(): Manager
    {
        if ($this->container->has(Manager::class)) {
            return $this->container->get(Manager::class);
        }

        $cache = $this->cache();
        $dbc   = $this->db();
        $usrm  = $this->users();

        $providers = $cache->getSet(
            'authproviders',
            function () use ($dbc) {
                return $dbc->all(
                    "SELECT * FROM authentication WHERE disabled = 0 ORDER BY position, authentication"
                );
            },
            3600 * 24
        );

        $auth = new Manager();
        $passwordKey = $this->config->getString('PASSWORDKEY');

        foreach ($providers as $provider) {
            $skip = false;
            $inst = null;
            if (isset($provider['conditions']) && $provider['conditions']) {
                $conditions = json_decode($provider['conditions'], true) ?? [];
                if (isset($conditions['ip']) && is_array($conditions['ip']) && !ClientIP::check($conditions['ip'])) {
                    $skip = true;
                }
            }
            $settings = @json_decode($provider['settings'], true);
            if (!$settings) {
                $settings = [];
            }
            switch ($provider['authenticator']) {
                case 'Password':
                    $inst = new \vakata\authentication\password\PasswordDatabase(
                        $dbc,
                        'user_providers',
                        $settings,
                        [
                            'username' => 'id',
                            'password' => 'data'
                        ],
                        [
                            'provider' => 'PasswordDatabase'
                        ],
                        $passwordKey
                    );
                    break;
                case 'StampIT':
                    $inst = new StampIT(
                        $settings['public'],
                        $settings['private'],
                        $settings['callbackUrl'],
                        $settings['permissions'] ?? null
                    );
                    break;

                default:
                    // unknown authenticator - continue
                    break;
            }
            if ($inst) {
                $auth->addProvider($inst, $skip ? false : true);
            }
        }

        $auth->addCallback(function (Credentials $credentials) use ($dbc, $usrm) {

            if ($credentials->getProvider() !== 'StampIT') {
                return $credentials;
            }

            $providerName = $credentials->getProvider();
            $providerId   = $credentials->getID();

            // check for user
            $checkUser = $dbc->one(
                "SELECT usr FROM user_providers WHERE provider = ? AND id = ?",
                [$providerName, $providerId]
            );

            // allowed groups
            $allowedGroups = [
                $this->config->getString('SUPER_ADMIN'),
                $this->config->getString('ADMIN_MIR'),
                $this->config->getString('CHECKING_MIR'),
                $this->config->getString('RESPONSIBLE_MIR'),
                $this->config->getString('CHECKING_MIR_CONTRACT'),
                $this->config->getString('MASTER_MIR'),
            ];

            // if user exisst
            try {
                $user = $usrm->getUserByProviderID($providerName, $providerId);
            } catch (UserException $e) {
                $user = null;
            }

            // ifuser group exist on allowed
            $hasBypassAccess = false;
            if ($user) {
                foreach ($user->getGroups() as $group) {
                    if (in_array($group->getID(), $allowedGroups, true)) {
                        $hasBypassAccess = true;
                        break;
                    }
                }
            }

            // check user for invite
            $checkInvite = $dbc->one(
                "SELECT company, moderator FROM company_egns WHERE egn = ?",
                [$providerId]
            );

            // if user is not on alowed groups
            if (!$hasBypassAccess) {
                // if user is missing and have invite- register
                if ($checkInvite && !$checkUser && $this->config->getBool('AUTOREGISTER')) {
                    $user = new \vakata\user\User(
                        '',
                        [
                            'name' => $credentials->get('name', ''),
                            'mail' => $credentials->get('mail', '')
                        ]
                    );


                    $user->addGroup(
                        $usrm->getGroup(
                            $this->config->getString('GROUP_USERS')
                        )
                    );

                    // if user is moderator - > add to inv admin group
                    if ((int)$checkInvite['moderator'] === 1) {
                        $user->addGroup(
                            $usrm->getGroup(
                                $this->config->getString('ADMIN_INV')
                            )
                        );
                    }
                    $user->addProvider(
                        new Provider($providerName, $providerId)
                    );

                    $usrm->saveUser($user);
                }

                //     if user miss invite - add to pending
                if (!$checkInvite) {
                    if (
                        !$dbc->one(
                            "SELECT 1 FROM user_pending WHERE provider = ? AND id = ?",
                            [$providerName, $providerId]
                        )
                    ) {
                        $dbc->query(
                            "INSERT INTO user_pending
                        (provider, id, name, mail, created, details)
                     VALUES (??)",
                            [
                                $providerName,
                                $providerId,
                                $credentials->get('name', ''),
                                $credentials->get('mail', ''),
                                date('Y-m-d H:i:s'),
                                json_encode(
                                    $credentials->getData(),
                                    JSON_UNESCAPED_SLASHES
                                        | JSON_UNESCAPED_UNICODE
                                        | JSON_PRETTY_PRINT
                                )
                            ]
                        );
                    }

                    throw new OAuthExceptionRedirect(
                        $this->url()->getBasePath() . 'login?error=missing'
                    );
                }
            }

            if (
                $dbc->one(
                    "SELECT 1 FROM user_providers WHERE provider = ? AND id = ? AND disabled = 0",
                    [$providerName, $providerId]
                )
            ) {
                $dbc->query(
                    "UPDATE user_providers SET used = ?, details = ?
             WHERE provider = ? AND id = ? AND disabled = 0",
                    [
                        date('Y-m-d H:i:s'),
                        json_encode(
                            $credentials->getData(),
                            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                        ),
                        $providerName,
                        $providerId
                    ]
                );
            }

            return new Credentials(
                $providerName,
                $providerId,
                array_filter([
                    'name' => $credentials->get('name', null),
                    'mail' => $credentials->get('mail', null)
                ])
            );
        });

        $this->container->register($auth);
        return $auth;
    }
    public function middleware(string $class): callable
    {
        switch ($class) {
            case \zni\middleware\UserDecorator::class:
                $dbc = $this->db();
                $cache = $this->cache();
                return new \zni\middleware\UserDecorator(
                    $dbc,
                    $this->config->getString('APPNAME_CLEAN') . '_SITE',
                    function (User $user) use ($dbc, $cache) {
                        $user->set(
                            'auth',
                            $cache->getSet(
                                'user-callback-' . $user->getID(),
                                function () use ($dbc, $user) {
                                    return $dbc->all(
                                        "SELECT provider, id, details FROM user_providers
                                        WHERE disabled = 0 AND details IS NOT NULL AND usr = ?",
                                        $user->getID()
                                    );
                                },
                                180
                            )
                        );
                    },
                    $this->cache(),
                    90,
                    $this->config->getBool('FEATURE_MESSAGING'),
                    $this->config->getBool('FEATURE_CMS')
                );
            default:
                return parent::middleware($class);
        }
    }

    public function middlewares(): array
    {
        $middlewares = parent::middlewares();
        $middlewares['MIDDLEWARE_USERDECORATOR'] = \zni\middleware\UserDecorator::class;

        return $middlewares;
    }
}
