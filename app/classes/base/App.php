<?php

declare(strict_types=1);

namespace base;

use base\components\files\Files;
use ErrorException;
use Generator as GlobalGenerator;
use Iterator;
use vakata\views\Views;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Level;
use Monolog\Logger;
use RuntimeException;
use vakata\cache\APCu;
use vakata\cache\CacheInterface;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\DB;
use vakata\migrations\Migrations;
use vakata\events\Dispatcher;
use vakata\events\EventInterface;
use vakata\cache\Memcached;
use vakata\cache\Libmemcached;
use vakata\cache\Filecache;
use vakata\cache\Libredis;
use vakata\cache\Redis;
use vakata\collection\Collection;
use vakata\di\DIContainer;
use vakata\files\FileDatabase;
use vakata\files\FileDatabaseCloud;
use vakata\files\FileDatabaseStorage;
use vakata\files\GCS;
use vakata\files\S3;
use vakata\http\Emitter;
use vakata\http\Request;
use vakata\http\Response;
use vakata\http\Uri;
use vakata\intl\Intl;
use vakata\mail\driver\CallbackSender;
use vakata\mail\driver\FileSender;
use vakata\mail\driver\MailSender;
use vakata\mail\driver\MultiSender;
use vakata\mail\driver\SenderInterface;
use vakata\mail\driver\SMTPSender;
use vakata\mail\MailInterface;
use vakata\session\handler\SessionCache;
use vakata\session\handler\SessionDatabase;
use vakata\session\handler\SessionFile;
use vakata\session\Native;
use vakata\session\Session;
use vakata\session\SessionInterface;

class App
{
    protected Config $config;
    protected DIContainer $container;

    public static function init(): self
    {
        /** @psalm-suppress InvalidArgument */
        return new self(
            (require __DIR__ . '/../../../.env.php') ?? Config::parseEnvFile(__DIR__ . '/../../../.env')
        );
    }

    /**
     * @return array<string,scalar|null>
     */
    public function defaults(): array
    {
        $dir = realpath(__DIR__ . '/../../../') ?: throw new \RuntimeException();
        return [
            'CLI'                   => php_sapi_name() === 'cli',
            'BASEDIR'               => $dir,
            'APPNAME'               => basename($dir),
            'APPNAME_CLEAN'         => strtoupper(preg_replace('([^a-z0-9_]+)', '_', basename($dir)) ?? ''),
            'TIMEZONE'              => 'Europe/Sofia',
            'DATABASE'              => '',
            'CACHE'                 => 'file',
            'CACHE_RESPONSE_TTL'    => 60,
            'CACHE_RESPONSE_MAX_SIZE' => 1048576,
            'STORAGE_CACHE'         => $dir . '/storage/cache',
            'ENVPARSE'              => false,
            'DB_CONFIG'             => true,
            'SIGNATUREKEY'          => 'Place-a-random-signature-key-here',
            'ENCRYPTIONKEY'         => '12345678901234567890123456789012',
            // all keys below can be set in DB
            'LOG'                   => 'file',
            'ENVCACHE'              => false,
            'LANGCACHE'             => false,
            'ASSETCACHE'            => false,
            'DEBUG'                 => false,
            'IDS_LIMIT'             => 5,
            'CSRF_TIMEOUT'          => 7200,
            'SESSION_TIMEOUT'       => 1800,
            'SESSION_AUTOSTART'     => false,
            'SESSION_REGENERATE'    => 300,
            'STORAGE_UPLOADS'       => $dir . '/storage/uploads',
            'STORAGE_SESSION'       => 'PHP',
            'STORAGE_LOG'           => $dir . '/storage/log',
            'STORAGE_TMP'           => $dir . '/storage/tmp',
            'STORAGE_INTL'          => $dir . '/storage/intl',
            'STORAGE_DATABASE'      => $dir . '/storage/database',
            'STORAGE_MAIL'          => $dir . '/storage/mail',
            'STORAGE_REQ'           => '', // $dir . '/storage/req',
            'STORAGE_CERTIFICATES'  => $dir . '/storage/certificates',
            'STORAGE_VERSIONS'      => $dir . '/storage/versions',
            'SMTP_CONNECTION'       => '',
            'SMTP_USER'             => '',
            'SMTP_PASSWORD'         => '',
            'MAILQUEUE'             => false,
            'FORGOT_PASSWORD'       => 0,
            'REGISTER_PASSWORD'     => 0,
            'TRANSLATIONS'          => true,
            'STATUS_CHECKER_USER'   => '',
            'STATUS_CHECKER_PASS'   => '',
            'UPLOAD_URL'            => 'upload',
            'LANGUAGES'             => 'bg',
            'SENDFILE'              => '',
            'MAX_IMAGE_SIZE'        => 0,
            'S3_ACCESS'             => '',
            'S3_SECRET'             => '',
            'S3_BUCKET'             => '',
            'S3_URL'                => '',
            'GCS_MAIL'              => '',
            'GCS_KEY'               => '',
            'GCS_BUCKET'            => '',
            "MIDDLEWARE_CACHE"             => false,
            "MIDDLEWARE_INTL"              => true,
            "MIDDLEWARE_LOGGER"            => true,
            "MIDDLEWARE_FIXER"             => true,
            "MIDDLEWARE_CLIENTIP"          => true,
            "MIDDLEWARE_SESSION"           => true,
            "MIDDLEWARE_HTTPS"             => false,
            "MIDDLEWARE_GZIP"              => false,
            "MIDDLEWARE_MINIFY"            => true,
            "MIDDLEWARE_OWASP"             => true,
            "MIDDLEWARE_CSP"               => true,
            "MIDDLEWARE_FP"                => true,
            "MIDDLEWARE_PP"                => true,
            "MIDDLEWARE_IDS"               => false,
            "MIDDLEWARE_CSRF"              => true,
            "MIDDLEWARE_CORS"              => false,
            "MIDDLEWARE_TRANSACTION"       => true,
            "MIDDLEWARE_UPLOADS"           => true
        ];
    }

