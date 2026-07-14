<?php

declare(strict_types=1);

namespace webadmin\modules\administration\log;

use webadmin\modules\common\crud\CRUDService;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\user\User;

/**
 * @extends CRUDService<\schema\LogEntity>
 */
class LogService extends CRUDService
{
    protected string $storage;

    public function __construct(LogModule $module, Config $config, DBInterface $db, User $user)
    {
        parent::__construct($module, $db, $user);
        $this->storage = $config->get('STORAGE_REQ');
    }
    public function listQuery(): TableQueryMapped
    {
        /** @var TableQueryMapped<\schema\LogEntity> */
        return parent::listQuery()
            ->sort('created', true)
            ->columns([ 'created', 'lvl', 'message', 'ip', 'usr_name' ]);
    }
    public function name(Entity $entity): string
    {
        return (string)$entity->id;
    }
    public function create(array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function update(mixed $id, array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    /**
     * @return array<int,array{usr:int,avatar_data:string,name:string}>
     */
    public function users(): array
    {
        return $this->db->rows(
            "SELECT usr, avatar_data, name FROM users ORDER BY name",
            []
        )
        ->toArray('usr');
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $arr = parent::toArray($entity, $relations);
        $time = strtotime($entity->created);
        if (!$time) {
            $time = time();
        }
        if ($arr['context']) {
            $arr['context'] = print_r(json_decode($arr['context'], true), true);
        }
        if (strlen($this->storage) && $this->storage !== 'DATABASE') {
            $path = rtrim($this->storage, '/') . '/' .
                date('Y', $time) . '/' . date('m', $time) .  '/' . date('d', $time);
            $uuid = trim(explode("\n", (explode('X-Request-UUID: ', $arr['response'] ?? '', 2)[1] ?? ''), 2)[0] ?? '');
            if (strpos($arr['request'] ?? '', '*** SKIPPED ***') && $uuid && is_file($path . '/' . $uuid . '.req')) {
                $arr['request'] = str_replace(
                    '*** SKIPPED ***',
                    file_get_contents($path . '/' . $uuid . '.req') ?: throw new \RuntimeException(),
                    $arr['request'] ?? ''
                );
            }
            if (strpos($arr['response'] ?? '', '*** SKIPPED ***') && $uuid && is_file($path . '/' . $uuid . '.res')) {
                $arr['response'] = str_replace(
                    '*** SKIPPED ***',
                    file_get_contents($path . '/' . $uuid . '.res') ?: throw new \RuntimeException(),
                    $arr['response'] ?? ''
                );
            }
        }
        return $arr;
    }
}
