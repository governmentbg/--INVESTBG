<?php

declare(strict_types=1);

namespace zni\modules\payments;

use vakata\database\DBInterface;
use vakata\database\schema\TableQueryMapped;
use vakata\user\User;
use webadmin\modules\common\crud\CRUDServiceInterface;
use webadmin\modules\common\crud\CRUDServiceVersioned;
use zni\modules\payments\PaymentModule;
use zni\permission\PermissionService;

/**
 * @extends CRUDServiceVersioned<\schema\PaymentsEntity>
 * @implements CRUDServiceInterface<\schema\PaymentsEntity>
 */
class PaymentService extends CRUDServiceVersioned implements CRUDServiceInterface
{
    public function __construct(
        PaymentModule $module,
        DBInterface $db,
        protected User $user,
        protected PermissionService $permissionService
    ) {
        parent::__construct($module, $db, $user);
    }

    public function entities(): TableQueryMapped
    {
        $entities = parent::entities()
            ->with('contracts')
            ->with('contracts.companies');

        if ($this->permissionService->isINV()) {
            return $entities->filter('contract', 0);
        }

        return $entities;
    }

    public function getContracts(): array
    {
        $contracts = $this->db->table('contracts');

        $arr = $contracts->sort('contracts.contract')
            ->collection(['contract', 'contract_date', 'companies.company_name'])
            ->map(function (array $row) {
                $row['name'] = $row['contract'] . ' / ' . $row['contract_date'] . ' / ' . $row['company_name'];
                return $row;
            });

        return $arr->toArray('contract', 'name');
    }
}
