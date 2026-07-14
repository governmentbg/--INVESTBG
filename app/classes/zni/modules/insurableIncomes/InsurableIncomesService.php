<?php

declare(strict_types=1);

namespace zni\modules\insurableIncomes;

use vakata\intl\Intl;
use vakata\user\User;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\schema\TableQueryMapped;
use zni\modules\nomenc\nomenc\NomencService;
use webadmin\modules\common\crud\CRUDServiceVersioned;
use zni\enums\LaborCategory;

/**
 * @extends CRUDServiceVersioned<\schema\InsurableIncomeEntity>
 */
class InsurableIncomesService extends CRUDServiceVersioned
{
    public function __construct(
        InsurableIncomesModule $module,
        DBInterface $db,
        User $user,
        protected Intl $intl,
        protected Config $config,
        protected NomencService $nomencService,
    ) {
        parent::__construct($module, $db, $user);
    }

    protected function entities(): TableQueryMapped
    {
        return parent::entities()
            ->order('from_date asc, to_date asc, category asc');
    }

    public function canCreate(): bool
    {
        $groups = $this->user->getGroups();
        $allowed = [
            $this->config->getInt('RESPONSIBLE_MIR'),
            $this->config->getInt('CHECKING_MIR_CONTRACT')
        ];

        foreach ($allowed as $gid) {
            if (array_key_exists($gid, $groups)) {
                return true;
            }
        }

        return false;
    }

    public function canUpdate(): bool
    {
        return $this->canCreate();
    }

    public function canDelete(): bool
    {
        return false;
    }

    public function laborCategoryOptions(): array
    {
        return LaborCategory::options();
    }
}
