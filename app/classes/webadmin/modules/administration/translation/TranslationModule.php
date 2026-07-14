<?php

declare(strict_types=1);

namespace webadmin\modules\administration\translation;

use vakata\di\DIContainer;
use webadmin\modules\VisualModule;

class TranslationModule extends VisualModule
{
    public const string NAME = 'translation';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'language',
            'purple',
            'settings',
            namespace\TranslationController::class
        );
    }
}
