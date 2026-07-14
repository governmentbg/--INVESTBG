<?php

declare(strict_types=1);

namespace webadmin\modules\site\pages;

use RuntimeException;
use vakata\di\DIContainer;
use webadmin\modules\PermissionsModuleInterface;
use webadmin\modules\site\TemplateInterface;
use webadmin\modules\site\TemplateProviderInterface;
use webadmin\modules\site\WidgetInterface;
use webadmin\modules\site\WidgetProviderInterface;
use webadmin\modules\VisualModule;

class PagesModule extends VisualModule implements
    PermissionsModuleInterface,
    WidgetProviderInterface,
    TemplateProviderInterface
{
    public const string NAME = 'pages';
    public function permissions(): array
    {
        return ['pages/publish', 'pages/structure', 'pages/permissions', 'pages/widgets'];
    }
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'desktop',
            'blue',
            'cms',
            namespace\PagesController::class
        );
    }
    public function getTemplates(): array
    {
        return [ 'homepage', 'page', 'searchpage' ];
    }
    public function getTemplate(string $name): TemplateInterface
    {
        switch ($name) {
            case 'homepage':
                return new HomepageTemplate();
            case 'page':
                return new PageTemplate();
            case 'searchpage':
                return new SearchpageTemplate();
            default:
                throw new RuntimeException();
        }
    }
    public function getWidgets(): array
    {
        return [ 'richtext', 'text' ];
    }
    public function getWidget(string $name): WidgetInterface
    {
        switch ($name) {
            case 'richtext':
                return new RichtextWidget();
            case 'text':
                return new TextWidget();
            default:
                throw new RuntimeException();
        }
    }
}
