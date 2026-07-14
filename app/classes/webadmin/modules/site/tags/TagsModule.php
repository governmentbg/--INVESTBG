<?php

declare(strict_types=1);

namespace webadmin\modules\site\tags;

use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\Table;

/**
 * @extends CRUDModule<\schema\TagsEntity,TagsService>
 */
class TagsModule extends CRUDModule implements APIProviderInterface
{
    public const string NAME = 'tags';

    public function __construct(DIContainer $container, string $slug = '')
    {
        /** @psalm-suppress all */
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'tag',
            'yellow',
            'cms',
            'tags',
            CRUDController::class,
            TagsService::class
        );
    }
    public function canRead(): bool
    {
        return false;
    }
    public function onDashboard(): bool
    {
        return false;
    }
    public function inMenu(): bool
    {
        return false;
    }
    public function listingCallback(Table $table): Table
    {
        $service = $this->getService();
        $table = parent::listingCallback($table);
        $langs = $service->getLanguages();
        $table->removeColumn('tag');
        $table
            ->getColumn('lang')
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
        $form = parent::formCallback($form);
        $service = $this->getService();
        $form->removeField('tag');
        $form->getField('lang')->setType('select')->setOption('values', $service->getLanguages());
        return $form;
    }
}
