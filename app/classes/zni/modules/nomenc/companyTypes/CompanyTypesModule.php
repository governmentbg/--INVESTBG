<?php

declare(strict_types=1);

namespace zni\modules\nomenc\companyTypes;

use schema\NomCompanyTypesEntity;
use vakata\di\DIContainer;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDModule<NomCompanyTypesEntity, CRUDService<NomCompanyTypesEntity>>
 */
class CompanyTypesModule extends CRUDModule
{
    public const string NAME = 'companyTypes';

    public function __construct(DIContainer $container, string $slug = '')
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'briefcase',
            'green',
            'nomenc',
            'nom_company_types',
            CRUDController::class,
            CRUDService::class
        );
    }
}
