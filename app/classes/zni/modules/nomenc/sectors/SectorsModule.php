<?php

declare(strict_types=1);

namespace zni\modules\nomenc\sectors;

use schema\NomSectorsEntity;
use vakata\di\DIContainer;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDModule<NomSectorsEntity, CRUDService<NomSectorsEntity>>
 */
class SectorsModule extends CRUDModule
{
    public const string NAME = 'sectors';

    public function __construct(DIContainer $container, string $slug = '')
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'warehouse',
            'teal',
            'nomenc',
            'nom_sectors',
            CRUDController::class,
            CRUDService::class
        );
    }
}
