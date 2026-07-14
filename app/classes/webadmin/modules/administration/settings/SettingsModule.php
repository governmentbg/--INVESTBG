<?php

declare(strict_types=1);

namespace webadmin\modules\administration\settings;

use vakata\di\DIContainer;
use webadmin\modules\VisualModule;

class SettingsModule extends VisualModule
{
    public const string NAME = 'settings';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'cogs',
            'black',
            'settings',
            namespace\SettingsController::class
        );
    }
}
