<?php

declare(strict_types=1);

namespace webadmin\modules\administration\modules;

use RuntimeException;
use vakata\cache\CacheInterface;
use vakata\database\DBInterface;
use webadmin\modules\ModuleInterface;

class ModulesService
{
    protected DBInterface $db;
    protected CacheInterface $cache;

    public function __construct(DBInterface $db, CacheInterface $cache)
    {
        $this->db = $db;
        $this->cache = $cache;
    }
    public function getModules(): array
    {
        $data = [];
        foreach ($this->db->rows("SELECT * FROM modules ORDER BY pos")->toArray() as $module) {
            if (
                !class_exists($module['classname']) ||
                !in_array(ModuleInterface::class, class_implements($module['classname']) ?: [])
            ) {
                continue;
            }
            $data[] = $module;
        }
        return $data;
    }
    public function setModules(array $modules): void
    {
        $this->db->begin();
        $settings = $this->db->all("SELECT name, settings FROM modules", [], 'name', true);
        $this->db->query("DELETE FROM modules");
        foreach ($modules as $pos => $module) {
            if (
                !class_exists($module['classname']) ||
                !in_array(ModuleInterface::class, class_implements($module['classname']) ?: [])
            ) {
                continue;
            }
            $this->db->query(
                "INSERT INTO modules (name, slug, classname, loaded, pos, settings) VALUES (??)",
                [
                    $module['name'],
                    $module['slug'],
                    $module['classname'],
                    $module['loaded'],
                    $pos,
                    $settings[$module['name']] ?? ''
                ]
            );
        }
        $this->db->commit();
        $this->cache->set(
            'modules',
            $this->db->all("SELECT name, slug, classname, settings FROM modules WHERE loaded = 1 ORDER BY pos, name")
        );
    }
}
