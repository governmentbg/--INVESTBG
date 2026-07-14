<?php

declare(strict_types=1);

namespace webadmin\modules\site\translation;

use vakata\di\DIContainer;
use webadmin\modules\VisualModule;

class TranslationModule extends VisualModule
{
    public const string NAME = 'ptranslation';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'language',
            'orange',
            'cms',
            namespace\TranslationController::class
        );
    }
}
