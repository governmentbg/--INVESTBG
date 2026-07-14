<?php

declare(strict_types=1);

namespace webadmin\modules\common\ekatte;

use schema\RegionsEntity;
use vakata\di\DIContainer;
use vakata\http\Request;
use webadmin\api\APIProviderInterface;
use webadmin\components\html\Button;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDModule<RegionsEntity, CRUDService<RegionsEntity>>
 */
class RegionsModule extends CRUDModule implements APIProviderInterface
{
    public const string NAME = 'regions';

    public function __construct(DIContainer $container, string $slug = '')
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'map marker alternate',
            'green',
            'other',
            'regions',
            CRUDController::class,
            CRUDService::class,
            __DIR__ . '/views'
        );
    }

    public function inMenu(): bool
    {
        return false;
    }

    public function onDashboard(): bool
    {
        return false;
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

    public function listingCallback(Table $table): Table
    {
        /** @var Request $req */
        $req = $this->container->get(Request::class);

        $table = parent::listingCallback($table);
        $table->addOperation(
            (new Button('ekatte'))
                ->setLabel($this->name . '.operation.ekatte')
                ->setIcon('map marked alternate')
                ->setClass('green icon labeled button')
                ->setAttr('href', $req->getUrl()->getBasePath() . 'ekatte')
        );
        $table->addOperation(
            (new Button('municipalities'))
                ->setLabel($this->name . '.operation.municipalities')
                ->setIcon('map')
                ->setClass('green icon labeled button')
                ->setAttr('href', $req->getUrl()->getBasePath() . 'municipalities')
        );

        foreach ($table->getRows() as $row) {
            $row->getOperation('municipalities')?->show();
        }

        return $table;
    }
}
