<?php

declare(strict_types=1);

namespace webadmin\modules\administration\settings;

use vakata\database\DBInterface;
use webadmin\Jobs;
use vakata\cache\CacheInterface;
use vakata\config\Config;

class SettingsService
{
    protected DBInterface $db;
    protected Jobs $jobs;
    protected Config $config;
    protected CacheInterface $cache;

    public function __construct(DBInterface $db, Config $config, CacheInterface $cache, Jobs $jobs)
    {
        $this->db = $db;
        $this->jobs = $jobs;
        $this->config = $config;
        $this->cache = $cache;
    }

    public function status(): array
    {
        $versions = [];
        $path = $this->config->get('BASEDIR') . '/vendor/composer/installed.php';
        if (is_file($path)) {
            $temp = include $path;
            if ($temp) {
                foreach ($temp['versions'] as $k => $v) {
                    if (!isset($v['pretty_version'])) {
                        continue;
                    }
                    $versions[$k] = $v['pretty_version'];
                }
            }
        }
        $core = [];
        $core['os'] = php_uname();
        $core['php'] = phpversion();
        $core['ext'] = implode(', ', get_loaded_extensions());
        $core['db'] = strtoupper($this->db->driverName());
        $core['cache'] = $this->config->getString('CACHE');
        if (strpos($this->config->getString('DATABASE'), 'mysql') === 0) {
            $core['db'] .= ': ' . $this->db->one("SELECT VERSION()");
        }
        if (strpos($this->config->getString('DATABASE'), 'postgre') === 0) {
            $core['db'] .= ': ' . $this->db->one("SELECT VERSION()");
        }
        if (strpos($this->config->getString('DATABASE'), 'oracle') === 0) {
            $core['db'] .= ': ' . $this->db->one('SELECT * FROM v$version WHERE banner LIKE \'Oracle%\'');
        }
        return [
            'writable'    => $this->writeable(),
            'version'     => $this->config->get('VERSION', ''),
            'core'        => $core,
            'versions'    => $versions,
            'debug'       => $this->config->get('DEBUG'),
            'maintenance' => $this->config->get('MIDDLEWARE_MAINTENANCE'),
            'push'        => $this->config->get('PUSH_NOTIFICATIONS') && $this->config->get('MIDDLEWARE_PUSH'),
            'ids'         => $this->config->get('MIDDLEWARE_IDS'),
            'ratelimit'   => $this->config->get('MIDDLEWARE_RATELIMIT'),
            'cors'        => $this->config->get('MIDDLEWARE_CORS'),
            'csrf'        => $this->config->get('MIDDLEWARE_CSRF'),
            'csp'         => $this->config->get('MIDDLEWARE_CSP'),
            'fp'          => $this->config->get('MIDDLEWARE_FP'),
            'pp'          => $this->config->get('MIDDLEWARE_PP'),
        ];
    }
    protected function writeable(): bool
    {
        return $this->config->get('DB_CONFIG');
    }

    public function clearCache(): void
    {
        $this->jobs->cacheClean();
        $this->jobs->cachePublic();
    }
    public function cacheLangs(): void
    {
        $this->jobs->cacheLangs();
    }
    public function cacheEnv(): void
    {
        $this->updateConfig('ENVCACHE', 'true');
        $this->jobs->cacheEnv();
    }

    protected function updateConfig(string $key, string $value): void
    {
        if ($this->writeable()) {
            $this->db->begin();
            $this->db->query("DELETE FROM config WHERE k = ?", $key);
            $this->db->query("INSERT INTO config (k, v) VALUES (?, ?)", [ $key, $value ]);
            $this->db->commit();
            $this->cache->set(
                'config',
                $this->db->rows("SELECT k, v FROM config")
                    ->toArray('k', 'v')
            );
        }
    }

    public function toggle(string $key): void
    {
        $status = $this->status();
        if (!in_array($key, $status)) {
            return;
        }
        switch ($key) {
            case 'ids':
                $this->updateConfig('MIDDLEWARE_IDS', $status[$key] ? 'false' : 'true');
                break;
            case 'ratelimit':
                $this->updateConfig('MIDDLEWARE_RATELIMIT', $status[$key] ? 'false' : 'true');
                break;
            case 'cors':
                $this->updateConfig('MIDDLEWARE_CORS', $status[$key] ? 'false' : 'true');
                break;
            case 'csrf':
                $this->updateConfig('MIDDLEWARE_CSRF', $status[$key] ? 'false' : 'true');
                break;
            case 'csp':
                $this->updateConfig('MIDDLEWARE_CSP', $status[$key] ? 'false' : 'true');
                break;
            case 'fp':
                $this->updateConfig('MIDDLEWARE_FP', $status[$key] ? 'false' : 'true');
                break;
            case 'pp':
                $this->updateConfig('MIDDLEWARE_PP', $status[$key] ? 'false' : 'true');
                break;
            case 'push':
                $val = !$status[$key];
                $this->updateConfig('PUSH_NOTIFICATIONS', $val ? 'true' : 'false');
                $this->updateConfig('MIDDLEWARE_PUSH', $val ? 'true' : 'false');
                break;
            default:
                $this->updateConfig(strtoupper($key), $status[$key] ? 'false' : 'true');
                break;
        }
    }
    public function setDebug(bool $value): void
    {
        $this->updateConfig('DEBUG', $value ? 'true' : 'false');
    }
    public function setMaintenance(bool $value): void
    {
        $this->updateConfig('MIDDLEWARE_MAINTENANCE', $value ? 'true' : 'false');
    }

    public function listFiles(): array
    {
        $json = [];
        foreach (['app', 'public', 'scripts'] as $dir) {
            $path = realpath($this->config->get('BASEDIR') . '/' . basename($dir));
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
                    $json[$dir . str_replace('\\', '/', substr($name, strlen($path)))] = md5_file($name);
                }
            }
        }
        foreach (['composer.json', 'composer.lock'] as $file) {
            $json[$file] = md5_file(
                realpath($this->config->get('BASEDIR') . '/' . $file) ?: throw new \RuntimeException()
            );
        }
        return $json;
    }
}
