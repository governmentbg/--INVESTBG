<?php

declare(strict_types=1);

namespace webadmin\modules\administration\organization;

use vakata\di\DIContainer;
use webadmin\modules\VisualModule;

class OrganizationModule extends VisualModule
{
    public const string NAME = 'organization';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'sitemap',
            'yellow',
            'administration',
            namespace\OrganizationController::class
        );
    }
}
