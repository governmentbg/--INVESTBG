<?php

declare(strict_types=1);

namespace webadmin\modules\administration\config;

use vakata\cache\CacheInterface;
use vakata\config\Config;
use vakata\database\DBInterface;

class ConfigService
{
    protected DBInterface $db;
    protected Config $config;
    protected CacheInterface $cache;

    public function __construct(DBInterface $db, Config $config, CacheInterface $cache)
    {
        $this->db = $db;
        $this->config = $config;
        $this->cache = $cache;
    }
    public function writable(): bool
    {
        return $this->config->get('DB_CONFIG');
    }
    /**
     * @return array<string,array{value:scalar,db:string,override:bool}>
     */
    public function getConfig(): array
    {
        $locked = [
            'CLI',
            'BASEDIR',
            'APPNAME',
            'APPNAME_CLEAN',
            'TIMEZONE',
            'DATABASE',
            'CACHE',
            'STORAGE_CACHE',
            'ENVPARSE',
            'DB_CONFIG',
            'SIGNATUREKEY',
            'ENCRYPTIONKEY',
            'PASSWORDKEY',
            'CONFIGFILE',
            'VERSION'
        ];
        $dbcnf = $this->db->rows("SELECT k, v FROM config")
            ->toArray('k', 'v');
        $temp = [];
        foreach ($this->config->toArray() as $k => $v) {
            if (in_array($k, $locked)) {
                continue;
            }
            if (strpos($k, 'FEATURE_') === 0) {
                continue;
            }
            if (gettype($v) == 'boolean') {
                $v = $v ? 'true' : 'false';
            }
            $temp[$k] = [ 'value' => $v, 'db' => $dbcnf[$k] ?? '', 'override' => isset($dbcnf[$k]) ];
        }
        return $temp;
    }
    /**
     * @param array<string,array{override:bool,db:scalar}> $config
     * @return void
     */
    public function setConfig(array $config): void
    {
        $this->db->begin();
        $this->db->query("DELETE FROM config");
        foreach ($config as $k => $v) {
            if (is_array($v) && isset($v['override']) && (int)$v['override']) {
                $this->db->query("INSERT INTO config (k, v) VALUES (?, ?)", [ $k, $v['db'] ]);
            }
        }
        $this->db->commit();
        $this->cache->set(
            'config',
            $this->db->rows("SELECT k, v FROM config")->toArray('k', 'v')
        );
    }
}
