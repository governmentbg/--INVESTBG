<?php

declare(strict_types=1);

namespace zni\modules\nomenc\sectorActivities;

use schema\NomSectorActivitiesEntity;
use vakata\di\DIContainer;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;

/**
 * @extends CRUDModule<NomSectorActivitiesEntity, SectorActivitiesService>
 */
class SectorActivitiesModule extends CRUDModule
{
    public const string NAME = 'sectorActivities';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'boxes',
            'blue',
            'nomenc',
            'nom_sector_activities',
            CRUDController::class,
            SectorActivitiesService::class
        );
    }

    public function listingCallback(Table $table): Table
    {
        $table = parent::listingCallback($table);

        $table->removeColumn('sector');
        $table->addColumn(new TableColumn('nom_sectors.name'));

        return $table->setOrder([ 'name', 'nom_sectors.name', 'pos' ]);
    }

    public function formCallback(Form $form): Form
    {
        $form = parent::formCallback($form);

        $form->getField('sector')
            ->setType('select')
            ->setOption('values', [ '' => '' ] + $this->getService()->getSectors()->toArray());

        return $form;
    }
}
