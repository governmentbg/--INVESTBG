<?php

declare(strict_types=1);

namespace webadmin\modules\site;

interface WidgetProviderInterface
{
    /**
     * @return array<string>
     */
    public function getWidgets(): array;
    public function getWidget(string $name): WidgetInterface;
}
