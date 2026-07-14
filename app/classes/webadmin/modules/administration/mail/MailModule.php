<?php

declare(strict_types=1);

namespace webadmin\modules\administration\mail;

use vakata\di\DIContainer;
use webadmin\modules\VisualModule;

class MailModule extends VisualModule
{
    public const string NAME = 'mail';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'mail',
            'teal',
            'settings',
            namespace\MailController::class
        );
    }
}
