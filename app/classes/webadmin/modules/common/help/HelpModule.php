<?php

declare(strict_types=1);

namespace webadmin\modules\common\help;

use vakata\di\DIContainer;
use webadmin\modules\VisualModule;

class HelpModule extends VisualModule
{
    public const string NAME = 'help';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'help',
            'teal',
            '',
            namespace\HelpController::class
        );
    }
    public function onDashboard(): bool
    {
        return false;
    }
    public function inMenu(): bool
    {
        return false;
    }
}
