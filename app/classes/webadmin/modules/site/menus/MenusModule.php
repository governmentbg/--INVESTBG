<?php

declare(strict_types=1);

namespace webadmin\modules\site\menus;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDServiceInterface;
use webadmin\modules\common\crud\CRUDModule;
use vakata\di\DIContainer;

/**
 * @extends CRUDModule<\schema\MenusEntity,MenusService>
 */
class MenusModule extends CRUDModule
{
    public const string NAME = 'menus';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'bars',
            'blue',
            'cms',
            'menus',
            CRUDController::class,
            namespace\MenusService::class
        );
    }
    public function canDelete(): bool
    {
        return false;
    }
    public function listingCallback(Table $table): Table
    {
        $service = $this->getService();
        $table = parent::listingCallback($table);
        $langs = $service->getLanguages();
        $table
            ->removeColumn('menu')
            ->removeColumn('site')
            ->removeColumn('items')
            ->getColumn('is_default')
                ->setMap(function (mixed $v): HTML {
                    return new HTML(
                        (int)$v ? '<i class="ui check icon"></i>' : ''
                    );
                });
        $table->getColumn('lang')
                ->setMap(function (mixed $v) use ($langs) {
                    return $langs[$v] ?? '';
                })->setFilter(
                    (new Form())
                        ->addField(new Field(
                            "select",
                            [ 'name' => 'lang' ],
                            [ 'label' => $this->name . '.filters.lang', 'values' => $service->getLanguages() ]
                        ))
                );
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $service = $this->getService();
        $form = parent::formCallback($form);
        $items = $service->items();
        $form
            ->removeField('menu')
            ->removeField('site')
            ->getField('is_default')
                ->setType('checkbox');
        $form
            ->getField('lang')
                ->setType('select')->setOption('values', $service->getLanguages())->setAttr('data-redraw', '1');
        $form
            ->getField('items')
                ->setType('json')
                ->setOption('reorder', true)
                ->setOption(
                    'form',
                    (new Form())
                        ->addField(
                            new Field('select', ['name' => 'type'], [ 'values' => $items, 'label' => 'menus.items'])
                        )
                        ->addField(new Field('text', ['name' => 'name'], [ 'label' => 'menus.text' ]))
                        ->addField(new Field('text', ['name' => 'href'], [ 'label' => 'menus.link' ]))
                        ->addField(new Field('text', ['name' => 'depth'], [ 'label' => 'menus.depth' ]))
                )
                ->setOption('min', 1);
        if ($form->hasValidator()) {
            $validator = $form->getValidator();
            $form->setValidator($validator->remove('site'));
        }
        return $form;
    }
}
