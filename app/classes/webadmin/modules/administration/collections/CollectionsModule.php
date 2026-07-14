<?php

declare(strict_types=1);

namespace webadmin\modules\administration\collections;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDServiceInterface;
use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;
use webadmin\modules\PermissionsModuleInterface;

/**
 * @extends CRUDModule<\schema\CollectionsEntity,CollectionsService>
 */
class CollectionsModule extends CRUDModule implements PermissionsModuleInterface, APIProviderInterface
{
    public const string NAME = 'collections';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'copy',
            'yellow',
            'other',
            'collections',
            CRUDController::class,
            namespace\CollectionsService::class
        );
    }
    public function permissions(): array
    {
        return [ 'collections/master' ];
    }
    public function canRead(): bool
    {
        return false;
    }
    public function inMenu(): bool
    {
        return false;
    }
    public function onDashboard(): bool
    {
        return false;
    }
    public function listingCallback(Table $table): Table
    {
        $table
            ->removeColumn('collection')
            ->removeColumn('owner');
        $table
            ->getColumn('rw')
            ->setFilter(
                (new Form())
                    ->addField(
                        new Field(
                            'select',
                            ['name' => 'rw'],
                            ['values' => ['rw.no', 'rw.r', 'rw.rw'], 'translate' => true]
                        )
                    )
            )
            ->setMap(function (string|int $v) {
                return $v == 0 ? '-- без --' : ($v == 1 ? 'само преглед' : 'пълен');
            });
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $service = $this->getService();
        $form
            ->removeField('collection')
            ->removeField('owner');
        $form
            ->getField('rw')
                ->setType('select')
                ->setOption('translate', true)
                ->setOption('values', ['rw.no', 'rw.r', 'rw.rw']);
        $form
            ->addField(
                new Field(
                    'checkboxes',
                    ['name' => 'r'],
                    ['label' => 'collections.fields.r', 'values' => $service->groups(), 'grid' => 5 ]
                )
            );
        $form
            ->addField(
                new Field(
                    'checkboxes',
                    ['name' => 'w'],
                    ['label' => 'collections.fields.w', 'values' => $service->groups(), 'grid' => 5 ]
                )
            );
        if ($form->hasValidator()) {
            $validator = $form->getValidator();
            $form->setValidator($validator->remove('owner'));
        }
        $entity = $form->getContext('entity', null);
        if ($entity) {
            $form->populate($service->toArray($entity));
        }
        $form->populate($form->getContext('data', []));
        return $form;
    }
}
