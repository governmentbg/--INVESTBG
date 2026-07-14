<?php

declare(strict_types=1);

namespace webadmin\modules\site\pages;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\modules\site\WidgetInterface;

class RichtextWidget implements WidgetInterface
{
    public function getName(): string
    {
        return 'richtext';
    }
    public function getForm(array $data = [], array $context = []): Form
    {
        return (new Form())
            ->addField(
                new Field('richtext', ['name' => 'richtext'], ['label' => ''])
            )
            ->populate($data);
    }
}
