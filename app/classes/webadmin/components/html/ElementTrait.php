<?php

declare(strict_types=1);

namespace webadmin\components\html;

trait ElementTrait
{
    protected array $attr  = [];

    public function getClass(): string
    {
        return $this->getAttr('class', '');
    }
    public function setClass(string $class): self
    {
        $this->setAttr('class', implode(' ', array_filter(array_unique(explode(' ', $class)))));
        return $this;
    }
    public function addClass(string $class): self
    {
        return $this->setClass(implode(' ', array_filter(array_unique(array_merge(
            explode(' ', $this->getClass()),
            explode(' ', $class)
        )))));
    }
    public function removeClass(string $class): self
    {
        $class = array_filter(array_unique(explode(' ', $class)));
        $class = array_diff(explode(' ', $this->getClass()), $class);
        return $this->setClass(implode(' ', $class));
    }
    public function hasClass(string $class): bool
    {
        $classes = array_filter(array_unique(explode(' ', $class)));
        $current = explode(' ', $this->getClass());
        foreach ($classes as $c) {
            if (!in_array($c, $current)) {
                return false;
            }
        }
        return true;
    }

    public function hasAttr(string $attr): bool
    {
        return $this->getAttr($attr) !== null;
    }
    /**
     * @param mixed $default
     * @return mixed
     */
    public function getAttr(string $key, mixed $default = null): mixed
    {
        $attr = $this->attr[$key] ?? $default;
        if ($attr instanceof \Closure) {
            $attr = call_user_func($attr, $this);
        }
        return $attr;
    }
    public function getAttrs(): array
    {
        $attrs = [];
        foreach (array_keys($this->attr) as $k) {
            $attrs[$k] = $this->getAttr((string)$k);
        }
        return $attrs;
    }
    public function setAttr(string $attr, mixed $value): self
    {
        $this->attr[$attr] = $value;
        return $this;
    }
    public function setAttrs(array $attr): self
    {
        $this->attr = $attr;
        return $this;
    }
    public function delAttr(string $attr): self
    {
        unset($this->attr[$attr]);
        return $this;
    }
    public function delAttrs(): self
    {
        $this->attr = [];
        return $this;
    }
}
