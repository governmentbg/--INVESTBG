<?php

declare(strict_types=1);

namespace zni\modules\nomenc\kid;

use schema\NomKidEntity;
use vakata\di\DIContainer;
use schema\NomContractTypesEntity;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDService;
use webadmin\modules\common\crud\CRUDController;

/**
 * @extends CRUDModule<NomKidEntity, CRUDService<NomKidEntity>>
 */
class KidModule extends CRUDModule
{
    public const string NAME = 'kid';



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
            'nom_kid',
            CRUDController::class,
            KidService::class
        );
    }
    public function canRead(): bool
    {
        return true;
    }
    public function canCreate(): bool
    {
        return false;
    }
    public function canUpdate(): bool
    {
        return false;
    }
    public function canDelete(): bool
    {
        return false;
    }
}
