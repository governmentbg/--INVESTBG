<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use RuntimeException;
use vakata\database\schema\Table;
use vakata\database\schema\Entity;

class CRUDEntityDefinition extends Table
{
    /** @var array<string,array<string,CRUDModuleInterface<Entity,CRUDServiceInterface<Entity>>>> */
    protected array $moduleRelations = [];

    public static function fromTable(Table $table): self
    {
        $definition = new self($table->getName(), $table->getSchema());
        $temp = [];
        foreach ($table->getFullColumns() as $k => $v) {
            $temp[$k] = clone $v;
        }
        $definition->data['columns'] = $temp;
        $definition->data['primary'] = $table->getPrimaryKey();
        $temp = [];
        foreach ($table->getRelations() as $k => $v) {
            $temp[$k] = clone $v;
        }
        $definition->relations = $temp;
        return $definition;
    }

    protected function deleteColumn(string $name): self
    {
        unset($this->data['columns'][$name]);
        return $this;
    }
    protected function deleteRelation(string $name): self
    {
        unset($this->relations[$name], $this->moduleRelations[$name]);
        return $this;
    }
    /**
     * @param string $name
     * @param CRUDModuleInterface<Entity,CRUDServiceInterface<Entity>> $module
     */
    public function addModule(string $name, CRUDModuleInterface $module): self
    {
        if (!isset($this->moduleRelations[$name])) {
            $this->moduleRelations[$name] = [];
        }
        $this->moduleRelations[$name][$module->getName()] = $module;
        return $this;
    }
    /**
     * @return array<string,CRUDModuleInterface<Entity,CRUDServiceInterface<Entity>>>
     */
    public function getModule(string $name): array
    {
        return $this->moduleRelations[$name] ?? [];
    }
    /**
     * @return array<string,array<string,CRUDModuleInterface<Entity,CRUDServiceInterface<Entity>>>>
     */
    public function getModules(): array
    {
        return $this->moduleRelations;
    }
}
