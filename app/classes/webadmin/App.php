<?php

declare(strict_types=1);

namespace webadmin;

use base\App as BaseApp;
use vakata\views\Views;
use base\middleware\ClientIP;
use Closure;
use RuntimeException;
use schema\mappers\DIMapper;
use vakata\authentication\Credentials;
use vakata\authentication\Manager;
use vakata\cache\APCu;
use vakata\cache\CacheInterface;
use vakata\config\Config;
use vakata\migrations\Migrations;
use vakata\cache\Memcached;
use vakata\cache\Libmemcached;
use vakata\cache\Filecache;
use vakata\cache\Libredis;
use vakata\cache\Redis;
use vakata\collection\Collection;
use vakata\database\schema\Entity;
use vakata\http\Request;
use vakata\http\Response;
use vakata\intl\Intl;
use vakata\user\Provider;
use vakata\user\User;
use vakata\user\UserException;
use vakata\user\UserManagementDatabase;
use vakata\user\UserManagementInterface;
use webadmin\modules\common\crud\ConfigCRUDModule;
use webadmin\modules\common\crud\CRUDModuleInterface;
use webadmin\modules\ModuleInterface;
use webadmin\modules\ModulesContainer;
use webadmin\modules\VisualModuleInterface;

class App extends BaseApp
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
        $dir = realpath(__DIR__ . '/../../../') ?: throw new \RuntimeException();
        return array_merge(
            parent::defaults(),
            [
                'DB_CONFIG'             => false,
                'SESSION_AUTOSTART'     => true,
                'PASSWORDKEY'           => '',
                'STORAGE_INTL_PUBLIC'   => $dir . '/storage/intl/public',
                'STORAGE_KEYS'          => $dir . '/storage/keys',
                'RATELIMIT_REQUESTS'    => 5,
                'RATELIMIT_SECONDS'     => 1,
                'FORCE_TFA'             => false,
                'TFA_REMEMBER'          => true,
                'FULLTEXT'              => false,
                'BASIC_AUTH'            => false,
                'AUTOREGISTER'          => false,
                'PUSH_NOTIFICATIONS'    => false,
                'GROUP_ADMINS'          => '1',
                'GROUP_USERS'           => '2',
                'FEATURE_CMS'           => true,
                'FEATURE_MESSAGING'     => true,
                'FEATURE_HELP'          => true,
                'FEATURE_EKATTE'        => true,
                'LOGIN_URL'             => 'login',
                'HEALTH_URL'            => 'health',
                'CERT_URL'              => 'cert',
                'PUBLIC_URL'            => '',
                "MIDDLEWARE_CERT"              => true,
                "MIDDLEWARE_HEALTH"            => true,
                "MIDDLEWARE_RESTORE"           => false,
                "MIDDLEWARE_REGISTER"          => false,
                "MIDDLEWARE_BASIC"             => false,
                "MIDDLEWARE_AUTH"              => true,
                "MIDDLEWARE_TFA"               => true,
                "MIDDLEWARE_MAINTENANCE"       => false,
                "CACHE_PUBLIC"                 => '',
                "CACHE_PUBLIC_KEY"             => '',
                "STORAGE_CACHE_PUBLIC"         => '',
                "STORAGE_LOG_PUBLIC"           => '',
                "MIDDLEWARE_USER"              => true,
                "MIDDLEWARE_USERDECORATOR"     => true,
                "MIDDLEWARE_PUSHNOTIFICATIONS" => false,
                "MIDDLEWARE_RATELIMIT"         => false,
                'PUBLIC_SIGNATUREKEY'          => '',
                'PUBLIC_ENCRYPTIONKEY'         => '',
                'TOKEN_CLAIMS'                 => '', // 'ip,ua,sess'
            ]
        );
    }
    public function modules(User $user): ModulesContainer
    {
        if ($this->container->has(ModulesContainer::class)) {
            return $this->container->get(ModulesContainer::class);
        }
        $dbc = $this->db();
        $cache = $this->cache();
        $modules = $cache->getSet('modules', function () use ($dbc) {
            /**
             * @var array<string,class-string<ModuleInterface>>
             */
            return $dbc->all(
                "SELECT name, slug, classname, settings FROM modules 
                 WHERE loaded = 1
                 ORDER BY pos, name"
            );
        });
        /** @var array<string,ModuleInterface> */
        $instances = [];
        foreach ($modules as $module) {
            $settings = [];
            $settings['slug'] = $module['slug'];
            $mclss = trim($module['classname'], '\\');
            if (
                $mclss === ConfigCRUDModule::class ||
                in_array(ConfigCRUDModule::class, class_parents($mclss) ?: [])
            ) {
                $config = json_decode($module['settings'], true);
                if (!isset($config['module']['table'])) {
                    throw new RuntimeException('Table not set for ConfigModule');
                }
                $config['module']['name'] = $module['name'];
                $config['module']['slug'] = $module['slug'];
                $settings = [ 'config' => $config ];
            }
            if ($user->hasPermission($module['name'])) {
                $instances[] = $this->di()->instance($module['classname'], $settings);
            }
        }
        /** @psalm-suppress all */
        $mc = new ModulesContainer($instances);
        $this->container->register($mc);
        return $mc;
    }
    public function cachePublic(): ?CacheInterface
    {
        if (
            !$this->config->getString('CACHE_PUBLIC_KEY')
        ) {
            return null;
        }
        $key = $this->config->getString('CACHE_PUBLIC_KEY');
        $loc = $this->config->getString('STORAGE_CACHE_PUBLIC');
        $drv = $this->config->getString('CACHE_PUBLIC');
        if (!$loc) {
            $loc = $this->config->getString('STORAGE_CACHE');
        }
        if (!$drv) {
            $drv = $this->config->getString('CACHE');
        }
        switch ($drv) {
            case 'apcu':
                $cache = new APCu($key);
                break;
            case 'memcache':
            case 'memcached':
                $cache = extension_loaded('memcached') ?
                    new Libmemcached($loc, $key) :
                    new Memcached($loc, $key);
                break;
            case 'redis':
                $cache = extension_loaded('redis') ?
                    new Libredis($loc, $key) :
                    new Redis($loc, $key);
                break;
            default:
                $dir = realpath($loc) ?:
                throw new \RuntimeException();
                $cache = new Filecache($dir, $key);
                break;
        }
        return $cache;
    }
    public function intl(): Intl
    {
        if ($this->container->has(Intl::class)) {
            return $this->container->get(Intl::class);
        }
        $intl = new Intl();
        /** @var array<string,string> $langs */
        $langs = Collection::from(
            /** @psalm-suppress all */
            explode(',', $this->config->getString('LANGUAGES'))
        )
            ->mapKey(function (string $v): string {
                return $v;
            })
            ->map(function (string $v): string {
                return $this->config->getString('STORAGE_INTL') . '/' . $v . '.json';
            })
            ->toArray();
        foreach ($langs as $lang => $file) {
            if ($this->config->getBool('LANGCACHE') && is_file($file . '.php')) {
                /** @psalm-suppress UnresolvableInclude */
                $temp = include $file . '.php';
            } else {
                $temp = @json_decode(file_get_contents($file) ?: '{}', true);
            }
            $intl->addTranslations($lang, $temp ?? []);
        }
        $overrides = $this->cache()->getSet('translations', function () {
            $all = [];
            foreach ($this->db()->all("SELECT * FROM translations") as $row) {
                if (isset($row['v'])) {
                    $all[$row['locale']][$row['k']] = (string)$row['v'];
                }
            }
            return $all;
        });
        foreach ($overrides as $lang => $override) {
            $intl->addTranslations($lang, $override);
        }
        $this->container->register($intl);
        return $intl;
    }
    public function views(): Views
    {
        if (!$this->container->has(Views::class)) {
            $views = parent::views();
            $views->addFolder(
                'webadmin',
                realpath($this->config->getString('BASEDIR') . '/app/views/webadmin') ?: throw new \RuntimeException()
            );
            $views->addData([
                /**
                 * @param array<string> $matches
                 */
                'nbsp' => function (array $matches): string {
                    return (string)str_replace(' ', '&nbsp;', (string)$matches[0]);
                }
            ]);
        }
        return $this->container->get(Views::class);
    }
    public function users(): UserManagementInterface
    {
        if ($this->container->has(UserManagementInterface::class)) {
            return $this->container->get(UserManagementInterface::class);
        }
        $users = new UserManagementDatabase(
            $this->db(),
            [
                'tableUsers'             => 'users',
                'tableProviders'         => 'user_providers',
                'tableGroups'            => 'grps',
                'tablePermissions'       => 'permissions',
                'tableGroupsPermissions' => 'group_permissions',
                'tableUserGroups'        => 'user_groups'
            ],
            [],
            $this->cache()
        );
        $this->container->register($users);
        return $users;
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
                case 'Certificate':
                    $inst = new \vakata\authentication\certificate\Certificate();
                    break;
                case 'CertificateAdvanced':
                    $inst = new \vakata\authentication\certificate\CertificateAdvanced($settings);
                    break;
                case 'LDAP':
                    $inst = new \vakata\authentication\ldap\LDAP(
                        $settings['host'],
                        $settings['base'] ?? null,
                        $settings['user'] ?? null,
                        $settings['pass'] ?? null,
                        array_map('trim', array_filter(explode(',', $settings['attr'] ?? '')))
                    );
                    break;
                case 'SMTP':
                    $inst = new \vakata\authentication\mail\SMTP($settings['host']);
                    break;
                case 'AzureAD':
                    $inst = new \vakata\authentication\oauth\AzureAD(
                        $settings['public'],
                        $settings['private'],
                        $settings['callbackUrl'],
                        $settings['permissions'] ?? null,
                        $settings['tenant'] ?? 'common',
                    );
                    break;
                case 'Facebook':
                    $inst = new \vakata\authentication\oauth\Facebook(
                        $settings['public'],
                        $settings['private'],
                        $settings['callbackUrl'],
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'Github':
                    $inst = new \vakata\authentication\oauth\Github(
                        $settings['public'],
                        $settings['private'],
                        $settings['callbackUrl'],
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'Google':
                    $inst = new \vakata\authentication\oauth\Google(
                        $settings['public'],
                        $settings['private'],
                        $settings['callbackUrl'],
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'LinkedIn':
                    $inst = new \vakata\authentication\oauth\Linkedin(
                        $settings['public'],
                        $settings['private'],
                        $settings['callbackUrl'],
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'Microsoft':
                    $inst = new \vakata\authentication\oauth\Microsoft(
                        $settings['public'],
                        $settings['private'],
                        $settings['callbackUrl'],
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'StampIT':
                    $inst = new \vakata\authentication\oauth\StampIT(
                        $settings['public'],
                        $settings['private'],
                        $settings['callbackUrl'],
                        $settings['permissions'] ?? null
                    );
                    break;
                case 'EAuth':
                    $inst = new \vakata\authentication\saml\EAuth(
                        $settings['providerID'],
                        $settings['serviceID'],
                        $settings['callbackUrl'],
                        $settings['metaURL'],
                        $settings['privatePEM'],
                        $settings['certificatePEM'],
                        $settings['remotePEM']
                    );
                    break;
                case 'AppleID':
                    $inst = new \vakata\authentication\oauth\AppleID(
                        $settings['teamID'],
                        $settings['keyID'],
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
            if ($this->config->getBool('AUTOREGISTER')) {
                try {
                    $usrm->getUserByProviderID($credentials->getProvider(), $credentials->getID());
                } catch (UserException $e) {
                    $user = new \vakata\user\User(
                        '',
                        [
                            'name' => $credentials->get('name', ''),
                            'mail' => $credentials->get('mail', '')
                        ]
                    );
                    $user->addGroup($usrm->getGroup($this->config->getString('GROUP_USERS')));
                    $user->addProvider(new Provider($credentials->getProvider(), $credentials->getID()));
                    $usrm->saveUser($user);
                }
            }
            if (
                $dbc->one(
                    "SELECT 1 FROM user_providers WHERE provider = ? AND id = ? AND disabled = 0",
                    [ $credentials->getProvider(), $credentials->getID() ]
                )
            ) {
                $dbc->query(
                    "UPDATE user_providers SET used = ?, details = ? WHERE provider = ? AND id = ? AND disabled = 0",
                    [
                        date('Y-m-d H:i:s'),
                        json_encode($credentials->getData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        $credentials->getProvider(),
                        $credentials->getID()
                    ]
                );
            } else {
                if (
                    !$dbc->one(
                        "SELECT 1 FROM user_pending WHERE provider = ? AND id = ?",
                        [ $credentials->getProvider(), $credentials->getID() ]
                    )
                ) {
                    $dbc->query(
                        "INSERT INTO user_pending (provider, id, name, mail, created, details) VALUES (??)",
                        [
                            $credentials->getProvider(),
                            $credentials->getID(),
                            $credentials->get('name', ''),
                            $credentials->get('mail', ''),
                            date('Y-m-d H:i:s'),
                            json_encode(
                                $credentials->getData(),
                                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                            )
                        ]
                    );
                }
            }
            return new Credentials(
                $credentials->getProvider(),
                $credentials->getID(),
                array_filter([
                    'name' => $credentials->get('name', null),
                    'mail' => $credentials->get('mail', null)
                ])
            );
        });
        $this->container->register($auth);
        return $auth;
    }
    public function journal(string $message, array $context = []): void
    {
        $this->db(true)->table('log_system')->insert(array_merge(
            $context,
            [
                'message' => $message,
                'lvl' => 'info',
                'context' => json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'created' => date('Y-m-d H:i:s'),
                'usr' => $this->container->has(User::class) ?
                    $this->container->get(User::class)->getID() :
                    null
            ]
        ));
    }
    public function migrations(bool $test = false): Migrations
    {
        return new Migrations(
            $this->db(),
            $this->config->getString('STORAGE_DATABASE') . '/' . $this->db()->driverName(),
            array_filter([
                'base',
                'webadmin/_core',
                $this->config->getBool('FEATURE_CMS')       ? 'webadmin/cms' : null,
                $this->config->getBool('FEATURE_HELP')      ? 'webadmin/help' : null,
                $this->config->getBool('FEATURE_MESSAGING') ? 'webadmin/messaging' : null,
                $this->config->getBool('FEATURE_EKATTE')    ? 'webadmin/ekatte' : null,
                $this->config->getString('APPNAME'),
                $test ? 'test' : ''
            ])
        );
    }

    public function middleware(string $class): callable
    {
        switch ($class) {
            case \webadmin\middleware\Cert::class:
                return new \webadmin\middleware\Cert(
                    $this->config->getString('CERT_URL'),
                    $this->config->getString('STORAGE_CERTIFICATES')
                );
            case \webadmin\middleware\Health::class:
                return new \webadmin\middleware\Health(
                    $this->config,
                    $this->db(true),
                    $this->cache(),
                    $this->config->getString('HEALTH_URL')
                );
            case \webadmin\middleware\Logger::class:
                $login = $this->config->getString('LOGIN_URL');
                return new \webadmin\middleware\Logger(
                    $this->logger(),
                    $this->db(true),
                    ['csp-report', 'ect-report', 'xss-report'],
                    [ $login, 'profile', $login . '/forgot', $login . '/register' ],
                    $this->config->getString('STORAGE_REQ'),
                    [],
                    $this->config->getString('STORAGE_CERTIFICATES'),
                    $this->config->getBool('DEBUG')
                );
            case \webadmin\middleware\User::class:
                return new \webadmin\middleware\User(
                    $this->config->getString('SIGNATUREKEY'),
                    $this->config->getString('ENCRYPTIONKEY'),
                    $this->config->getString('APPNAME_CLEAN'),
                    $this->users(),
                    $this->config->getInt('SESSION_TIMEOUT'),
                    $this->config->getString('LOGIN_URL'),
                    false,
                    $this->config->getString('TOKEN_CLAIMS')
                );
            case \webadmin\middleware\UserDecorator::class:
                $dbc = $this->db();
                $cache = $this->cache();
                return new \webadmin\middleware\UserDecorator(
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
            case \webadmin\middleware\PushNotifications::class: // if PUSH_NOTIFICATIONS
                return new \webadmin\middleware\PushNotifications($this->db());
            case \webadmin\middleware\Ratelimit::class: // if RATELIMIT_REQUESTS & RATELIMIT_SECONDS
                return new \webadmin\middleware\Ratelimit(
                    $this->cache(),
                    $this->config->getInt('RATELIMIT_REQUESTS'),
                    $this->config->getInt('RATELIMIT_SECONDS')
                );
            case \webadmin\middleware\Restore::class: // if FORGOT_PASSWORD
                return new \webadmin\middleware\Restore(
                    $this->users(),
                    $this->auth(),
                    $this->views(),
                    $this->config->getString('LOGIN_URL') . '/forgot',
                    $this->config->getString('SIGNATUREKEY'),
                    $this->config->getString('ENCRYPTIONKEY'),
                    $this->config->getString('APPNAME'),
                    $this->mail(),
                    $this->intl(),
                    $this->config->getInt('FORGOT_PASSWORD')
                );
            case \webadmin\middleware\Register::class: // if REGISTER_PASSWORD
                return new \webadmin\middleware\Register(
                    $this->users(),
                    $this->auth(),
                    $this->views(),
                    $this->config->getString('LOGIN_URL') . '/register',
                    $this->config->getString('SIGNATUREKEY'),
                    $this->config->getString('ENCRYPTIONKEY'),
                    $this->config->getString('APPNAME'),
                    $this->mail(),
                    $this->intl(),
                    $this->config->getInt('REGISTER_PASSWORD'),
                    [ $this->config->getString('GROUP_USERS') ]
                );
            case \webadmin\middleware\Basic::class: // if BASIC_AUTH
                return new \webadmin\middleware\Basic(
                    $this->auth(),
                    $this->users(),
                    $this->views(),
                    $this->config->getString('LOGIN_URL'),
                    false
                );
            case \webadmin\middleware\Auth::class:
                return new \webadmin\middleware\Auth(
                    $this->auth(),
                    $this->views(),
                    $this->config->getString('LOGIN_URL'),
                    array_filter([
                        $this->config->getString('LOGIN_URL') . '/forgot' =>
                            $this->config->getInt('FORGOT_PASSWORD') ?
                                'common.login.forgot' :
                                null,
                        $this->config->getString('LOGIN_URL') . '/register' =>
                            $this->config->getInt('REGISTER_PASSWORD') ?
                                'common.login.register' :
                                null
                    ]),
                    $this->config->getBool('BASIC_AUTH')
                );
            case \webadmin\middleware\TFA::class:
                return new \webadmin\middleware\TFA(
                    $this->users(),
                    $this->views(),
                    $this->config->getString('APPNAME_CLEAN'),
                    $this->config->getString('LOGIN_URL') . '/tfa',
                    $this->config->getBool('FORCE_TFA'),
                    $this->config->getBool('TFA_REMEMBER')
                );
            case \webadmin\middleware\Maintenance::class: // if MAINTENANCE
                return new \webadmin\middleware\Maintenance(
                    $this->config->getString('GROUP_ADMINS'),
                    $this->config->getString('LOGIN_URL')
                );
            case \webadmin\middleware\Uploads::class:
                return new \webadmin\middleware\Uploads(
                    $this->files(),
                    $this->config->getString('UPLOAD_URL'),
                    $this->config->getString('STORAGE_TMP'),
                    $this->config->getString('SENDFILE'),
                    $this->config->getInt('MAX_IMAGE_SIZE'),
                    $this->db()
                );
            case \webadmin\middleware\CSRF::class: // if CSRF
                return new \webadmin\middleware\CSRF(
                    $this->config->getString('SIGNATUREKEY'),
                    $this->config->getString('ENCRYPTIONKEY'),
                    [ 'iss'  => $this->config->getString('APPNAME') ],
                    $this->config->getInt('CSRF_TIMEOUT')
                );
            case \webadmin\middleware\Intl::class:
                return new \webadmin\middleware\Intl(
                    $this->intl(),
                    $this->config->getString('APPNAME_CLEAN') . '_LOCALE',
                    $this->config->getBool('TRANSLATIONS') ?
                        Closure::fromCallable(function (string $locale, array $missing) {
                            foreach ($missing as $k) {
                                try {
                                    $this->db()->query(
                                        'INSERT INTO translations (locale, k, v) VALUES (?, ?, NULL)',
                                        [ $locale, $k ]
                                    );
                                } catch (\Throwable) {
                                }
                            }
                        }) :
                        null
                );
            default:
                return parent::middleware($class);
        }
    }
    public function middlewares(): array
    {
        return [
            "MIDDLEWARE_HEALTH" => \webadmin\middleware\Health::class,
            "MIDDLEWARE_GZIP" => \base\middleware\Gzip::class,
            "MIDDLEWARE_LOGGER" => \webadmin\middleware\Logger::class,
            "MIDDLEWARE_CLIENTIP" => \base\middleware\ClientIP::class,
            "MIDDLEWARE_SESSION" => \base\middleware\Session::class,
            "MIDDLEWARE_USER" => \webadmin\middleware\User::class,
            "MIDDLEWARE_CERT" => \webadmin\middleware\Cert::class,
            "MIDDLEWARE_MINIFY" => \base\middleware\Minify::class,
            "MIDDLEWARE_FIXER" => \base\middleware\Fixer::class,
            "MIDDLEWARE_USERDECORATOR" => \webadmin\middleware\UserDecorator::class,
            "MIDDLEWARE_PUSHNOTIFICATIONS" => \webadmin\middleware\PushNotifications::class,
            "MIDDLEWARE_HTTPS" => \base\middleware\HTTPS::class,
            "MIDDLEWARE_OWASP" => \base\middleware\OWASP::class,
            "MIDDLEWARE_CSP" => \base\middleware\CSP::class,
            "MIDDLEWARE_FP" => \base\middleware\FP::class,
            "MIDDLEWARE_PP" => \base\middleware\PP::class,
            "MIDDLEWARE_RATELIMIT" => \webadmin\middleware\Ratelimit::class,
            "MIDDLEWARE_IDS" => \base\middleware\IDS::class,
            "MIDDLEWARE_CSRF" => \webadmin\middleware\CSRF::class,
            "MIDDLEWARE_CORS" => \base\middleware\CORS::class,
            "MIDDLEWARE_INTL" => \webadmin\middleware\Intl::class,
            "MIDDLEWARE_RESTORE" => \webadmin\middleware\Restore::class,
            "MIDDLEWARE_REGISTER" => \webadmin\middleware\Register::class,
            "MIDDLEWARE_BASIC" => \webadmin\middleware\Basic::class,
            "MIDDLEWARE_AUTH" => \webadmin\middleware\Auth::class,
            "MIDDLEWARE_TFA" => \webadmin\middleware\TFA::class,
            "MIDDLEWARE_MAINTENANCE" => \webadmin\middleware\Maintenance::class,
            "MIDDLEWARE_TRANSACTION" => \base\middleware\Transaction::class,
            "MIDDLEWARE_UPLOADS" => \webadmin\middleware\Uploads::class
        ];
    }
    protected function mappers(): void
    {
        $dbc = $this->db(true);
        $schema = $dbc->getSchema();
        $dbc->clearMappers();

        $tables = [];
        $pivots = [];
        foreach ($schema->getTables() as $table) {
            $tables[] = $table->getName();
            foreach ($table->getRelations() as $relation) {
                $pivots[] = $relation->pivot?->getName();
            }
        }
        $tables = array_unique(array_filter($tables));
        $pivots = array_unique(array_filter($pivots));
        foreach ($tables as $table) {
            if (in_array($table, $pivots)) {
                continue;
            }
            /** @var class-string<\vakata\database\schema\Entity> $clss */
            $clss = 'schema\\' .
                implode('', array_map('ucfirst', array_filter(explode('_', $table)))) . "Entity";
            if (!class_exists($clss)) {
                $clss = Entity::class;
            }
            $dbc->setMapper(
                $table,
                new DIMapper($this->di(), $dbc, $table, $clss),
                $clss
            );
        }
    }
    public function core(Request $req): Response
    {
        $di = $this->di();
        $dbc = $this->db(true);
        $usrm = $this->users();
        $auth = $this->auth();
        $views = $this->views();
        $url = $req->getUrl();
        /** @var \vakata\user\User $user */
        $user = $req->getAttribute('user');
        /** @var \vakata\jwt\JWT $token */
        $token = $req->getAttribute('token');

        // di and views
        $di->register($req);
        $di->register($user);
        $di->register($token);
        $di->register($this);
        foreach ($auth->getProviders() as $method) {
            $di->register($method, false);
        }
        $sess = $req->getAttribute('session');
        if ($sess !== null) {
            $views->addData(['session' => $sess]);
        }
        $views->addData(['req' => $req]);

        $this->mappers();

        $modules = $this->modules($user);
        /**
         * @var array<string,VisualModuleInterface>
         */
        $visual = [];
        foreach ($modules as $module) {
            if ($module instanceof VisualModuleInterface) {
                $visual[$module->getName()] = $module;
            }
        }
        if ($this->config->getBool('FORCE_TFA') && !$token->getClaim('tfa')) {
            if (!isset($visual['profile'])) {
                throw new RuntimeException('Invalid configuration of TFA and profiles');
            }
            $visual = [
                'profile' => $visual['profile']
            ];
        }
        if (!count($visual)) {
            throw new RuntimeException('No allowed modules');
        }

        // add the CRUD views to the views registry
        $views->addFolder(
            'crud',
            $this->config->getString('BASEDIR') . '/app/classes/webadmin/modules/common/crud/views'
        );
        // open the first ALLOWED module by default
        $slug = $url->getSegment(0, $visual[array_keys($visual)[0]]->getSlug());
        // hit the index method if none is specified
        $segment = $url->getSegment(1, 'index');
        $views->addData(['modules' => $visual ]);
        $views->addData(['app' => $di ]);
        $views->addData(['usrm' => $usrm ]);
        $views->addData(['user' => $user ]);
        if ($this->config->getBool('FEATURE_HELP')) {
            $views->addData([
                'helper' => (new \webadmin\modules\common\help\HelpService($dbc))->get($url->getRealPath())
            ]);
        }
        $active = null;
        foreach ($visual as $module) {
            if ($module->getSlug() === $slug) {
                $active = $module;
            }
            if (
                $module instanceof CRUDModuleInterface &&
                $module->getViews()
            ) {
                /** @psalm-suppress all */
                $views->addFolder($module->getName(), $module->getViews());
            }
        }
        $views->addData(['module' => $active ]);
        // try to run a registered module based on the first two parts of the path
        if (!isset($active)) {
            throw new \Exception('Controller not found', 404);
        }

        return $active->process($req);
    }
    /**
     * @param array{title:string,body:string,tag:string} $data
     * @param array<string,scalar|null> $subscription
     * @param string|null $sender
     * @return void
     */
    public function push(array $data, array $subscription, ?string $sender = null): void
    {
        if (
            !$this->config->getBool('PUSH_NOTIFICATIONS') ||
            !is_file($this->config->getString('STORAGE_KEYS') . '/push_public.txt') ||
            !is_file($this->config->getString('STORAGE_KEYS') . '/push_private.txt') ||
            !is_file($this->config->getString('STORAGE_KEYS') . '/push_private.pem')
        ) {
            throw new \Exception('Push notifications not enabled or not configured');
        }
        (
            new \Minishlink\WebPush\WebPush(["VAPID" => [
                "subject" => $sender,
                "publicKey" => file_get_contents($this->config->getString('STORAGE_KEYS') . "/push_public.txt"),
                "privateKey" => file_get_contents($this->config->getString('STORAGE_KEYS') . "/push_private.txt"),
                "privateKeyPEM" => file_get_contents($this->config->getString('STORAGE_KEYS') . "/push_private.pem")
            ]])
        )->sendOneNotification(\Minishlink\WebPush\Subscription::create($subscription), json_encode($data) ?: '{}');
    }
}
