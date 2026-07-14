<?php

declare(strict_types=1);

namespace base;

use RuntimeException;
use vakata\config\Config;
use vakata\random\Generator;

class Jobs
{
    protected App $app;
    protected Config $cnf;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->cnf = $app->config();
    }

    protected function readline(string $prompt = ''): string
    {
        if (!defined('STDIN')) {
            define('STDIN', fopen('php://stdin', 'r') ?: throw new RuntimeException('NO STDIN'));
        }
        if ($prompt) {
            echo $prompt;
        }
        return trim(fgets(STDIN) ?: '');
    }

    // interactive
    public function setup(
        ?string $_app = null,
        ?string $_appc = null,
        ?string $_db = null,
        ?string $_file = null,
        ?string $_host = null,
        ?string $_user = null,
        ?string $_pass = null,
        ?string $_schema = null,
        ?array $_features = null
    ): void {
        $bdir = $this->cnf->getString('BASEDIR');
        $name = basename($bdir);
        $config = [
            'APPNAME' => $name,
            'APPNAME_CLEAN' => strtoupper(preg_replace('([^a-z0-9_]+)ui', '_', $name) ?: '')
        ];
        if ($input = $_app ?? $this->readline("Application name: (" . $config['APPNAME'] . "): ")) {
            $config['APPNAME'] = $input;
        }
        if (
            ($input = $_appc ?? $this->readline(
                "Application name (alphanum and _): (" . $config['APPNAME_CLEAN'] . "): "
            )) &&
            preg_match('(^[A-Z0-9_]+$)', $input)
        ) {
            $config['APPNAME_CLEAN'] = $input;
        }

        $connection = '';
        echo "\r\n";
        echo 'DATABASE CONNECTION:' . "\r\n";
        do {
            $type = $_db ?? $this->readline(" - Database engine: (POSTGRE/mysql/oracle/sqlite): ");
            if (!$type) {
                $type = 'postgre';
            }
            $type = strtolower($type);
        } while (!in_array($type, ['mysql','oracle','postgre','sqlite']));
        if ($type === 'sqlite') {
            $file = $_file ?? $this->readline(" - File: (" . strtolower($name) . ".sqlite): ");
            if (!$file) {
                $file = strtolower($name) . '.sqlite';
            }
            if (!strpos($file, ':') && strpos($file, '/') !== 0) {
                $file = $bdir . '/' . $file;
            }
            $connection = 'sqlite://' . $file;
        } else {
            $dflt = $type === 'mysql' ? 'root' : ($type === 'oracle' ? 'sys' : 'postgres');
            $user = $_user ?? $this->readline(" - Username: (" . $dflt . "): ");
            if (!$user) {
                $user = $dflt;
            }
            $pass = $_pass ?? $this->readline(" - Password: ");
            $host = $_host ?? $this->readline(" - Hostname: (127.0.0.1): ");
            if (!$host) {
                $host = '127.0.0.1';
            }
            $dbname = $_schema ?? $this->readline(" - Database name: (" . strtolower($name) . "): ");
            if (!$dbname) {
                $dbname = strtolower($name);
            }
            $connection = $type . '://' . $user . ($pass ? ':' . $pass : '') . '@' . $host . '/' . $dbname;
        }
        $config['DATABASE'] = $connection;

        echo "\r\n";
        echo 'FEATURES:' . "\r\n";
        foreach ($this->cnf->toArray() as $k => $v) {
            if (strpos($k, 'FEATURE_') === 0) {
                $feature = strtoupper(str_replace('FEATURE_', '', $k));
                if (isset($_features)) {
                    $config['FEATURE_' . $feature] = isset($_features[$feature]) && $_features[$feature] ?
                        'true' :
                        'false';
                } else {
                    $config['FEATURE_' . $feature] = $this->readline(' - ' . $feature . ' (y/N): ') === 'y' ?
                        'true' :
                        'false';
                }
            }
        }

        // update .env file
        $file = $bdir . '/.env';
        $data = file_get_contents($file) ?: '';
        if (strpos($data, 'SIGNATUREKEY') === false || strpos($data, 'ENCRYPTIONKEY') === false) {
            $config['SIGNATUREKEY'] = Generator::string(32);
            $config['ENCRYPTIONKEY'] = Generator::string(32);
        }
        foreach (array_keys($config) as $k) {
            $data = preg_replace('(' . preg_quote($k) . '.*?\n)', '', $data) ?: '';
        }
        $data .= "\n";
        foreach ($config as $k => $v) {
            $v = preg_match('(^(true|false|\d+)$)', $v) ? $v : '"' . $v . '"';
            $data .= $k . ' = ' . $v . "\n";
        }
        file_put_contents($file, $data) ?: throw new RuntimeException('Could not write to config file: ' . $file);
    }
    public function permissions(): void
    {
        $needed = [];
        $needed[] = $this->cnf->getString('STORAGE_TMP');
        $needed[] = $this->cnf->getString('STORAGE_INTL');
        $needed[] = $this->cnf->getString('STORAGE_VERSIONS');
        if ($this->cnf->getString('CACHE') === 'FILE') {
            $needed[] = $this->cnf->getString('STORAGE_CACHE');
        }
        if (strpos($this->cnf->getString('LOG'), 'file') !== false) {
            $needed[] = $this->cnf->getString('STORAGE_LOG');
        }
        if (!in_array($this->cnf->getString('STORAGE_UPLOADS'), ['DATABASE', 'S3', 'GCS'])) {
            $needed[] = $this->cnf->getString('STORAGE_UPLOADS');
        }
        if (!in_array($this->cnf->getString('STORAGE_SESSION'), ['DATABASE', 'CACHE', 'PHP'])) {
            $needed[] = $this->cnf->getString('STORAGE_SESSION');
        }
        if (!in_array($this->cnf->getString('STORAGE_REQ'), ['DATABASE', ''])) {
            $needed[] = $this->cnf->getString('STORAGE_REQ');
        }
        if (!in_array($this->cnf->getString('STORAGE_CERTIFICATES'), ['DATABASE', ''])) {
            $needed[] = $this->cnf->getString('STORAGE_CERTIFICATES');
        }
        if (!in_array($this->cnf->getString('STORAGE_MAIL'), ['DATABASE'])) {
            $needed[] = $this->cnf->getString('STORAGE_MAIL');
        }
        foreach ($needed as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
        }
        // storage dirs
        foreach (
            [
                'STORAGE_UPLOADS',
                'STORAGE_CACHE',
                'STORAGE_SESSION',
                'STORAGE_LOG',
                'STORAGE_TMP',
                'STORAGE_INTL',
                'STORAGE_DATABASE',
                'STORAGE_MAIL',
                'STORAGE_REQ',
                'STORAGE_CERTIFICATES',
                'STORAGE_VERSIONS'
            ] as $dir
        ) {
            $dir = $this->cnf->getString($dir);
            if (is_dir($dir)) {
                @chmod($dir, 0777);
            }
        }

        foreach (scandir($this->cnf->getString('STORAGE_INTL')) ?: [] as $file) {
            if (is_file($this->cnf->getString('STORAGE_INTL') . '/' . $file)) {
                @chmod($this->cnf->getString('STORAGE_INTL') . '/' . $file, 0666);
            }
        }
        if ($this->cnf->getString('STORAGE_INTL_PUBLIC')) {
            foreach (scandir($this->cnf->getString('STORAGE_INTL_PUBLIC')) ?: [] as $file) {
                if (is_file($this->cnf->getString('STORAGE_INTL_PUBLIC') . '/' . $file)) {
                    @chmod($this->cnf->getString('STORAGE_INTL_PUBLIC') . '/' . $file, 0666);
                }
            }
        }

        // cronjobs
        $jobdir = $this->cnf->getString('BASEDIR') . '/scripts/';
        $files = scandir($jobdir);
        if (!$files) {
            $files = [];
        }
        foreach ($files as $item) {
            if (is_file($jobdir . '/' . $item) && strpos($item, '.php') !== false) {
                @chmod($jobdir . '/' . $item, 0775);
            }
        }

        // docker scripts
        $jobdir = $this->cnf->getString('BASEDIR') . '/docker/';
        $files = scandir($jobdir);
        if (!$files) {
            $files = [];
        }
        foreach ($files as $item) {
            if (is_file($jobdir . '/' . $item) && strpos($item, '.sh') !== false) {
                @chmod($jobdir . '/' . $item, 0775);
            }
        }
    }

    public function tmpClean(): void
    {
        $threshold = strtotime('-24 hours');
        $files = scandir($this->cnf->getString('STORAGE_TMP'));
        if (!$files) {
            $files = [];
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->cnf->getString('STORAGE_TMP'),
                \FilesystemIterator::KEY_AS_PATHNAME |
                \FilesystemIterator::CURRENT_AS_FILEINFO |
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $object) {
            if (
                $object->isFile() &&
                $object->getMTime() < $threshold &&
                $object->getFileName() !== '.gitignore'
            ) {
                @unlink($object->getRealPath());
            }
            if (
                $object->isDir() &&
                count(scandir($object->getRealPath()) ?: []) === 2
            ) {
                @rmdir($object->getRealPath());
            }
        }
        if ($this->cnf->getString('SENDFILE')) {
            $dir = explode(':', $this->cnf->getString('SENDFILE'), 2);
            $dir = $dir[1] ?? '';
            $dir = rtrim($dir, '/\\');
            $files = is_dir($dir) ? scandir($dir) : [];
            if (!$files) {
                $files = [];
            }
            foreach ($files as $file) {
                if (
                    is_file($dir . '/' . $file) &&
                    filemtime($dir . '/' . $file) < $threshold &&
                    $file !== '.gitignore'
                ) {
                    @unlink($dir . '/' . $file);
                }
            }
        }
    }

    public function migrationsUp(): void
    {
        $this->app->migrations()->up();
        $this->app->schema(true);
    }
    public function migrationsTest(): void
    {
        $this->app->migrations(true)->up();
        $this->app->schema(true);
    }
    public function migrationsReset(): void
    {
        $this->app->migrations()->reset();
        $this->app->migrations()->up();
        $this->app->schema(true);
    }

    public function cacheEnv(string $file = '.env'): void
    {
        $file = $this->cnf->getString('BASEDIR') . '/' . basename($file);
        if (is_file($file)) {
            $config = new \vakata\config\Config();
            $config->fromFile($file);
            if ($config->getBool('ENVPARSE', false)) {
                $config->fromEnv(true);
            }
            $data = $config->toArray();
            file_put_contents(
                $this->cnf->getString('BASEDIR') . '/.env.php',
                '<?php' . "\n" . '// This file is autogenerated, do not edit manually!' . "\n" .
                (
                    !$config->getBool('ENVCACHE', false) ?
                        'return null;' :
                        'return ' . var_export($data, true) . ';' . "\n"
                )
            );
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        }
    }
    public function cacheClean(): void
    {
        $cache = $this->app->cache();
        $cache->clear();
    }
    public function cacheLangs(): void
    {
        if (!$this->cnf->getBool('LANGCACHE')) {
            return;
        }
        foreach (
            [
                $this->cnf->getString('STORAGE_INTL'),
                $this->cnf->getString('STORAGE_INTL_PUBLIC')
            ] as $dir
        ) {
            if (!$dir || !is_dir($dir)) {
                continue;
            }
            $files = scandir($dir);
            if (!$files) {
                $files = [];
            }
            foreach ($files as $file) {
                if (is_file($dir . '/' . $file) && preg_match('(\.json$)i', $file)) {
                    $data = @json_decode(
                        file_get_contents($dir . '/' . $file) ?: throw new RuntimeException(),
                        true
                    );
                    if (is_array($data)) {
                        file_put_contents(
                            $dir . '/' . $file . '.php',
                            '<?php return ' . var_export($data, true) . ';'
                        );
                        if (function_exists('opcache_compile_file')) {
                            try {
                                @opcache_compile_file($dir . '/' . $file . '.php');
                            } catch (\Exception) {
                            }
                        }
                    }
                }
            }
        }
    }
    public function cacheSchema(): void
    {
        $this->app->schema(true);
    }

    /**
     * @param string $zipfile
     * @param array<string,string> $additional
     * @return void
     */
    public function pack(string $zipfile, array $additional = []): void
    {
        $file = [];
        $dirs = ['app', 'public', 'scripts', 'vendor', 'storage/intl', 'storage/database'];
        $bdir = $this->cnf->getString('BASEDIR');
        foreach ($dirs as $dir) {
            $path = realpath($bdir . '/' . $dir) ?: throw new RuntimeException();
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $path,
                    \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO
                )
            );
            foreach ($files as $name => $object) {
                if ($object->isFile()) {
                    $new = ltrim(str_replace('\\', '/', substr($name, strlen($bdir))), '/');
                    $file[$new] = $name;
                }
            }
        }
        $items = [
            'composer.json',
            'composer.lock',
            'storage/versions/pub.key',
            'storage/versions/.version'
        ];
        foreach ($items as $name) {
            if (is_file($this->cnf->getString('BASEDIR') . '/' . $name)) {
                $file[$name] = realpath($this->cnf->getString('BASEDIR') . '/' . $name);
            }
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipfile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create ZIP');
        }
        foreach ($file as $name => $path) {
            $zip->addFile($path, $name);
        }
        foreach ($additional as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
    }

    public function versionCreate(string $version, ?string $password = null): void
    {
        $bdir = $this->cnf->getString('BASEDIR');
        $vdir = $this->cnf->getString('STORAGE_VERSIONS');

        if (!$password) {
            $password = 'filecheck';
        }

        if (!is_file($vdir . '/priv.key')) {
            $keypair = sodium_crypto_sign_keypair();
            file_put_contents($vdir . '/pub.key', sodium_crypto_sign_publickey($keypair));
            file_put_contents($vdir . '/priv.key', sodium_crypto_sign_secretkey($keypair));
        }

        if (!preg_match('(^\d+\.\d+\.\d+$)', $version)) {
            throw new RuntimeException('Please supply a valid version!');
        }

        if (is_file($vdir . '/.version')) {
            $temp = file_get_contents($vdir . '/.version') ?: throw new RuntimeException('Read error');
            $temp = explode(':', $temp)[0];
            if (preg_match('(^\d+\.\d+\.\d+$)', $temp)) {
                list($major, $minor, $patch) = explode('.', $temp, 3);
                list($new_major, $new_minor, $new_patch) = explode('.', $version, 3);
                if (
                    $new_major < $major ||
                    ($new_major === $major && $new_minor < $minor) ||
                    ($new_major === $major && $new_minor === $minor && $new_patch <= $patch)
                ) {
                    throw new RuntimeException('Please supply a higher version!');
                }
            }
        }

        file_put_contents(
            $bdir . '/.env',
            'VERSION = "' . $version . '"' . "\n" .
            preg_replace(
                "(^VERSION\s*=.*$)m",
                '',
                file_get_contents($bdir . '/.env') ?: throw new RuntimeException('Read error')
            )
        );

        $list = [];
        foreach (['app', 'public', 'scripts'] as $dir) {
            $path = realpath($bdir . '/' . basename($dir));
            if ($path === false) {
                continue;
            }
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $path,
                    \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO
                )
            );
            foreach ($files as $name => $object) {
                if ($object->isFile()) {
                    $list[$dir . str_replace('\\', '/', substr($name, strlen($path)))] = md5_file($name);
                }
            }
        }
        foreach (['composer.json', 'composer.lock'] as $file) {
            $list[$file] = md5_file(
                realpath($bdir . '/' . $file) ?: throw new RuntimeException()
            );
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc') ?: throw new RuntimeException(''));
        $payload = [
            'files'   => $list,
            'created' => date('Y-m-d H:i:s'),
            'user'    => ''
        ];

        file_put_contents(
            $vdir . '/.version',
            implode(':', [
                $version,
                base64_encode($iv),
                openssl_encrypt(json_encode($payload, JSON_THROW_ON_ERROR), 'aes-256-cbc', md5($password), 0, $iv)
            ])
        );

        $this->pack($vdir . '/' . $version . '.dat');

        $hash = md5_file($vdir . '/' . $version . '.dat') ?: throw new RuntimeException();
        $signature = sodium_crypto_sign_detached(
            $hash,
            file_get_contents($vdir . '/priv.key') ?: throw new RuntimeException()
        );
        file_put_contents($vdir . '/' . $version . '.dat', "\n" . base64_encode($signature), FILE_APPEND);
    }
    public function versionDeploy(string $file): void
    {
        $bdir = $this->cnf->getString('BASEDIR');
        $vdir = $this->cnf->getString('STORAGE_VERSIONS');

        if (!is_file($vdir . '/pub.key')) {
            throw new RuntimeException('Missing key');
        }
        if (!$file || !is_file($file) || !is_readable($file)) {
            throw new RuntimeException('Invalid file');
        }

        $data = file_get_contents($file) ?: throw new RuntimeException();
        $npos = strrpos($data, "\n") ?: throw new RuntimeException();
        $hash = substr($data, $npos + 1);
        $data = substr($data, 0, $npos);
        if (
            !sodium_crypto_sign_verify_detached(
                base64_decode($hash) ?: throw new RuntimeException(),
                md5($data),
                file_get_contents($vdir . '/pub.key') ?: throw new RuntimeException()
            )
        ) {
            throw new RuntimeException('Invalid signature');
        }

        $zip = new \ZipArchive();
        $zip->open($file);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $item = $zip->statIndex($i);
            if ($item === false) {
                continue;
            }
            if (is_file($bdir . '/' . $item['name']) || is_dir($bdir . '/' . $item['name'])) {
                if (!is_writable($bdir . '/' . $item['name'])) {
                    throw new RuntimeException('Insufficient permissions for extract');
                }
            } else {
                $dir = dirname($bdir . '/' . $item['name']);
                while (!is_dir($dir)) {
                    $dir = dirname($dir);
                }
                if ($dir === '.' || !is_writable($dir)) {
                    throw new RuntimeException('Insufficient permissions for extract');
                }
            }
        }

        $migrations = $this->app->migrations();
        $this->pack(
            $vdir . '/version.bkp',
            [
                '.version.db' => implode("\n", array_filter($migrations->current()))
            ]
        );

        $zip->extractTo($bdir . '/');
        $zip->close();

        $this->permissions();
        $this->cacheClean();
        $this->cacheLangs();
        $this->cacheEnv();
        $migrations->up();
        $this->cacheSchema();
    }
    public function versionRevert(): void
    {
        $bdir = $this->cnf->getString('BASEDIR');
        $file = $this->cnf->getString('STORAGE_VERSIONS') . '/version.bkp';
        if (!is_file($file)) {
            throw new RuntimeException('No backup found');
        }
        $zip = new \ZipArchive();
        $zip->open($file);
        $zip->extractTo($bdir . '/');
        $zip->close();
        $migrations = $this->app->migrations();
        $this->permissions();
        $this->cacheClean();
        $this->cacheLangs();
        $this->cacheEnv();
        $migrations->to(array_filter(explode("\n", (file_get_contents($bdir . '/.version.db') ?: ''))));
        @unlink($bdir . '/.version.db');
        $this->cacheSchema();
    }
}
