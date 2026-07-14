<?php

declare(strict_types=1);

namespace webadmin\modules\site;

use webadmin\components\html\Form;

interface TemplateProviderInterface
{
    /**
     * @return array<string>
     */
    public function getTemplates(): array;
    /**
     * @param string $name
     * @return TemplateInterface
     */
    public function getTemplate(string $name): TemplateInterface;
}
