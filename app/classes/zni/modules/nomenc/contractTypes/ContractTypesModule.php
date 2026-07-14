<?php

declare(strict_types=1);

namespace zni\modules\nomenc\contractTypes;

use schema\NomContractTypesEntity;
use vakata\di\DIContainer;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDModule<NomContractTypesEntity, CRUDService<NomContractTypesEntity>>
 */
class ContractTypesModule extends CRUDModule
{
    public const string NAME = 'contractTypes';

    public function __construct(DIContainer $container, string $slug = '')
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'file contract',
            'teal',
            'nomenc',
            'nom_contract_types',
            CRUDController::class,
            CRUDService::class
        );
    }
}
