<?php

declare(strict_types=1);

namespace webadmin\modules\site;

use webadmin\components\html\Form;

interface WidgetInterface
{
    public function getName(): string;
    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $context
     * @return Form
     */
    public function getForm(array $data = [], array $context = []): Form;
}
