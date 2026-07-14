<?php

declare(strict_types=1);

namespace webadmin\components\html;

use Closure;

class TableColumn
{
    use ElementTrait;

    protected string $name;
    protected ?Form $filter = null;
    protected ?Closure $mapper = null;
    protected bool $sortable = true;
    protected bool $hidden = false;
    protected ?string $quickFilter = null;

    public function __construct(string $name)
    {
        $this->setName($name);
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): TableColumn
    {
        $this->name = $name;
        return $this;
    }
    public function isSortable(): bool
    {
        return $this->sortable;
    }
    public function setSortable(bool $sortable): TableColumn
    {
        $this->sortable = $sortable;
        return $this;
    }
    public function setHidden(bool $hidden): TableColumn
    {
        $this->hidden = $hidden;
        return $this;
    }
    public function show(): TableColumn
    {
        return $this->setHidden(false);
    }
    public function hide(): TableColumn
    {
        return $this->setHidden(true);
    }
    public function isHidden(): bool
    {
        return $this->hidden;
    }
    public function hasFilter(): bool
    {
        return $this->filter !== null;
    }
    public function setFilter(Form $filter): self
    {
        $this->filter = $filter;
        return $this;
    }
    public function getFilter(): ?Form
    {
        return $this->filter;
    }
    public function setMap(?callable $mapper = null): self
    {
        $this->mapper = $mapper ? Closure::fromCallable($mapper) : null;
        return $this;
    }
    public function hasMap(): bool
    {
        return $this->mapper !== null;
    }
    public function getMap(): ?callable
    {
        return $this->mapper;
    }
    public function hasQuickFilter(): bool
    {
        return $this->quickFilter !== null;
    }
    public function getQuickFilter(): ?string
    {
        return $this->quickFilter;
    }
    public function setQuickFilter(?string $quickFilter = null): self
    {
        $this->quickFilter = $quickFilter;
        return $this;
    }
}
