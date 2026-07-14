<?php

declare(strict_types=1);

namespace webadmin\modules\site\templates;

use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDServiceInterface;
use webadmin\modules\common\crud\CRUDModule;

/**
 * @extends CRUDModule<\schema\TemplatesEntity,TemplatesService>
 */
class TemplatesModule extends CRUDModule implements APIProviderInterface
{
    public const string NAME = 'templates';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'clone outline',
            'teal',
            'cms',
            'templates',
            namespace\TemplatesController::class,
            namespace\TemplatesService::class,
            __DIR__ . '/views'
        );
    }
    public function canDelete(): bool
    {
        return false;
    }
    public function listingCallback(Table $table): Table
    {
        $table = parent::listingCallback($table);
        $service = $this->getService();
        $table
            ->removeColumn('template')
            ->removeColumn('widgets')
            ->removeColumn('zones')
            ->removeColumn('child_default')
            ->getColumn('is_default')
                ->setMap(function (mixed $v): HTML {
                    return new HTML(
                        (int)$v ? '<i class="ui check icon"></i>' : ''
                    );
                });
        foreach ($table->getRows() as $v) {
            $v->removeOperation('read');
            $v->removeOperation('delete');
        }
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $form = parent::formCallback($form);
        $service = $this->getService();
        $templates = $service->baseTemplates();
        $form->removeField('template');
        $form->getField('is_default')->setType('checkbox');
        $form->getField('zones')
            ->setAttr('data-serialize', 'zones')
            ->setType('checkboxes')
            ->setOption('values', ['main' => 'main']);
        $form->getField('widgets')
            ->setType('custom')
            ->setOption('view', 'templates::widgets')
            ->setOption('form', '')
            ->setOption('widgets', $service->widgets());
        $form->getField('base')
            ->setType('select')
            ->setAttr('data-redraw', '1')
            ->setOption('values', array_combine($templates, $templates));
        $form->getField('child_default')
            ->setType('select')
            ->setOption('values', ['' => '-- без --'] + $service->templates());
        if ($form->getContext('type') === 'create') {
            $data = $form->getContext('data');
            $zones = $service->template($data['base'] ?? array_keys($templates)[0])->getZones();
            $zones[] = 'main';
            $zones = array_unique($zones);
            if (isset($data['zones']) && is_string($data['zones'])) {
                $data['zones'] = json_decode($data['zones'], true);
            }
            if (!isset($data['zones'])) {
                $data['zones'] = [];
            }
            $form->getField('zones')
                ->setOption('values', array_combine($zones, $zones))
                ->setValue($data['zones']['zones'] ?? ['main']);
        }
        if ($form->getContext('type') === 'update') {
            $templates = $service->templates();
            $data = $form->getContext('data');
            $entity = $form->getContext('entity');
            $zones = $service->template($data['base'] ?? $entity->base ?? array_keys($templates)[0])->getZones();
            $zones[] = 'main';
            $zones = array_unique($zones);
            if (isset($data['zones']) && is_string($data['zones'])) {
                $data['zones'] = json_decode($data['zones'], true);
            }
            if (!isset($data['zones'])) {
                $data['zones'] = [];
            }
            $ezones = [];
            if (isset($entity->zones) && is_string($entity->zones)) {
                $ezones = json_decode($entity->zones, true);
            }
            $form->getField('zones')
                ->setOption('values', array_combine($zones, $zones))
                ->setValue($data['zones']['zones'] ?? $ezones ?? ['main']);
        }
        return $form;
    }
}