    /**
     * @param array<string,scalar|null> $options
     */
    public function __construct(array $options)
    {
        $defaults = $this->defaults();
        $this->config = new Config($defaults);
        $this->config->fromArray($options);
        if ($this->config->get('ENVPARSE')) {
            $this->config->fromEnv(true);
        }
        $this->container = new DIContainer();
        $this->container->register($this->container);
        $this->container->register($this);

        if ($this->config->get('DB_CONFIG') && $this->config->get('DATABASE')) {
            try {
                $dbc = $this->db();
                $cnf = $this->cache()->getSet('config', function () use ($dbc) {
                    return @$dbc->all("SELECT k, v FROM config", [], 'k', true);
                });
            } catch (\Exception) {
                $cnf = [];
            }
            foreach ($cnf as $k => $v) {
                switch (gettype($defaults[$k] ?? null)) {
                    case 'boolean':
                        $v = $v === 'false' ? false : (bool)$v;
                        break;
                    case 'integer':
                        $v = (int)$v;
                        break;
                    case 'double':
                        $v = (float)$v;
                        break;
                    case 'string':
                        $v = (int)$v;
                        break;
                    default:
                        if (preg_match('(^\d+$)', $v)) {
                            $v = (int)$v;
                        } elseif (is_numeric($v)) {
                            $v = (float)$v;
                        } elseif ($v === 'true') {
                            $v = true;
                        } elseif ($v === 'false') {
                            $v = false;
                        } elseif ($v === 'null') {
                            $v = null;
                        }
                        break;
                }
                $cnf[$k] = $v;
            }
            $this->config->fromArray($cnf);
        }
        $config = clone $this->config; // clone in order to prevent changes
        $config->lock();
        $this->container->register($config);
        $this->normalize();
    }
    protected function normalize(): void
    {
        // timezone & locale
        setlocale(LC_ALL, 'en_US.UTF-8');
        date_default_timezone_set($this->config->getString('TIMEZONE') ?: 'Europe/Sofia');
        ini_set('default_charset', 'UTF-8');
        mb_internal_encoding('UTF-8');

        // ERROR HANDLING
        error_reporting(E_ALL);
        ini_set('log_errors', 'On');
        ini_set('display_errors', ( $this->config->getBool('DEBUG') ? 'On' : 'Off' ));
        ini_set('display_start_up_errors', ( $this->config->getBool('DEBUG') ? 'On' : 'Off' ));
        ini_set('log_errors_max_len', '0');
        ini_set('ignore_repeated_errors', '1');
        ini_set('ignore_repeated_source', '0');
        ini_set('track_errors', '0');
        ini_set('html_errors', '0');
        ini_set('report_memleaks', '1');
        if ($this->config->getBool('DEBUG')) {
            ini_set('opcache.enable', '0');
        }
        // create a default exception handler
        set_exception_handler(function ($e) {
            @error_log(
                date("[d-M-Y H:i:s e] ") .
                'PHP Exception:' .
                ((int)$e->getCode() ? ' ' . $e->getCode() . ' -' : '') . ' ' . $e->getMessage() .
                ' in ' . $e->getFile() . ' on line ' . $e->getLine()
            );
            while (ob_get_level()) {
                ob_end_clean();
            }
            if ($this->config->getBool('CLI')) {
                if ($this->config->getBool('DEBUG')) {
                    echo $e->getMessage() . ' > ' . $e->getFile() . ':' . $e->getLine() . "\r\n";
                    echo $e->getTraceAsString() . "\r\n";
                } else {
                    echo 'Try again later' . "\r\n";
                }
                exit(1);
            }
            if (!headers_sent()) {
                header(
                    'Content-Type: text/html; charset=utf-8',
                    true,
                    $e->getCode() >= 200 && $e->getCode() <= 503 ? (int)$e->getCode() : 500
                );
            }
            echo '
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset="UTF-8"><title>Please, try again later.</title>
                    <style>body { background:#e0e0e0; min-width:320px; }
                        h1 { font-size:1.4em; text-align:center; margin:2em 0 0 0; color:#8b0000; }
                        p { font-size:1.2em; text-align:center; margin:2em 0 0 0; }
                    </style>
                </head>
                <body>
                    <h1>Please, try again later.</h1>' .
                    ($this->config->getBool('DEBUG') ?
                        '<p>
                            <strong>' . htmlspecialchars($e->getMessage()) . '</strong><br />
                            <code>' . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . '</code>
                        </p>
                        <pre>' .
                        htmlspecialchars(str_replace(': ', ": \n\t", $e->getTraceAsString())) .
                        '</pre>' :
                        ''
                    ) .
                '</body>
            </html>';
            die();
        });
        // turn all errors into exceptions
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            // do not touch errors where @ is used or that are not marked for reporting
            if ($errno === 0 || !($errno & error_reporting())) {
                return true;
            }
            // do not throw exceptions for "lightweight" errors - those will end up in the log and not break execution
            if (in_array($errno, [ E_NOTICE, E_DEPRECATED, E_USER_NOTICE, E_USER_DEPRECATED ])) {
                @error_log(
                    date("[d-M-Y H:i:s e] ") .
                    'PHP Notice: ' . $errno . ' ' . $errstr .
                    ($errfile && $errline ? ' in ' . $errfile . ' on line ' . $errline : '')
                );
                return true;
            }
            // throw exception for all others
            throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
        });

        if (!$this->config->getBool('CLI')) {
            // normalize REDIRECT_ vars
            foreach ($_SERVER as $k => $v) {
                /** @psalm-suppress RedundantCondition */
                if (is_string($k) && substr($k, 0, 9) === 'REDIRECT_' && !isset($_SERVER[substr($k, 9)])) {
                    $_SERVER[substr($k, 9)] = $v;
                }
            }

            // normalize cert number
            if (isset($_SERVER['SSL_CLIENT_M_SERIAL']) && is_string($_SERVER['SSL_CLIENT_M_SERIAL'])) {
                $_SERVER['SSL_CLIENT_M_SERIAL'] = strtoupper(ltrim($_SERVER['SSL_CLIENT_M_SERIAL'], '0'));
            }

            // normalize session
            if (session_status() !== PHP_SESSION_ACTIVE) {
                ini_set('session.use_cookies', '1');
                ini_set('session.use_only_cookies', '1');
                ini_set('session.use_strict_mode', '1');
                ini_set('session.use_trans_sid', '0');
                ini_set('session.name', $this->config->getString('APPNAME_CLEAN') . '_SESSID');
                ini_set('session.gc_maxlifetime', $this->config->getString('SESSION_TIMEOUT'));
                ini_set('session.cookie_name', $this->config->getString('APPNAME_CLEAN') . '_SESSID');
                // ini_set('session.cookie_path', $this->url()->linkTo('')); // php native will set to path to /
                ini_set('session.cookie_lifetime', '0');
                ini_set('session.cookie_httponly', '1');
                ini_set('session.cookie_samesite', 'Lax');
                if (
                    isset($_SERVER['HTTPS']) &&
                    !empty($_SERVER['HTTPS']) &&
                    (!is_string($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) !== 'off')
                ) {
                    ini_set('session.cookie_secure', '1');
                }
            }

            // remove revealing headers
            @header_remove('x-powered-by');
        }
    }
    public function config(): Config
    {
        return $this->container->get(Config::class);
    }

