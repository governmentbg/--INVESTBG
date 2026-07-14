<?php

declare(strict_types=1);

namespace webadmin\modules\site\languages;

use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use vakata\di\DIContainer;

/**
 * @extends CRUDModule<\schema\LanguagesEntity,LanguagesService>
 */
class LanguagesModule extends CRUDModule
{
    public const string NAME = 'languages';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'font',
            'pink',
            'cms',
            'languages',
            CRUDController::class,
            namespace\LanguagesService::class
        );
    }
    public function canDelete(): bool
    {
        return false;
    }
}
