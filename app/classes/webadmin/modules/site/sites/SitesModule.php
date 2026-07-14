<?php

declare(strict_types=1);

namespace webadmin\modules\site\sites;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDServiceInterface;
use webadmin\modules\common\crud\CRUDModule;
use vakata\di\DIContainer;

/**
 * @extends CRUDModule<\schema\SitesEntity,SitesService>
 */
class SitesModule extends CRUDModule
{
    public const string NAME = 'sites';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'world',
            'violet',
            'cms',
            'sites',
            CRUDController::class,
            namespace\SitesService::class
        );
    }
    public function canDelete(): bool
    {
        return false;
    }
    public function listingCallback(Table $table): Table
    {
        $table = parent::listingCallback($table);
        $table
            ->removeColumn('site')
            ->removeColumn('domains')
            ->removeColumn('disabled')
            ->removeColumn('dflt')
            ->removeColumn('tree');
        foreach ($table->getRows() as $v) {
            $operations = $v->getOperations();
            $operations = [
                'update' => $operations['update']
            ];
            $v->setOperations($operations);
            $entity = $v->getData();
            if ($entity->disabled) {
                $v->addClass("negative");
            }
            if (!$entity->disabled && $entity->dflt) {
                $v->addClass("positive");
            }
        }
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $form = parent::formCallback($form);
        $service = $this->getService();
        $form->removeField('site');
        $langs = $service->getAvailableLangs();
        $form->addField(
            new Field(
                'checkboxes',
                [ 'name' => 'langs' ],
                [ 'label' => $this->name . '.columns.langs', 'grid' => 4, 'values' => $langs ]
            )
        );
        $tree = $service->tree();
        $form->getField("domains")->setType("textarea");
        $form->getField("disabled")->setType("checkbox");
        $form->getField("dflt")->setType("checkbox");
        $form->getField("tree")->setType("tree")->setOptions([
            'label' => $this->name . '.columns.tree',
            'values' => $tree,
            'multiple' => false
        ]);
        $entity = $form->getContext('entity', null);
        if ($entity) {
            $form->populate($service->toArray($entity));
        }
        $form->populate($form->getContext('data'));
        return $form;
    }
}
