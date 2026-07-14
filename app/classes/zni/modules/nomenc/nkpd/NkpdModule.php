<?php

declare(strict_types=1);

namespace zni\modules\nomenc\nkpd;

use schema\NomNkpdEntity;
use vakata\di\DIContainer;
use schema\NomContractTypesEntity;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDService;
use webadmin\modules\common\crud\CRUDController;

/**
 * @extends CRUDModule<NomNkpdEntity, CRUDService<NomNkpdEntity>>
 */
class NkpdModule extends CRUDModule
{
    public const string NAME = 'nkpd';

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
            'nom_nkpd',
            CRUDController::class,
            NkpdService::class
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
