<?php

declare(strict_types=1);

namespace webadmin\modules\administration\collections;

use webadmin\modules\common\crud\CRUDService;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;

/**
 * @extends CRUDService<\schema\CollectionsEntity>
 */
class CollectionsService extends CRUDService
{
    protected function entities(): TableQueryMapped
    {
        $repository = $this->db->entities(\schema\CollectionsEntity::class);
        if (!$this->user->hasPermission('collections/master')) {
            $repository->filter('owner', $this->user->getID());
        }
        return $repository;
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $arr = parent::toArray($entity, $relations);
        $arr['g'] =  $this->db->rows(
            "SELECT grp, rw FROM collection_groups WHERE collection = ?",
            [$entity->collection]
        )
            ->toArray('grp', 'rw');
        $arr['r'] = [];
        $arr['w'] = [];
        foreach ($arr['g'] as $grp => $rw) {
            if ($rw == 2) {
                $arr['w'][] = $grp;
            }
            if ($rw == 1) {
                $arr['r'][] = $grp;
            }
        }
        return $arr;
    }
    public function create(array $data = []): Entity
    {
        $data['owner'] = $this->user->getID();
        $entity = parent::create($data);
        $g = [];
        if (!$data['r']) {
            $data['r'] = [];
        }
        if (!$data['w']) {
            $data['w'] = [];
        }
        foreach ($data['r'] ?? [] as $i) {
            $g[$i] = 1;
        }
        foreach ($data['w'] ?? [] as $i) {
            $g[$i] = 2;
        }
        $groups = $this->groups();
        foreach ($g as $i => $rw) {
            if (isset($groups[$i])) {
                $this->db->query(
                    "INSERT INTO collection_groups (collection, grp, rw) VALUES (??)",
                    [ $entity->collection, $i, $rw ]
                );
            }
        }
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        $entity = $this->read($id);
        $data['owner'] = $entity->owner;
        parent::update($id, $data);
        $entity = $this->read($id);
        $g = [];
        if (!$data['r']) {
            $data['r'] = [];
        }
        if (!$data['w']) {
            $data['w'] = [];
        }
        foreach ($data['r'] as $i) {
            $g[(int)$i] = 1;
        }
        foreach ($data['w'] as $i) {
            $g[(int)$i] = 2;
        }

        $groups = $this->groups();
        $eg = $this->toArray($entity)['g'];
        foreach (array_keys($eg) as $i) {
            /** @psalm-suppress InvalidArrayOffset */
            if (isset($groups[$i])) {
                /** @psalm-suppress InvalidArrayOffset */
                if (!isset($g[$i])) {
                    $this->db->query(
                        "DELETE FROM collection_groups WHERE collection = ? AND grp = ?",
                        [ $entity->collection, $i ]
                    );
                } else {
                    $this->db->query(
                        "UPDATE collection_groups SET rw = ? WHERE collection = ? AND grp = ?",
                        [ $g[$i], $entity->collection, $i ]
                    );
                }
            }
        }
        foreach ($g as $i => $rw) {
            if (isset($groups[$i]) && !isset($eg[$i])) {
                $this->db->query(
                    "INSERT INTO collection_groups (collection, grp, rw) VALUES (??)",
                    [ $entity->collection, $i, $rw ]
                );
            }
        }
        return $entity;
    }
    public function delete(mixed $id): void
    {
        $entity = $this->read($id);
        $this->db->query("DELETE FROM upload_collections WHERE collection = ?", $entity->collection);
        $this->db->query("DELETE FROM collection_groups WHERE collection = ?", $entity->collection);
        parent::delete($id);
    }
    /**
     * @return array<int,string>
     */
    public function groups(): array
    {
        $grps = array_keys($this->user->getGroups());
        $grps[] = 0;
        $temp = $this->user->hasPermission('collections/master') ?
            $this->db->rows("SELECT grp, name FROM grps ORDER BY name") :
            $this->db->rows("SELECT grp, name FROM grps WHERE grp IN(??) ORDER BY name", [$grps]);
        return $temp
            ->toArray('grp', 'name');
    }
}
