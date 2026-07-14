<?php

declare(strict_types=1);

namespace webadmin\components\html;

class Button
{
    use ElementTrait;

    protected string $name;
    protected ?string $label  = null;
    protected ?string $icon   = null;
    protected bool $hidden = false;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
    public function setLabel(?string $label = null): Button
    {
        $this->label = $label;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }
    public function setIcon(?string $icon = null): Button
    {
        $this->icon = $icon;
        return $this;
    }

    public function show(): Button
    {
        $this->hidden = false;
        return $this;
    }
    public function hide(): Button
    {
        $this->hidden = true;
        return $this;
    }
    public function isHidden(): bool
    {
        return $this->hidden;
    }
}
