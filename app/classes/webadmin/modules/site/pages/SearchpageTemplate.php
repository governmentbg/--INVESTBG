<?php

declare(strict_types=1);

namespace webadmin\modules\site\pages;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\modules\site\TemplateInterface;

class SearchpageTemplate implements TemplateInterface
{
    public function getName(): string
    {
        return 'searchpage';
    }
    public function getForm(array $data = [], array $context = []): Form
    {
        return (new Form())
            ->addField(
                new Field('text', ['name' => 'perpage'], ['label' => 'pages.templates.search.perpage'])
            )
            ->populate($data);
    }
    public function getZones(): array
    {
        return [];
    }
    public function wildcard(): bool
    {
        return false;
    }
}
