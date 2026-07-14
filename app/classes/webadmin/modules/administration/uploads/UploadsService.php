<?php

declare(strict_types=1);

namespace webadmin\modules\administration\uploads;

use schema\CollectionsEntity;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDServiceInterface;
use webadmin\modules\common\crud\CRUDService;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\files\File;
use base\components\files\Files;
use vakata\user\User;
use webadmin\modules\common\crud\CRUDNotFoundException;

/**
 * @extends CRUDService<\schema\UploadsEntity>
 */
class UploadsService extends CRUDService
{
    protected Files $files;
    protected string $tmp;

    public function __construct(
        UploadsModule $module,
        Files $files,
        Config $config,
        DBInterface $db,
        User $user
    ) {
        parent::__construct($module, $db, $user);
        $this->files = $files;
        $this->tmp = $config->getString('STORAGE_TMP');
    }
    protected function entities(): TableQueryMapped
    {
        $repository = $this->db->entities(\schema\UploadsEntity::class)
            ->with('users')
            ->with('collections');
        if (!$this->isMaster()) {
            $repository->any([
                ['users.usr', $this->user->getID()],
                ['collections.owner', $this->user->getID()],
                ['collections.collection', array_merge(['' => 0], array_keys($this->getCollections()))]
            ]);
        }
        return $repository;
    }
    public function listQuery(): TableQueryMapped
    {
        return parent::listQuery()
            ->sort('uploaded', true)
            ->limitOnMainTable(true)
            ->columns([ 'name', 'bytesize', 'uploaded', 'collections.name', 'users.name' ]);
    }

    public function create(array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $arr = parent::toArray($entity, $relations);
        $arr['colls'] = $entity->collections
            ->clone()
            ->map(function (CollectionsEntity $v): int {
                return $v->collection;
            })
            ->toArray();
        return $arr;
    }
    public function read(mixed $id): Entity
    {
        /** @var \schema\UploadsEntity $entity */
        $entity = $this->entities()
            ->columns(array_filter(
                $this->table->getColumns(),
                function ($v) {
                    return $v !== 'data';
                }
            ))
            ->find($id);
        if (!$entity) {
            throw new CRUDNotFoundException('Record not found');
        }
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        if ($this->isMaster()) {
            if (isset($data['temp']) && $data['temp'] && is_file($this->tmp . '/' . $data['temp'])) {
                $file = $this->files->get((string)$id['id']);
                $data['hash'] = md5_file($this->tmp . '/' . $data['temp']);
                $data['bytesize'] = filesize($this->tmp . '/' . $data['temp']);
                $this->files->storage()->set($file, fopen($this->tmp . '/' . $data['temp'], 'r'));
                unlink($this->tmp . '/' . $data['temp']);
                if (is_file($this->tmp . '/' . $data['temp'] . '.settings')) {
                    unlink($this->tmp . '/' . $data['temp'] . '.settings');
                }
            }
            parent::update($id, $data);
        }
        $entity = $this->read($id);
        $arr = $this->toArray($entity);
        $colls = $data['colls'] ?? [];
        $writeable = $this->getCollections(true);
        foreach (array_keys($writeable) as $c) {
            if (!in_array($c, $colls) && in_array($c, $arr['colls'])) {
                $this->db->query(
                    "DELETE FROM upload_collections WHERE upload = ? AND collection = ?",
                    [ $id['id'], $c ]
                );
            }
            if (in_array($c, $colls) && !in_array($c, $arr['colls'])) {
                $this->db->query(
                    "INSERT INTO upload_collections (upload, collection) VALUES (?, ?)",
                    [ $id['id'], $c ]
                );
            }
        }
        // $this->db->query("DELETE FROM upload_collections WHERE upload = ?", $id['id']);
        // foreach ($data['colls'] ?? [] as $coll) {
        //     $this->db->query(
        //         "INSERT INTO upload_collections (upload, collection) VALUES (?, ?)",
        //         [ $id['id'], $coll ]
        //     );
        // }
        return $entity;
    }
    public function isMaster(): bool
    {
        return $this->user->hasPermission('uploads/master');
    }
    public function getCollections(bool $writeable = false): array
    {
        $temp = $this->isMaster() ?
            $this->db->rows(
                "SELECT c.collection, c.name, c.owner, u.name usr
                 FROM collections c
                 JOIN users u ON c.owner = u.usr
                 ORDER BY c.owner = 1 DESC, c.name"
            ) :
            $this->db->rows(
                "SELECT c.collection, c.name, c.owner, u.name usr
                 FROM collections c
                 JOIN users u ON c.owner = u.usr
                 LEFT JOIN collection_groups cg ON c.collection = cg.collection
                 WHERE c.rw > ? OR c.owner = ? OR (cg.grp IN (??) AND cg.rw > ?)
                 ORDER BY c.owner = 1 DESC, c.name",
                [
                    $writeable ? 1 : 0,
                    $this->user->getID(),
                    array_merge(['' => 0], array_keys($this->user->getGroups())),
                    $writeable ? 1 : 0,
                ]
            );
        return $temp
            ->pluckKey('collection')
            ->map(fn(array $v): string => ($v['owner'] == 1 ? '' : $v['usr'] . ': ') . $v['name'])
            ->toArray();
    }
    public function getUser(): array
    {
        return [ 'id' => $this->user->getID(), 'name' => $this->user->name ];
    }
    public function getFile(string $id): File
    {
        try {
            return $this->files->get($id);
        } catch (\Throwable $e) {
            throw new CRUDNotFoundException();
        }
    }
    public function getFileLink(string $id, array $query = []): string
    {
        try {
            return $this->files->toLink($this->files->get($id), $query);
        } catch (\Throwable $e) {
            throw new CRUDNotFoundException();
        }
    }
}
