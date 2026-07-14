<?php

declare(strict_types=1);

namespace webadmin\modules\administration\modules;

use vakata\di\DIContainer;
use webadmin\modules\VisualModule;

class ModulesModule extends VisualModule
{
    public const string NAME = 'modules';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'puzzle',
            'purple',
            'settings',
            namespace\ModulesController::class
        );
    }
}
