<?php

declare(strict_types=1);

namespace webadmin\components\html;

class FormLayoutRow
{
    public static function fromScalar(FormLayout $layout, mixed $scalar = null): self
    {
        $fields = [];
        $title = null;
        $separatorBefore = false;
        $separatorAfter = false;

        if (is_bool($scalar)) {
            $separatorBefore = $scalar;
        } elseif (is_string($scalar)) {
            $title = $scalar;
        } elseif (is_array($scalar)) {
            $fields = [];
            foreach ($scalar as $v) {
                $tmp = (string)preg_replace('(\:\d+$)', '', (string)$v);
                if ($layout->getForm()->hasField($tmp)) {
                    $fields[] = $v;
                }
                if (is_string($v) && strpos($v, 't:') === 0) {
                    $fields[] = substr($v, 2);
                }
            }
        }
        return new self($layout, $fields, $title, $separatorBefore, $separatorAfter);
    }

    protected FormLayout $layout;
    /**
     * @var array<int,Field|string>
     */
    protected array $fields = [];
    /**
     * @var array<int,?int>
     */
    protected array $widths = [];

    protected ?string $title = null;
    protected bool $separatorBefore = false;
    protected bool $separatorAfter = false;
    protected ?string $parent = null;

    public function __construct(
        FormLayout $layout,
        array $fields = [],
        ?string $title = null,
        bool $separatorBefore = false,
        bool $separatorAfter = false,
        ?string $parent = null
    ) {
        $this->layout = $layout;
        $this->title = $title;
        $this->separatorBefore = $separatorBefore;
        $this->separatorAfter = $separatorAfter;
        $this->parent = $parent;
        $this->fields = $fields;
    }

    public function getLayout(): FormLayout
    {
        return $this->layout;
    }
    public function getForm(): Form
    {
        return $this->layout->getForm();
    }
    public function hasField(Field|string $field): bool
    {
        if (is_string($field)) {
            $field = $this->getForm()->hasField($field) ? $this->getForm()->getField($field) : null;
        }
        return $field && in_array($field, $this->fields, true);
    }
    public function hasText(string $text): bool
    {
        return in_array($text, $this->fields, true);
    }
    public function getField(string $name): ?Field
    {
        $field = $this->getForm()->hasField($name) ? $this->getForm()->getField($name) : null;
        return $field && $this->hasField($field) ? $field : null;
    }
    public function getFieldIndex(Field|string $field): ?int
    {
        if (is_string($field)) {
            $field = $this->getField($field);
        }
        $i = array_search($field, $this->fields, true);
        return is_int($i) ? $i : null;
    }
    public function getFieldWidth(Field|string $field): ?int
    {
        $i = $this->getFieldIndex($field);
        return $i ? ($this->widths[$i] ?? null) : null;
    }
    public function setFieldWidth(Field|string $field, int $width): self
    {
        if ($this->hasField($field)) {
            $this->widths[(int)$this->getFieldIndex($field)] = $width;
        }
        return $this;
    }
    public function getTextWidth(string $text): ?int
    {
        $i = array_search($text, $this->fields, true);
        return $i !== false ? ($this->widths[$i] ?? null) : null;
    }
    public function setTextWidth(string $text, int $width): self
    {
        $i = array_search($text, $this->fields, true);
        if ($i !== false) {
            $this->widths[$i] = $width;
        }
        return $this;
    }

    public function addField(Field $field, ?int $width = null): self
    {
        $field->getRow()?->removeField($field->getName());
        $this->fields[] = $field;
        $this->widths[] = $width;
        return $this;
    }
    public function addFieldByName(string $name, ?int $width = null): self
    {
        if (!$this->getForm()->hasField($name)) {
            throw new \Exception('Unknown field - ' . htmlspecialchars($name));
        }
        return $this->addField($this->getForm()->getField($name), $width);
    }
    public function addText(string $text, ?int $width = null): self
    {
        $this->fields[] = $text;
        $this->widths[] = $width;
        return $this;
    }

    public function removeField(string $name): self
    {
        foreach ($this->fields as $k => $v) {
            if ($v instanceof Field && $v->getName() === $name) {
                unset($this->fields[$k]);
                unset($this->widths[$k]);
                $this->fields = array_values($this->fields);
                $this->widths = array_values($this->widths);
                return $this;
            }
        }
        return $this;
    }
    public function removeText(string $text): self
    {
        foreach ($this->fields as $k => $v) {
            if (is_string($v) && $v === $text) {
                unset($this->fields[$k]);
                unset($this->widths[$k]);
                $this->fields = array_values($this->fields);
                $this->widths = array_values($this->widths);
                return $this;
            }
        }
        return $this;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }
    public function hasParent(): bool
    {
        return $this->parent !== null;
    }
    public function setParent(?string $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function index(): int
    {
        return $this->layout->getRowIndex($this);
    }
    public function moveBefore(FormLayoutRow $ref): self
    {
        $layout = $this->getLayout();
        $layout->moveRow($this, $layout->getRowIndex($ref));
        return $this;
    }
    public function moveAfter(FormLayoutRow $ref): self
    {
        $layout = $this->getLayout();
        $layout->moveRow($this, $layout->getRowIndex($ref) + 1);
        return $this;
    }
    public function move(int $position): self
    {
        $this->getLayout()->moveRow($this, $position);
        return $this;
    }
    public function moveFirst(): self
    {
        $this->getLayout()->moveRow($this, 0);
        return $this;
    }
    public function moveLast(): self
    {
        $this->getLayout()->moveRow($this, count($this->getLayout()->getRows()));
        return $this;
    }

    public function remove(): FormLayout
    {
        return $this->getLayout()->removeRow($this);
    }

    public function getFields(): array
    {
        return $this->fields;
    }
    public function moveField(Field $field, int $position): self
    {
        $f = new Field('text');
        foreach ($this->fields as $k => $v) {
            if ($v === $field) {
                $this->fields[$k] = $f;
                $this->widths[$k] = -1;
            }
        }
        $w = $field->getWidth();
        $this->fields = array_values(
            array_filter(
                array_splice($this->fields, $position, 0, [ $field ]),
                function ($v) use ($f) {
                    return $v !== $f;
                }
            )
        );
        $this->widths = array_values(
            array_filter(
                array_splice($this->widths, $position, 0, [ $w ]),
                function ($v) {
                    return $v !== -1;
                }
            )
        );
        return $this;
    }

    public function hasSeparatorBefore(): bool
    {
        return $this->separatorBefore;
    }
    public function hasSeparatorAfter(): bool
    {
        return $this->separatorAfter;
    }
    public function hasTitle(): bool
    {
        return $this->title !== null;
    }
    public function getTitle(): ?string
    {
        return $this->title;
    }
}
