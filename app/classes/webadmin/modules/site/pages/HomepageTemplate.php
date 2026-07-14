<?php

declare(strict_types=1);

namespace webadmin\modules\site\pages;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\modules\site\TemplateInterface;

class HomepageTemplate implements TemplateInterface
{
    public function getName(): string
    {
        return 'homepage';
    }
    public function getForm(array $data = [], array $context = []): Form
    {
        return (new Form())
            ->addField(new Field('richtext', ['name' => 'content'], ['label' => '']))
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
