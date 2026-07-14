<?php

declare(strict_types=1);

namespace webadmin\modules\site\pages;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\modules\site\WidgetInterface;

class TextWidget implements WidgetInterface
{
    public function getName(): string
    {
        return 'text';
    }
    public function getForm(array $data = [], array $context = []): Form
    {
        return (new Form())
            ->addField(
                new Field('textarea', ['name' => 'textarea'], ['label' => ''])
            )
            ->populate($data);
    }
}
