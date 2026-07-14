<?php

declare(strict_types=1);

namespace webadmin\modules\common\ekatte;

use schema\MunicipalitiesEntity;
use vakata\di\DIContainer;
use vakata\http\Request;
use webadmin\api\APIProviderInterface;
use webadmin\components\html\Button;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDModule<MunicipalitiesEntity, CRUDService<MunicipalitiesEntity>>
 */
class MunicipalitiesModule extends CRUDModule implements APIProviderInterface
{
    public const string NAME = 'municipalities';

    public function __construct(DIContainer $container, string $slug = '')
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'map',
            'green',
            'other',
            'municipalities',
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
        $table->removeColumn('region');
        $table->addColumn((new TableColumn('regions.name')));

        $table->addOperation(
            (new Button('ekatte'))
                ->setLabel($this->name . '.operation.ekatte')
                ->setIcon('map marked alternate')
                ->setClass('green icon labeled button')
                ->setAttr('href', $req->getUrl()->getBasePath() . 'ekatte')
        );
        $table->addOperation(
            (new Button('regions'))
                ->setLabel($this->name . '.operation.regions')
                ->setIcon('map marker alternate')
                ->setClass('green icon labeled button')
                ->setAttr('href', $req->getUrl()->getBasePath() . 'regions')
        );

        foreach ($table->getRows() as $row) {
            $row->getOperation('cities')?->show();
        }

        return $table->setOrder([ 'code', 'name', 'name_en', 'regions.name', 'pos' ]);
    }
}
