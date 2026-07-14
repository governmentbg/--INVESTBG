<?php

declare(strict_types=1);

namespace webadmin\modules;

interface PermissionsModuleInterface
{
    /**
     * @return array<string>
     */
    public function permissions(): array;
}
