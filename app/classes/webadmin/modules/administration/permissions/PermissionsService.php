<?php

declare(strict_types=1);

namespace webadmin\modules\administration\permissions;

use vakata\user\UserManagementInterface;
use ReflectionMethod;
use vakata\config\Config;
use vakata\di\DIInterface;
use webadmin\modules\ModulesContainer;
use webadmin\modules\PermissionsModuleInterface;
use webadmin\modules\VisualModuleInterface;

class PermissionsService
{
    protected DIInterface $di;
    protected UserManagementInterface $usrm;
    protected string $admins;
    /** @var array<string,array<string>> */
    protected array $permissions = [];

    public function __construct(DIInterface $di, UserManagementInterface $usrm, Config $config, ModulesContainer $mc)
    {
        $this->di = $di;
        $this->usrm = $usrm;
        $this->admins = (string)$config->get('GROUP_ADMINS');
        foreach ($mc as $module) {
            if ($module instanceof VisualModuleInterface) {
                $this->permissions[$module->getName()] = [];
            }
            if ($module instanceof PermissionsModuleInterface) {
                $this->permissions[$module->getName()] = $module->permissions();
            }
        }
    }

    public function getAvailablePermissions(): array
    {
        return $this->permissions;
    }
    public function getStoredPermissions(): array
    {
        return $this->usrm->permissions();
    }
    public function setPermissions(array $permissions): void
    {
        foreach ($this->usrm->permissions() as $permission) {
            if (!in_array($permission, $permissions)) {
                $this->usrm->deletePermission($permission);
            }
        }
        $admins = $this->usrm->getGroup($this->admins);
        foreach ($permissions as $permission) {
            if (!$this->usrm->permissionExists($permission)) {
                $this->usrm->addPermission($permission);
            }
            if (!$admins->hasPermission($permission)) {
                $admins->addPermission($permission);
            }
        }
        $this->usrm->saveGroup($admins);
    }
}
