<?php

declare(strict_types=1);

namespace webadmin\modules;

use IteratorAggregate;
use Traversable;
use ArrayIterator;
use RuntimeException;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDModuleInterface;
use webadmin\modules\site\TemplateInterface;
use webadmin\modules\site\TemplateProviderInterface;
use webadmin\modules\site\WidgetInterface;
use webadmin\modules\site\WidgetProviderInterface;

/**
 * @implements \IteratorAggregate<int,ModuleInterface>
 */
class ModulesContainer implements IteratorAggregate
{
    /**
     * @var array<ModuleInterface>
     */
    protected array $modules;

    /**
     * @param array<ModuleInterface> $modules
     * @return void
     */
    public function __construct(array $modules = [])
    {
        $this->modules = $modules;
        foreach ($this->modules as $module) {
            if ($module instanceof VisualModule) {
                $module::$modules = $this;
            }
        }
    }

    public function register(ModuleInterface $module): self
    {
        $this->modules[] = $module;
        return $this;
    }
    public function byName(string $name): ModuleInterface
    {
        foreach ($this->modules as $module) {
            if ($module->getName() === $name) {
                return $module;
            }
        }
        throw new \RuntimeException('Module not found');
    }
    // @phpstan-ignore-next-line
    public function byTable(string $table): array
    {
        $modules = [];
        foreach ($this->modules as $module) {
            if ($module instanceof CRUDModuleInterface && $module->getTable() === $table) {
                $modules[] = $module;
            }
        }
        return $modules;
    }
    public function getIterator(): Traversable
    {
        return new ArrayIterator(array_values($this->modules));
    }
    /**
     * @return array<string>
     */
    public function getTemplates(): array
    {
        $templates = [];
        foreach ($this->modules as $module) {
            if ($module instanceof TemplateProviderInterface) {
                $templates = array_merge($templates, $module->getTemplates());
            }
        }
        return $templates;
    }
    public function getTemplate(string $name): TemplateInterface
    {
        foreach ($this->modules as $module) {
            if ($module instanceof TemplateProviderInterface) {
                if (in_array($name, $module->getTemplates())) {
                    return $module->getTemplate($name);
                }
            }
        }
        throw new RuntimeException('Unknown template');
    }
     /**
     * @return array<string>
     */
    public function getWidgets(): array
    {
        $widgets = [];
        foreach ($this->modules as $module) {
            if ($module instanceof WidgetProviderInterface) {
                $widgets = array_merge($widgets, $module->getWidgets());
            }
        }
        return $widgets;
    }
    public function getWidget(string $name): WidgetInterface
    {
        foreach ($this->modules as $module) {
            if ($module instanceof WidgetProviderInterface) {
                if (in_array($name, $module->getWidgets())) {
                    return $module->getWidget($name);
                }
            }
        }
        throw new RuntimeException('Unknown template');
    }
}
