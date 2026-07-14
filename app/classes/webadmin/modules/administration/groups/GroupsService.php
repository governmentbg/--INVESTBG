<?php

declare(strict_types=1);

namespace webadmin\modules\administration\groups;

use vakata\user\UserManagementInterface;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDServiceVersioned;
use vakata\database\DBInterface;
use vakata\user\User;
use vakata\user\Group;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\di\DIInterface;
use webadmin\modules\ModulesContainer;
use webadmin\modules\PermissionsModuleInterface;
use webadmin\modules\VisualModuleInterface;

/**
 * @extends CRUDServiceVersioned<\schema\GrpsEntity>
 */
class GroupsService extends CRUDServiceVersioned
{
    protected UserManagementInterface $usrm;
    protected DIInterface $di;
    /**
     * @var array<string>
     */
    protected array $permissions = [];

    public function __construct(
        GroupsModule $module,
        DBInterface $db,
        User $user,
        UserManagementInterface $usrm,
        DIInterface $di,
        ModulesContainer $mc
    ) {
        $this->usrm = $usrm;
        $this->di = $di;
        foreach ($mc as $m) {
            if ($m instanceof VisualModuleInterface) {
                $this->permissions[] = $m->getName();
            }
            if ($m instanceof PermissionsModuleInterface) {
                $this->permissions = array_merge($this->permissions, $m->permissions());
            }
        }
        parent::__construct($module, $db, $user);
    }
    protected function entities(): TableQueryMapped
    {
        return $this->db->entities(\schema\GrpsEntity::class);
    }
    public function listQuery(): TableQueryMapped
    {
        /** @var TableQueryMapped<\schema\GrpsEntity> */
        return parent::listQuery()->columns(['name']);
    }
    /**
     * @return array<string>
     */
    public function getStoredPermissions(): array
    {
        $stored = $this->usrm->permissions();
        $rslt = [];
        foreach ($this->permissions as $v) {
            if (in_array($v, $stored)) {
                $rslt[] = $v;
            }
        }
        return $rslt;
    }

    public function create(array $data = []): Entity
    {
        if ($this->usrm->groupExists((string)$data['name'])) {
            throw new CRUDException('modules.groups.groupalreadyexists');
        }
        $group = new Group('', $data['name'] ?? '');
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            foreach ($data['permissions'] as $permission) {
                $group->addPermission($permission);
            }
        }
        if (isset($data['additional']) && is_array($data['additional'])) {
            foreach ($data['additional'] as $permission) {
                $group->addPermission($permission);
            }
        }
        $this->usrm->saveGroup($group);
        $entity = $this->read($group->getID());
        $this->version($entity, 0, true);
        return $entity;
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $arr = parent::toArray($entity, $relations);
        $arr['permissions'] = $arr['additional'] = $this->usrm->getGroup((string)$entity->grp)->getPermissions();
        return $arr;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        $group = $this->usrm->getGroup((string)($id['grp'] ?? ''));
        $group->setName($data['name'] ?? '');
        if (!isset($data['permissions']) || !is_array($data['permissions'])) {
            $data['permissions'] = [];
        }
        if (!isset($data['additional']) || !is_array($data['additional'])) {
            $data['additional'] = [];
        }
        foreach ($group->getPermissions() as $permission) {
            if (!in_array($permission, $data['permissions']) && !in_array($permission, $data['additional'])) {
                $group->deletePermission($permission);
            }
        }
        foreach ($data['permissions'] as $permission) {
            if (!$group->hasPermission($permission)) {
                $group->addPermission($permission);
            }
        }
        foreach ($data['additional'] as $permission) {
            if (!$group->hasPermission($permission)) {
                $group->addPermission($permission);
            }
        }
        $this->usrm->saveGroup($group);
        $entity = $this->read($group->getID());
        $this->version($entity, 1, true);
        return $entity;
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
}
