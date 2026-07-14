<?php

declare(strict_types=1);

namespace webadmin\components\html;

use vakata\validation\Validator;

class Field
{
    use ElementTrait;

    protected ?Form $form = null;
    protected array $options = [];
    protected bool $hidden = false;

    public function __construct(string $type = "text", array $attr = [], array $options = [])
    {
        $this->attr = $attr;
        $this->attr['type'] = $type;
        $this->options = $options;
    }
    public function __clone()
    {
        $this->form = null;
    }

    public function getType(string $default = 'text'): string
    {
        return $this->getAttr('type', $default);
    }
    public function setType(string $type): self
    {
        return $this->setAttr('type', $type);
    }

    public function getName(string $default = ''): string
    {
        return $this->getAttr('name', $default);
    }
    public function setName(string $value): self
    {
        return $this->setAttr('name', $value);
    }

    public function setAttr(string $attr, mixed $value): self
    {
        $this->attr[$attr] = $value;
        if ($attr === 'name' && isset($this->form)) {
            $this->form->refreshFieldMap();
        }
        if ($attr === 'value') {
            $this->setValue($value);
        }
        return $this;
    }
    public function setAttrs(array $attr): self
    {
        $this->attr = $attr;
        if (isset($this->form)) {
            $this->form->refreshFieldMap();
        }
        if (isset($attr['value'])) {
            $this->setValue($attr['value']);
        }
        return $this;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function getValue(mixed $default = null): mixed
    {
        $value = $this->getAttr('value', $default);
        if (!is_string($value) && is_callable($value)) {
            $value = call_user_func($value);
            $this->setAttr('value', $value);
            if ($this->form) {
                $this->form->valueFromField($this);
            }
        }
        return $value;
    }
    /**
     * @param mixed $value
     * @return self
     */
    public function setValue(mixed $value): self
    {
        $this->attr['value'] = $value;
        if ($this->form && !is_callable($value)) {
            $this->form->valueFromField($this);
        }
        return $this;
    }

    public function enable(): self
    {
        if ($this->hasAttr('readonly')) {
            $this->delAttr('readonly');
        }
        if ($this->hasAttr('disabled')) {
            $this->delAttr('disabled');
        }
        return $this;
    }
    public function disable(): self
    {
        if (in_array($this->getType(), ['select', 'multipleselect', 'tags'])) {
            return $this->setAttr('disabled', 'disabled');
        }
        return $this->setAttr('readonly', 'readonly');
    }

    public function hasOption(string $key): bool
    {
        return isset($this->options[$key]);
    }
    /**
     * @return mixed
     */
    public function getOption(string $key, mixed $default = null)
    {
        $opt = $this->options[$key] ?? $default;
        if ($opt instanceof \Closure) {
            $opt = call_user_func($opt, $this);
        }
        return $opt;
    }
    public function getOptions(): array
    {
        $opts = [];
        foreach (array_keys($this->options) as $k) {
            $opts[$k] = $this->getOption((string)$k);
        }
        return $opts;
    }
    public function setOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;
        return $this;
    }
    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }
    public function delOption(string $option): self
    {
        unset($this->options[$option]);
        return $this;
    }
    public function delOptions(): self
    {
        $this->options = [];
        return $this;
    }
    public function setForm(?Form $form = null): self
    {
        $changed = $form !== $this->form;
        if (isset($this->form) && $changed) {
            $this->form->removeField($this->getName(''));
        }
        $this->form = $form;
        if (isset($this->form) && $changed) {
            $this->form->addField($this);
        }
        return $this;
    }
    public function getForm(): ?Form
    {
        return $this->form;
    }
    public function getLayout(bool $createDefault = false): ?FormLayout
    {
        return $this->getForm()?->getLayout($createDefault);
    }
    public function getRow(bool $createDefault = false): ?FormLayoutRow
    {
        $layout = $this->getLayout($createDefault);
        if (!$layout) {
            return null;
        }
        foreach ($layout->getRows() as $row) {
            if ($row->hasField($this)) {
                return $row;
            }
        }
        return null;
    }
    public function getWidth(): ?int
    {
        return $this->getRow()?->getFieldWidth($this->getName());
    }
    public function setWidth(int $width): self
    {
        $this->getRow()?->setFieldWidth($this, $width);
        return $this;
    }
    public function index(): ?int
    {
        return $this->getRow()?->getFieldIndex($this->getName());
    }

    public function moveBefore(Field $ref): self
    {
        $row = $this->getRow();
        if ($row && $row === $ref->getRow()) {
            $row->moveField($this, (int)$ref->index());
        }
        return $this;
    }
    public function moveAfter(Field $ref): self
    {
        $row = $this->getRow();
        if ($row && $row === $ref->getRow()) {
            $row->moveField($this, $ref->index() + 1);
        }
        return $this;
    }
    public function move(int $position): self
    {
        $row = $this->getRow();
        if ($row) {
            $row->moveField($this, $position);
        }
        return $this;
    }
    public function moveFirst(): self
    {
        $this->move(0);
        return $this;
    }
    public function moveLast(): self
    {
        $row = $this->getRow();
        if ($row) {
            $this->move(count($row->getFields()));
        }
        return $this;
    }
    public function show(): self
    {
        $this->hidden = false;
        return $this;
    }
    public function hide(): self
    {
        $this->hidden = true;
        return $this;
    }
    public function isHidden(): bool
    {
        return $this->hidden;
    }
    public function validator(): Validator
    {
        if (!$this->form || !$this->getName('')) {
            throw new \Exception('No form or name');
        }
        return $this->form->getValidator()->optional($this->getName(''));
    }
}
