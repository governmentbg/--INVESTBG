<?php

declare(strict_types=1);

namespace webadmin\modules\site\galleries;

use DateTime;
use RuntimeException;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\site\TemplateProviderInterface;
use schema\GalleriesEntity;
use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;
use webadmin\modules\site\TemplateInterface;

/**
 * @extends CRUDModule<GalleriesEntity,GalleriesService>
 */
class GalleriesModule extends CRUDModule implements TemplateProviderInterface, APIProviderInterface
{
    public const string NAME = 'galleries';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'image',
            'yellow',
            'cms',
            'galleries',
            CRUDController::class,
            namespace\GalleriesService::class
        );
    }
    public function canRead(): bool
    {
        return false;
    }
    public function listingCallback(Table $table): Table
    {
        $service = $this->getService();
        $table = parent::listingCallback($table);
        $langs = $service->getLanguages();
        $table
            ->removeColumn('gallery')
            ->removeColumn('visible_beg')
            ->removeColumn('visible_end')
            ->removeColumn('content')
            ->removeColumn('site')
            ->addColumn(
                (new TableColumn('tags.tag'))
                    ->setMap(function (mixed $k, GalleriesEntity $row) {
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
        };
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $service = $this->getService();
        $form = parent::formCallback($form);
        $form
            ->removeField('gallery')
            ->removeField('site');
        $form->getField('tags[]')->show();
        $form->addField(
            (new Field('images', ['name' => 'images'], ['label' => 'galleries.columns.images']))
                ->setOption('editor', true)
                ->setOption(
                    'form',
                    (new Form())
                        ->addField(new Field("text", ["name" => "title"], ["label" => "galleries.images.title"]))
                        ->addField(
                            new Field(
                                "textarea",
                                ["name" => "description"],
                                ["label" => "galleries.images.description"]
                            )
                        )
                )
        );
        $form->getField('content')->setType('richtext');
        $form->getField('hidden')->setType('checkbox');
        $form->getField('lang')->setType('select')->setOption('values', $service->getLanguages());
        $form->getField('visible_beg')->setValue(date('Y-m-d H:i:s'));
        $entity = $form->getContext('entity', null);
        if ($entity) {
            $form->populate($service->toArray($entity));
        }
        $form->populate($form->getContext('data', []));
        return $form;
    }
    public function getTemplates(): array
    {
        return [ 'gallery' ];
    }
    public function getTemplate(string $name): TemplateInterface
    {
        if ($name !== 'gallery') {
            throw new RuntimeException();
        }
        return $this->container->instance(GalleryTemplate::class, [ 'service' => $this->getService() ]);
    }
}
