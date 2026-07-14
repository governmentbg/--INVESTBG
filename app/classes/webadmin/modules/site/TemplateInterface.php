<?php

declare(strict_types=1);

namespace webadmin\modules\site;

use webadmin\components\html\Form;

interface TemplateInterface
{
    public function getName(): string;
    public function getForm(array $data = [], array $context = []): Form;
    /**
     * @return array<string>
     */
    public function getZones(): array;
    public function wildcard(): bool;
}
