<?php

declare(strict_types=1);

namespace webadmin\modules\administration\groups;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDServiceInterface;
use vakata\di\DIContainer;

/**
 * @extends CRUDModule<\schema\GrpsEntity,GroupsService>
 */
class GroupsModule extends CRUDModule
{
    public const string NAME = 'groups';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'users',
            'olive',
            'settings',
            'grps',
            CRUDController::class,
            namespace\GroupsService::class,
            __DIR__ . '/views'
        );
    }

    public function canRead(): bool
    {
        return false;
    }
    public function canDelete(): bool
    {
        return false;
    }
    public function listingCallback(Table $table): Table
    {
        $table
            ->removeColumn('grp')
            ->removeColumn('created');
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $service = $this->getService();
        $perms = $service->getStoredPermissions();
        $form
            ->removeField('grp')
            ->removeField('created');
        $modules = [];
        $additional = [];
        foreach ($perms as $v) {
            if (strpos($v, '/') || strpos($v, '.')) {
                $additional[$v] = 'permission.' . $v;
            } else {
                $modules[$v] = $v . '.title';
            }
        }
        $form->addField(
            new Field(
                'checkboxes',
                [ 'name' => 'permissions' ],
                [
                    'label' => $this->name . '.columns.permissions',
                    'values' => $modules,
                    'translate' => true
                ]
            )
        );
        $form->addField(
            new Field(
                'checkboxes',
                [ 'name' => 'additional' ],
                [
                    'label' => $this->name . '.columns.additional',
                    'values' => $additional,
                    'translate' => true
                ]
            )
        );
        if ($form->hasValidator()) {
            $validator = $form->getValidator();
            $form->setValidator($validator->remove('created'));
        }
        $entity = $form->getContext('entity', null);
        if ($entity) {
            $form->populate($service->toArray($entity));
        }
        $form->populate($form->getContext('data', []));
        return $form;
    }
}
