<?php

declare(strict_types=1);

namespace webadmin\modules\site\news;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use vakata\intl\Intl;
use webadmin\modules\site\WidgetInterface;

class NewsTopWidget implements WidgetInterface
{
    public function __construct(
        protected NewsService $service,
        protected Intl $intl
    ) {
    }
    public function getName(): string
    {
        return 'news_top';
    }
    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $context
     * @return Form
     */
    public function getForm(array $data = [], array $context = []): Form
    {
        return (new Form())
            ->addField(
                new Field('text', ['name' => 'count'], ['label' => 'pages.templates.news.count'])
            )
            ->addField(
                (new Field('select', ['name' => 'tag'], ['label' => 'pages.templates.news.tags']))
                    ->setOption('values', [$this->intl->get('pages.templates.news.notag')] + $this->service->tags())
            )
            ->populate($data)
            ->setLayout([['tag:12', 'count:4']]);
    }
}
