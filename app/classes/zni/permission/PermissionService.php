<?php

declare(strict_types=1);

namespace zni\permission;

use vakata\intl\Intl;
use vakata\user\User;
use vakata\config\Config;
use vakata\database\DBInterface;
use schema\NomCertificateTypesEntity;
use webadmin\modules\ModulesContainer;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDService<NomCertificateTypesEntity>
 */
class PermissionService extends CRUDService
{
    public function __construct(
        PermissionModule $module,
        DBInterface $db,
        User $user,
        protected ModulesContainer $mc,
        protected Intl $intl,
        protected Config $config
    ) {
        parent::__construct($module, $db, $user);
    }
    public function isMIR(): bool
    {
        $MIRGroups = [
            $this->config->getInt('MASTER_MIR'),
            $this->config->getInt('RESPONSIBLE_MIR'),
            $this->config->getInt('CHECKING_MIR'),
            $this->config->getInt('ADMIN_MIR'),
            $this->config->getInt('CHECKING_MIR_CONTRACT'),
        ];

        $userGroups = array_keys($this->user->getGroups());
        return (bool) array_intersect($MIRGroups, $userGroups);
    }

    public function isINV(): bool
    {
        $INVGroups = [
            $this->config->getInt('ADMIN_INV'),
            $this->config->getInt('NORMAL_INV'),
        ];

        $userGroups = array_keys($this->user->getGroups());

        return (bool) array_intersect($INVGroups, $userGroups);
    }
}
