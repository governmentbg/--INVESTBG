<?php

declare(strict_types=1);

namespace webadmin\modules\common\profile;

use vakata\di\DIContainer;
use webadmin\modules\VisualModule;

class ProfileModule extends VisualModule
{
    public const string NAME = 'profile';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'user',
            'orange',
            '',
            namespace\ProfileController::class
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
