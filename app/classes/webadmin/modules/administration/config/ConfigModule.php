<?php

declare(strict_types=1);

namespace webadmin\modules\administration\config;

use vakata\di\DIContainer;
use webadmin\modules\VisualModule;

class ConfigModule extends VisualModule
{
    public const string NAME = 'config';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'cog',
            'yellow',
            'settings',
            namespace\ConfigController::class
        );
    }
}
