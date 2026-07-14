<?php

declare(strict_types=1);

namespace webadmin\modules\administration\errors;

use vakata\di\DIContainer;
use webadmin\modules\VisualModule;

class ErrorsModule extends VisualModule
{
    public const string NAME = 'errors';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'bug',
            'red',
            'settings',
            namespace\ErrorsController::class
        );
    }
}
