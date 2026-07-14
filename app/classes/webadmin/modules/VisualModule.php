<?php

declare(strict_types=1);

namespace webadmin\modules;

use RuntimeException;
use vakata\di\DIContainer;
use vakata\http\Request;
use vakata\http\Response;

/**
* @SuppressWarnings("PHPMD.NumberOfChildren")
*/
abstract class VisualModule implements VisualModuleInterface
{
    public static ?ModulesContainer $modules = null;
    /**
     * @param string $name
     * @param string $slug
     * @param string $icon
     * @param string $color
     * @param string $parent
     * @param ?class-string $controller
     * @return void
     */
    public function __construct(
        protected DIContainer $container,
        protected string $name,
        protected string $slug,
        protected string $icon,
        protected string $color,
        protected string $parent,
        protected ?string $controller = null
    ) {
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getSlug(): string
    {
        return $this->slug;
    }
    public function getIcon(): string
    {
        return $this->icon;
    }
    public function getColor(): string
    {
        return $this->color;
    }
    public function getParent(): string
    {
        return $this->parent;
    }
    public function onDashboard(): bool
    {
        return true;
    }
    public function inMenu(): bool
    {
        return true;
    }
    public function getController(): object
    {
        if (!$this->controller) {
            throw new RuntimeException('Controller missing');
        }
        return $this->container->instance($this->controller);
    }
    public function process(Request $request): Response
    {
        $controller = $this->getController();
        $segment = $request->getUrl()->getSegment(1, 'index');

        $verb = strtolower($request->getMethod());
        foreach ([ $verb . ucfirst($segment), $segment, '__invoke' ] as $method) {
            if (method_exists($controller, $method)) {
                return $this->container->invoke($controller, $method);
            }
        }
        throw new RuntimeException('Method not found', 404);
    }
}
