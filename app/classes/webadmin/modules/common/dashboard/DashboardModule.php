<?php

declare(strict_types=1);

namespace webadmin\modules\common\dashboard;

use vakata\di\DIContainer;
use webadmin\modules\PermissionsModuleInterface;
use webadmin\modules\VisualModule;

class DashboardModule extends VisualModule implements PermissionsModuleInterface
{
    public const string NAME = 'dashboard';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'home',
            'green',
            '',
            namespace\DashboardController::class
        );
    }
    public function permissions(): array
    {
        return [ self::NAME . '/errors' ];
    }
    public function onDashboard(): bool
    {
        return false;
    }
    public function inMenu(): bool
    {
        return true;
    }
}
