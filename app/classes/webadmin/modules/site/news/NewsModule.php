<?php

declare(strict_types=1);

namespace webadmin\modules\site\news;

use DateTime;
use RuntimeException;
use schema\NewsEntity;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;
use webadmin\modules\site\TemplateInterface;
use webadmin\modules\site\TemplateProviderInterface;
use webadmin\modules\site\WidgetInterface;
use webadmin\modules\site\WidgetProviderInterface;

/**
 * @extends CRUDModule<\schema\NewsEntity,NewsService>
 */
class NewsModule extends CRUDModule implements
    TemplateProviderInterface,
    WidgetProviderInterface,
    APIProviderInterface
{
    public const string NAME = 'news';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'calendar',
            'olive',
            'cms',
            'news',
            CRUDController::class,
            namespace\NewsService::class
        );
    }
    public function canRead(): bool
    {
        return true;
    }
    public function listingCallback(Table $table): Table
    {
        $service = $this->getService();
        $table = parent::listingCallback($table);
        $langs = $service->getLanguages();
        $table
            ->removeColumn('news')
            ->removeColumn('visible_beg')
            ->removeColumn('visible_end')
            ->removeColumn('image')
            ->removeColumn('content')
            ->removeColumn('site')
            ->addColumn(
                (new TableColumn('tags.tag'))
                    ->setMap(function (mixed $k, NewsEntity $row) {
                        $tags = [];
                        foreach ($row->tags as $tag) {
                            /** @psalm-suppress PossiblyNullPropertyFetch */
                            $tags[] = '<span class="ui horizontal label">' . $tag->name . '</span>';
                        }
                        return new HTML(implode('', $tags));
                    })
                    ->setSortable(false)
                    ->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "multipleselect",
                                [ 'name' => 'tags.tag[]' ],
                                [ 'label' => $this->name . '.filters.tags', 'values' => $service->tags() ]
                            ))
                    )
            )
            ->getColumn('fordate')
                ->setMap(function (mixed $v) {
                    return new HTML(
                        '<i class="ui clock icon"></i> ' .
                        (($temp = DateTime::createFromFormat('Y-m-d', $v)) ?
                            $temp->format('d.m.Y') : ''
                        )
                    );
                });
        $table
            ->getColumn('hidden')
                ->setMap(function (mixed $v) {
                    return new HTML(
                        '<button
                            data-value="1"
                            data-field="hidden"
                            class="state-button ui mini basic icon button ' . ($v ? 'hide' : '') . '">
                                <i class="ui eye icon"></i></button>' .
                        '<button
                            data-value="0"
                            data-field="hidden"
                            class="state-button ui mini basic icon button ' . (!$v ? 'hide' : '') . '">
                                <i class="ui eye slash icon"></i></button>'
                    );
                });
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
        foreach ($table->getRows() as $v) {
            if ($v->hasOperation('update')) {
                $v->removeOperation('read');
            }
            $v->getOperation('tags')?->show();
        }
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $form = parent::formCallback($form);
        $service = $this->getService();
        $form
            ->removeField('news')
            ->removeField('site');
        $form->getField('tags[]')->show();
        $form->getField('image')->setType('image')->setOption('editor', true);
        $form->getField('content')->setType('richtext');
        $form->getField('hidden')->setType('checkbox');
        $form->getField('lang')->setType('select')->setOption('values', $service->getLanguages());
        if ($form->getContext('type') === 'create') {
            $form->getField('visible_beg')->setValue(date('Y-m-d H:i:s'));
        }
        return $form;
    }
    public function getTemplates(): array
    {
        return [ 'news' ];
    }
    public function getTemplate(string $name): TemplateInterface
    {
        if ($name !== 'news') {
            throw new RuntimeException();
        }
        return $this->container->instance(NewsTemplate::class, [ 'service' => $this->getService() ]);
    }
    public function getWidgets(): array
    {
        return [ 'news_top' ];
    }
    public function getWidget(string $name): WidgetInterface
    {
        if ($name !== 'news_top') {
            throw new RuntimeException();
        }
        return $this->container->instance(NewsTopWidget::class, [ 'service' => $this->getService() ]);
    }
}
