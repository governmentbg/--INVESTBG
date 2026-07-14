<?php

declare(strict_types=1);

namespace webadmin\components\html;

use RuntimeException;

class Table
{
    use ElementTrait;

    protected string $name = '';

    /**
     * @var array<string,TableColumn>
     */
    protected array $columns = [];
    /**
     * @var array<TableRow>
     */
    protected array $rows = [];
    /**
     * @var array<string,Button>
     */
    protected array $operations = [];
    /**
     * @var list<array<string,string>>
     */
    protected array $filters = [];

    public function __construct(array $columns = [], array $rows = [])
    {
        $this->setColumns($columns);
        $this->setRows($rows);
    }

    public function addColumn(TableColumn $column): Table
    {
        $this->columns[$column->getName()] = $column;
        return $this;
    }
    public function hasColumn(string $name): bool
    {
        return isset($this->columns[$name]);
    }
    public function getColumn(string $name): TableColumn
    {
        if (!isset($this->columns[$name])) {
            throw new RuntimeException("Invalid column name");
        }
        return $this->columns[$name];
    }
    public function removeColumn(string $name): Table
    {
        unset($this->columns[$name]);
        return $this;
    }
    /**
     * @return array<string,TableColumn>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
    public function setColumns(array $columns): Table
    {
        $temp = $this->columns;
        $this->columns = [];
        foreach ($columns as $column) {
            if (is_string($column)) {
                $column = $temp[$column] ?? null;
            }
            if ($column instanceof TableColumn) {
                $this->addColumn($column);
            }
        }
        return $this;
    }
    public function addRow(TableRow $row): Table
    {
        $this->rows[] = $row;
        return $this;
    }
    /**
     * @return array<TableRow>
     */
    public function getRows(): array
    {
        return $this->rows;
    }
    public function setRows(array $rows): Table
    {
        $this->rows = [];
        foreach ($rows as $row) {
            $this->addRow($row);
        }
        return $this;
    }
    /**
     * @param bool $includeHidden
     * @return array<string,Button>
     */
    public function getOperations(bool $includeHidden = false): array
    {
        if ($includeHidden) {
            return $this->operations;
        }
        return array_filter($this->operations, function ($v) {
            return !$v->isHidden();
        });
    }
    /**
     * @param array<Button> $operations
     * @return Table
     */
    public function setOperations(array $operations): Table
    {
        $this->operations = [];
        foreach ($operations as $operation) {
            $this->addOperation($operation);
        }
        return $this;
    }
    public function addOperation(Button $operation): Table
    {
        $this->operations[$operation->getName()] = $operation;
        return $this;
    }
    public function removeOperation(string $name): Table
    {
        unset($this->operations[$name]);
        return $this;
    }
    public function getOperation(string $name): Button
    {
        return $this->operations[$name];
    }
    public function hasOperation(string $name, bool $includeHidden = false): bool
    {
        return isset($this->operations[$name]) && ($includeHidden || !$this->operations[$name]->isHidden());
    }
    /**
     * @param array<string> $order
     * @return Table
     */
    public function setOrder(array $order): self
    {
        $temp = [];
        foreach ($order as $column) {
            if (isset($this->columns[$column])) {
                $temp[$column] = $this->columns[$column];
            }
        }
        foreach ($this->columns as $cname => $column) {
            if (!isset($temp[$cname])) {
                $temp[$cname] = $column->hide();
            }
        }
        $this->columns = $temp;
        return $this;
    }
    /**
     * @return array<string>
     */
    public function getOrder(): array
    {
        return array_keys($this->columns);
    }
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * @param list<array<string,string>> $filters
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }
    /**
     * @return list<array<string,string>>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
    public function hasFilters(): bool
    {
        return count($this->filters) > 0;
    }
}
