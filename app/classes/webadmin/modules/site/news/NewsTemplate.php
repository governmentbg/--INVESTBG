<?php

declare(strict_types=1);

namespace webadmin\modules\site\news;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use vakata\intl\Intl;
use webadmin\modules\site\TemplateInterface;

class NewsTemplate implements TemplateInterface
{
    public function __construct(
        protected NewsService $service,
        protected Intl $intl
    ) {
    }
    public function getName(): string
    {
        return 'news';
    }
    public function getForm(array $data = [], array $context = []): Form
    {
        return (new Form())
            ->addField(
                new Field('text', ['name' => 'perpage'], ['label' => 'pages.templates.news.perpage'])
            )
            ->addField(
                (new Field('select', ['name' => 'tags'], ['label' => 'pages.templates.news.tags']))
                    ->setOption('values', [$this->intl->get('pages.templates.news.notag')] + $this->service->tags())
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
