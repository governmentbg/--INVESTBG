<?php

declare(strict_types=1);

namespace webadmin\modules\administration\permissions;

use vakata\di\DIContainer;
use webadmin\modules\VisualModule;

class PermissionsModule extends VisualModule
{
    public const string NAME = 'permissions';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'lock',
            'green',
            'settings',
            namespace\PermissionsController::class
        );
    }
}
