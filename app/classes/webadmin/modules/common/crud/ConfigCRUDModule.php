<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use RuntimeException;
use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;
use webadmin\components\html\Form;
use webadmin\components\html\Table;

/**
 * @template T of \vakata\database\schema\Entity
 * @template S of CRUDServiceInterface<T>
 * @extends CRUDModule<T,S>
 */
class ConfigCRUDModule extends CRUDModule implements APIProviderInterface
{
    /** @psalm-suppress ArgumentTypeCoercion */
    public function __construct(
        protected DIContainer $container,
        protected array $config
    ) {
        $dir = dirname((new \ReflectionClass(static::class))->getFileName() ?: throw new RuntimeException("No path"));
        $cls = '\\' . preg_replace('(Module$)', '', static::class);
        parent::__construct(
            $container,
            $config['module']['name'],
            $config['module']['slug'],
            $config['module']['icon'] ?? 'cubes',
            $config['module']['color'] ?? 'olive',
            $config['module']['parent'] ?? '',
            $config['module']['table'],
            (
                class_exists($cls . 'Controller') &&
                in_array(namespace\CRUDController::class, class_parents($cls . 'Controller') ?: [])
            ) ? $cls . 'Controller' : namespace\CRUDController::class,
            /** @phpstan-ignore-next-line */
            (
                class_exists($cls . 'Service') &&
                in_array(namespace\CRUDService::class, class_parents($cls . 'Service') ?: [])
            ) ?
                $cls . 'Service' :
                (
                    ($config['module']['history'] ?? false) ?
                        namespace\CRUDServiceVersioned::class :
                        namespace\CRUDService::class
                ),
            is_dir($dir . '/views') ? $dir . '/views' : null
        );
    }
    public function listingCallback(Table $table): Table
    {
        $table = parent::listingCallback($table);
        if (isset($this->config['table']['columns'])) {
            $table->setColumns($this->config['table']['columns']);
        }
        if (isset($this->config['table']['actions'])) {
            foreach ($table->getOperations(true) as $name => $operation) {
                if (!in_array($name, $this->config['table']['actions'])) {
                    $operation->hide();
                } else {
                    $operation->show();
                }
            }
        }
        if (isset($this->config['table']['operations'])) {
            foreach ($table->getRows() as $row) {
                foreach ($row->getOperations(true) as $name => $operation) {
                    if (!in_array($name, $this->config['table']['operations'])) {
                        $operation->hide();
                    } else {
                        $operation->show();
                    }
                }
            }
        }
        return $table;
    }
    public function canCreate(): bool
    {
        return in_array('create', $this->config['table']['actions'] ?? ['create']);
    }
    public function canRead(): bool
    {
        return in_array('read', $this->config['table']['operations'] ?? []);
    }
    public function canUpdate(): bool
    {
        return in_array('update', $this->config['table']['operations'] ?? ['update']);
    }
    public function canDelete(): bool
    {
        return in_array('delete', $this->config['table']['operations'] ?? ['delete']);
    }
    public function canCopy(): bool
    {
        return in_array('copy', $this->config['table']['operations'] ?? []);
    }
    public function hasHistory(): bool
    {
        return $this->config['module']['history'] ?? false;
    }
    public function formCallback(Form $form): Form
    {
        $definition = $this->config['forms'][$form->getContext('type', 'base')] ??
            $this->config['forms']['base'] ?? [];
        $form = parent::formCallback($form);
        foreach ($this->config['forms']['base']['fields'] ?? [] as $name => $config) {
            if ($form->hasField($name)) {
                $field = $form->getField($name);
                $field->setAttrs($config['attrs']);
                $field->setOptions($config['options']);
            }
        }
        foreach ($definition['fields'] ?? [] as $name => $config) {
            if ($form->hasField($name)) {
                $field = $form->getField($name);
                $field->setAttrs($config['attrs']);
                $field->setOptions($config['options']);
            }
        }
        $form->setLayout($definition['layout'] ?? $this->config['forms']['base']['layout'] ?? null);
        return $form;
    }
}
