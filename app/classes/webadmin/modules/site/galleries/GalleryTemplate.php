<?php

declare(strict_types=1);

namespace webadmin\modules\site\galleries;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use vakata\intl\Intl;
use webadmin\modules\site\TemplateInterface;

class GalleryTemplate implements TemplateInterface
{
    public function __construct(
        protected GalleriesService $service,
        protected Intl $intl
    ) {
    }
    public function getName(): string
    {
        return 'gallery';
    }
    public function getForm(array $data = [], array $context = []): Form
    {
        return (new Form())
            ->addField(
                new Field('text', ['name' => 'perpage'], ['label' => 'pages.templates.gallery.perpage'])
            )
            ->addField(
                (new Field('select', ['name' => 'tags'], ['label' => 'pages.templates.gallery.tags']))
                    ->setOption('values', [$this->intl->get('pages.templates.gallery.notag')] + $this->service->tags())
            )
            ->populate($data);
    }
    public function getZones(): array
    {
        return [];
    }
    public function wildcard(): bool
    {
        return true;
    }
}
