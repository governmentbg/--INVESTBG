<?php

declare(strict_types=1);

namespace webadmin\modules\common\ekatte;

use schema\CitiesEntity;
use vakata\di\DIContainer;
use vakata\http\Request;
use vakata\intl\Intl;
use webadmin\api\APIProviderInterface;
use webadmin\components\html\Button;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;

/**
 * @extends CRUDModule<CitiesEntity, EkatteService>
 */
class EkatteModule extends CRUDModule implements APIProviderInterface
{
    public const string NAME = 'ekatte';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'map marked alternate',
            'green',
            'other',
            'cities',
            CRUDController::class,
            EkatteService::class,
            __DIR__ . '/views'
        );
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
        /** @var Intl $intl */
        $intl = $this->container->get(Intl::class);
        /** @var Request $req */
        $req = $this->container->get(Request::class);

        $table = parent::listingCallback($table);

        $table->removeColumn('municipality');
        $table->removeColumn('name_en');
        $table->removeColumn('parent');

        $table->addColumn(new TableColumn('city'));
        $table->addColumn(
            (new TableColumn('municipalities.name'))
                ->setFilter(
                    (new Form())
                        ->addField(new Field(
                            "multipleselect",
                            [ 'name' => 'municipality[]' ],
                            [
                                'label' => $this->name . '.filters.municipality',
                                'values' => $this->getService()
                                    ->getMunicipalities()
                                    ->pluck('name')
                                    ->toArray()
                            ]
                        ))
                )
        );
        $table->addColumn(new TableColumn('parent_cities.name'));
        $table->addColumn(
            (new TableColumn('region'))
                ->setMap(fn (mixed $value, CitiesEntity $entity): string => $entity->municipalities->regions->name)
                ->setFilter(
                    (new Form())
                        ->addField(new Field(
                            "multipleselect",
                            [ 'name' => 'municipalities.region[]' ],
                            [
                                'label' => $this->name . '.filters.region',
                                'values' => $this->getService()
                                    ->getRegions()
                                    ->pluck('name')
                                    ->toArray()
                            ]
                        ))
                )
        );

        $table->getColumn('type')
            ->setMap(fn (mixed $value): string => $intl->get(CityType::from($value)->label()))
            ->setFilter(
                (new Form())
                    ->addField(new Field(
                        "multipleselect",
                        [ 'name' => 'type[]' ],
                        [ 'label' => $this->name . '.filters.type', 'values' => CityType::labels(), 'translate' => 1 ]
                    ))
            );

        foreach ($table->getRows() as $row) {
            $row->removeOperation('read');
        }

        $table->addOperation(
            (new Button('municipalities'))
                ->setLabel($this->name . '.operation.municipalities')
                ->setIcon('map')
                ->setClass('green icon labeled button')
                ->setAttr('href', $req->getUrl()->getBasePath() . 'municipalities')
        );

        $table->addOperation(
            (new Button('regions'))
                ->setLabel($this->name . '.operation.regions')
                ->setIcon('map marker alternate')
                ->setClass('green icon labeled button')
                ->setAttr('href', $req->getUrl()->getBasePath() . 'regions')
        );

        return $table
            ->setOrder([ 'city', 'name', 'region', 'municipalities.name', 'type', 'parent_cities.name', 'pos' ]);
    }
}