    public function db(bool $schema = false): DBInterface
    {
        if ($this->container->has(DB::class)) {
            $dbc = $this->container->get(DB::class);
            if ($schema && !$dbc->hasSchema()) {
                $this->schema();
            }
            return $dbc;
        }
        $dbc = new DB($this->config->getString('DATABASE'));
        $this->container->register($dbc);
        if ($schema && !$dbc->hasSchema()) {
            $this->schema();
        }
        return $dbc;
    }
    public function schema(bool $refresh = false): void
    {
        $key = $this->config->getString('APPNAME_CLEAN') . '_schema';
        $dbc = $this->db(false);
        $cache = $this->cache();
        if (!$refresh && $cached = $cache->get($key)) {
            $dbc->setSchema($cached);
        } else {
            $cache->getSet(
                $key,
                function () use ($dbc) {
                    $dbc->parseSchema();
                    return $dbc->getSchema();
                }
            );
        }
    }
    public function cache(): CacheInterface
    {
        if ($this->container->has(CacheInterface::class)) {
            return $this->container->get(CacheInterface::class);
        }
        $key =  $this->config->getString('APPNAME_CLEAN');
        $loc =  $this->config->getString('STORAGE_CACHE');
        switch ($this->config->getString('CACHE')) {
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
        $this->container->register($cache);
        return $cache;
    }
    public function req(): Request
    {
        if ($this->container->has(Request::class)) {
            return $this->container->get(Request::class);
        }
        $req = Request::fromGlobals();
        $this->container->register($req);
        return $req;
    }
    public function url(): Uri
    {
        if ($this->container->has(Uri::class)) {
            return $this->container->get(Uri::class);
        }
        $url = $this->req()->getUrl();
        $this->container->register($url);
        return $url;
    }
    public function files(): Files
    {
        if ($this->container->has(Files::class)) {
            return $this->container->get(Files::class);
        }
        switch ($this->config->getString('STORAGE_UPLOADS')) {
            case 'DATABASE':
                $storage = new FileDatabase(
                    // phpcs:ignore
                    realpath($this->config->getString('STORAGE_TMP')) ?: throw new \RuntimeException(),
                    $this->db(false),
                    'uploads'
                );
                break;
            case 'S3':
                $storage = new FileDatabaseCloud(
                    new S3(
                        $this->config->getString('S3_ACCESS'),
                        $this->config->getString('S3_SECRET'),
                        $this->config->getString('S3_BUCKET'),
                        $this->config->getString('S3_URL')
                    ),
                    // phpcs:ignore
                    realpath($this->config->getString('STORAGE_TMP')) ?: throw new \RuntimeException(),
                    $this->db(false),
                    'uploads'
                );
                break;
            case 'GCS':
                $storage = new FileDatabaseCloud(
                    GCS::fromKey(
                        $this->config->getString('GCS_MAIL'),
                        $this->config->getString('GCS_KEY'),
                        $this->config->getString('GCS_BUCKET')
                    ),
                    // phpcs:ignore
                    realpath($this->config->getString('STORAGE_TMP')) ?: throw new \RuntimeException(),
                    $this->db(false),
                    'uploads'
                );
                break;
            default:
                $storage = new FileDatabaseStorage(
                    // phpcs:ignore
                    realpath($this->config->getString('STORAGE_UPLOADS')) ?: throw new \RuntimeException(),
                    $this->db(false),
                    'uploads',
                    false,
                    date('Y/m/d'),
                    realpath($this->config->getString('STORAGE_TMP')) ?: sys_get_temp_dir()
                );
                break;
        }
        $files = new Files(
            $storage,
            $this->url(),
            $this->config->getString('UPLOAD_URL'),
            $this->config->getString('SIGNATUREKEY')
        );
        $this->container->register($storage);
        $this->container->register($files);
        $this->container->alias(get_class($files), ['file']);
        return $files;
    }
    public function sess(): SessionInterface
    {
        if ($this->container->has(SessionInterface::class)) {
            return $this->container->get(SessionInterface::class);
        }
        switch ($this->config->getString('STORAGE_SESSION')) {
            case 'DATABASE':
                $sess = new SessionDatabase($this->db());
                break;
            case 'CACHE':
                $sess = new SessionCache($this->cache(), $this->config->getString('APPNAME_CLEAN') . '_sess');
                break;
            case 'PHP':
                $sess = null;
                break;
            default:
                $dir = realpath($this->config->getString('STORAGE_SESSION')) ?:
                throw new \RuntimeException();
                $sess = new SessionFile($dir, 'sess_');
                break;
        }
        $session = !isset($sess) ? new Native() : new Session($sess, $this->config->getInt('SESSION_TIMEOUT'));
        $this->container->register($session);
        return $session;
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
                $temp = @json_decode(file_get_contents($file) ?: '{}', true) ?? [];
            }
            $intl->addTranslations($lang, $temp);
        }
        $this->container->register($intl);
        return $intl;
    }
    public function views(): Views
    {
        if ($this->container->has(Views::class)) {
            return $this->container->get(Views::class);
        }
        $intl = $this->intl();
        $cache = $this->cache();
        $views  = (new Views())
            ->addData([
                'intl'  => $intl,
                'cspNonce'  => '',
                'config' => function (string $k): mixed {
                    return $this->config->get($k);
                },
                'asset' => function (
                    string $path = '',
                    array $params = [],
                    bool $absolute = false
                ) use (
                    $cache
                ): string {
                    static $assets_version;
                    $path = explode('.', $path);
                    $fext = array_pop($path);
                    if ($this->config->getBool('ASSETCACHE')) {
                        if (!$assets_version) {
                            $assets_version = $cache->getSet('assets_version', function () {
                                return time();
                            });
                        }
                        $path[] = $assets_version;
                    }
                    $path[] = $fext;
                    return $this->url()->linkTo(implode('.', $path), $params, $absolute);
                },
                'upload' => function (
                    string $id,
                    array $params = [],
                    bool $absolute = false
                ): string {
                    return $this->files()->toLink($id, $params, $absolute);
                }
            ]);
        $this->container->register($views);
        return $views;
    }
    public function mail(): SenderInterface
    {
        if ($this->container->has(SenderInterface::class)) {
            return $this->container->get(SenderInterface::class);
        }
        $dbc = $this->db(true);
        $tbl = 'mails';
        $sender = new CallbackSender(function (MailInterface $message) use ($dbc, $tbl) {
            $recp = array_filter(
                array_unique(
                    array_merge(
                        $message->getTo(true),
                        $message->getCc(true),
                        $message->getBcc(true)
                    )
                )
            );
            $dbc->table($tbl)->insert([
                'added' => date('Y-m-d H:i:s'),
                'recipient' => implode(', ', $recp),
                'subject' => $message->getSubject(),
                'content' => (string)$message
            ]);
            return [ 'good' => $recp, 'fail' => [] ];
        });
        if ($this->config->getBool('MAILQUEUE')) {
            $mail = $sender;
        } else {
            $mail = new MultiSender([
                $this->config->getString('SMTP_CONNECTION') ?
                        new SMTPSender(
                            $this->config->getString('SMTP_CONNECTION'),
                            $this->config->get('SMTP_USER'),
                            $this->config->get('SMTP_PASSWORD')
                        ) :
                        new MailSender(),
                        $this->config->getString('STORAGE_MAIL') === 'DATABASE' ?
                    $sender :
                    new FileSender(rtrim($this->config->getString('STORAGE_MAIL'), '\\/') . '/' . date('Y-m-d'))
            ]);
        }
        $this->container->register($mail);
        $this->container->alias(get_class($mail), ['mail']);
        return $mail;
    }
    public function di(): DIContainer
    {
        return $this->container;
    }
    public function logger(): Logger
    {
        if ($this->container->has(Logger::class)) {
            return $this->container->get(Logger::class);
        }
        $locations = explode(',', $this->config->getString('LOG'));
        $err = new Logger(
            $this->config->getString('APPNAME_CLEAN'),
            array_values(
                array_filter([
                    in_array('file', $locations) ?
                        new StreamHandler(
                            realpath($this->config->getString('STORAGE_LOG')) . '/' .
                            date('Y') . '/' . date('m.d') . '.log',
                            $this->config->getBool('DEBUG') ? Level::Debug : Level::Info
                        ) :
                        null,
                    in_array('errorlog', $locations) ?
                        new ErrorLogHandler(
                            ErrorLogHandler::OPERATING_SYSTEM,
                            $this->config->getBool('DEBUG') ? Level::Debug : Level::Info
                        ) :
                        null,
                    in_array('syslog', $locations) ?
                        new SyslogHandler(
                            $this->config->getString('APPNAME'),
                            LOG_USER,
                            $this->config->getBool('DEBUG') ? Level::Debug : Level::Info
                        ) :
                        null
                ])
            )
        );
        $this->container->register($err);
        return $err;
    }
    /**
     * @param string $message
     * @param array<mixed> $context
     * @return void
     */
    public function journal(string $message, array $context = []): void
    {
        $this->db(true)->table('log_system')->insert(array_merge(
            $context,
            [
                'message' => $message,
                'lvl' => 'INFO',
                'context' => json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'created' => date('Y-m-d H:i:s'),
                'usr' => null
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
                $test ? 'test' : ''
            ])
        );
    }

    public function middleware(string $class): callable
    {
        switch ($class) {
            case \base\middleware\Minify::class:
                return new \base\middleware\Minify();
            case \base\middleware\Cache::class:
                return new \base\middleware\Cache(
                    $this->cache(),
                    $this->config()->getInt('CACHE_RESPONSE_TTL'),
                    $this->config()->getInt('CACHE_RESPONSE_MAX_SIZE')
                );
            case \base\middleware\Intl::class:
                return new \base\middleware\Intl(
                    $this->intl(),
                    $this->config->getString('APPNAME_CLEAN') . '_LOCALE'
                );
            case \base\middleware\Logger::class:
                return new \base\middleware\Logger(
                    $this->logger(),
                    $this->config->getBool('DEBUG')
                );
            case \base\middleware\Fixer::class:
                return new \base\middleware\Fixer();
            case \base\middleware\ClientIP::class:
                return new \base\middleware\ClientIP();
            case \base\middleware\Session::class:
                return new \base\middleware\Session(
                    $this->sess(),
                    $this->config->getBool('SESSION_AUTOSTART'),
                    $this->config->getString('APPNAME_CLEAN') . '_SESSID',
                    $this->config->getInt('SESSION_TIMEOUT'),
                    $this->config->getInt('SESSION_REGENERATE')
                );
            case \base\middleware\HTTPS::class: // if FORCE_HTTPS
                return new \base\middleware\HTTPS(
                    $this->config->getBool('DEBUG') ? 1 : 30,
                    $this->config->getBool('DEBUG') ? 1 : 30,
                    false,
                    'ect-report'
                );
            case \base\middleware\Gzip::class: // if GZIP
                return new \base\middleware\Gzip();
            case \base\middleware\OWASP::class:
                return new \base\middleware\OWASP('xss-report');
            case \base\middleware\CSP::class: // if CSP
                return new \base\middleware\CSP(
                    $this->views(),
                    [
                        'default-src'    => "'self'",
                        'script-src'     => ["'self'", "'nonce-{__NONCE__}'"],
                        'style-src'      => ["'self'", "'nonce-{__NONCE__}'"],
                        'img-src'        => ["'self'", "data:", "blob:"],
                        'font-src'       => ["'self'", "data:"],
                        'frame-src'      => ["'self'"],
                        'style-src-elem' => ["'self'", "'nonce-{__NONCE__}'"],
                        'style-src-attr' => ["'self'", "'nonce-{__NONCE__}'" , "'unsafe-inline'"],
                    ],
                    'csp-report'
                );
            case \base\middleware\FP::class: // if FP
                return new \base\middleware\FP();
            case \base\middleware\PP::class: // if PP
                return new \base\middleware\PP();
            case \base\middleware\IDS::class: // if IDS
                return new \base\middleware\IDS($this->config->getInt('IDS_LIMIT'));
            case \base\middleware\CSRF::class: // if CSRF
                return new \base\middleware\CSRF(
                    $this->config->getString('SIGNATUREKEY'),
                    $this->config->getString('ENCRYPTIONKEY'),
                    [ 'iss'  => $this->config->getString('APPNAME') ],
                    $this->config->getInt('CSRF_TIMEOUT')
                );
            case \base\middleware\CORS::class: // if CORS
                return new \base\middleware\CORS();
            case \base\middleware\Uploads::class:
                return new \base\middleware\Uploads(
                    $this->files(),
                    $this->config->getString('UPLOAD_URL'),
                    $this->config->getString('STORAGE_TMP'),
                    $this->config->getString('SENDFILE'),
                    $this->config->getInt('MAX_IMAGE_SIZE')
                );
            case \base\middleware\Transaction::class:
                return new \base\middleware\Transaction($this->db());
            default:
                throw new \RuntimeException('Unknown middleware');
        }
    }
    /**
     * @return array<string,string>
     */
    public function middlewares(): array
    {
        return [
            "MIDDLEWARE_CACHE" => \base\middleware\Cache::class,
            "MIDDLEWARE_LOGGER" => \base\middleware\Logger::class,
            "MIDDLEWARE_CLIENTIP" => \base\middleware\ClientIP::class,
            "MIDDLEWARE_MINIFY" => \base\middleware\Minify::class,
            "MIDDLEWARE_FIXER" => \base\middleware\Fixer::class,
            "MIDDLEWARE_SESSION" => \base\middleware\Session::class,
            "MIDDLEWARE_HTTPS" => \base\middleware\HTTPS::class,
            "MIDDLEWARE_GZIP" => \base\middleware\Gzip::class,
            "MIDDLEWARE_OWASP" => \base\middleware\OWASP::class,
            "MIDDLEWARE_CSP" => \base\middleware\CSP::class,
            "MIDDLEWARE_FP" => \base\middleware\FP::class,
            "MIDDLEWARE_PP" => \base\middleware\PP::class,
            "MIDDLEWARE_IDS" => \base\middleware\IDS::class,
            "MIDDLEWARE_CSRF" => \base\middleware\CSRF::class,
            "MIDDLEWARE_CORS" => \base\middleware\CORS::class,
            "MIDDLEWARE_TRANSACTION" => \base\middleware\Transaction::class,
            "MIDDLEWARE_UPLOADS" => \base\middleware\Uploads::class,
            "MIDDLEWARE_INTL" => \base\middleware\Intl::class
        ];
    }
    /**
     * @return GlobalGenerator<callable>
     */
    public function stack(): GlobalGenerator
    {
        foreach ($this->middlewares() as $config => $middleware) {
            try {
                if ($this->config->getBool($config)) {
                    yield $this->middleware($middleware);
                }
            } catch (\RuntimeException) {
            }
        }
        yield function (Request $req): Response {
            return $this->core($req);
        };
    }
    public function core(Request $req): Response
    {
        return new Response(200, ((string)$req->getUrl()) . ' OK');
    }
    /**
     * @param Iterator<callable> $stack
     * @param Request $req
     * @return Response
     */
    public function run(Iterator $stack, Request $req): Response
    {
        $this->container->register($req);
        $this->container->register($req->getUrl());
        $this->views()->addData([
            'req' => $req,
            'url' => $req->getUrl()
        ]);
        $run = function (Request $req) use ($stack, &$run): Response {
            if (!$stack->valid()) {
                throw new RuntimeException('Stack empty');
            }
            $middleware = $stack->current();
            $stack->next();
            /** @psalm-suppress PossiblyNullFunctionCall */
            return call_user_func($middleware, $req, $run);
        };
        return $run($req);
    }
    public function runArray(array &$stack, Request $req): Response
    {
        $this->container->register($req);
        $this->container->register($req->getUrl());
        $this->views()->addData([
            'req' => $req,
            'url' => $req->getUrl()
        ]);
        $run = function (Request $req) use (&$stack, &$run): Response {
            $m = current($stack);
            next($stack);
            if ($m === false) {
                throw new RuntimeException('Stack empty');
            }
            return call_user_func($m, $req, $run);
        };
        return $run($req);
    }
    public function emit(Response $res): void
    {
        (new Emitter())->emit($res);
    }

    public function dispatcher(): Dispatcher
    {
        if ($this->container->has(Dispatcher::class)) {
            return $this->container->get(Dispatcher::class);
        }
        $dispatcher = new Dispatcher();
        $this->container->register($dispatcher);
        return $dispatcher;
    }
    public function listen(string $event, callable $listener): Dispatcher
    {
        return $this->dispatcher()->listen($event, $listener);
    }
    public function dispatch(EventInterface $event, bool $lazy = false): Dispatcher
    {
        return $this->dispatcher()->dispatch($event, $lazy);
    }
}
