<?php

declare(strict_types=1);

namespace webadmin\components\html;

class FormLayout
{
    protected Form $form;
    /**
     * @var array<int,FormLayoutRow>
     */
    protected array $rows = [];

    public static function fromArray(Form $form, array $data): FormLayout
    {
        return new self($form, $data);
    }

    public function __construct(Form $form, array $data = [])
    {
        $this->form = $form;
        foreach ($data as $row) {
            if (!($row instanceof FormLayoutRow)) {
                $row = FormLayoutRow::fromScalar($this, $row);
            }
            $this->rows[] = $row;
        }
    }
    public function addRow(mixed $value): self
    {
        $this->rows[] = new FormLayoutRow($this, $value);
        return $this;
    }
    public function moveRow(FormLayoutRow $row, int $position): self
    {
        $f = new FormLayoutRow($this);
        foreach ($this->rows as $k => $v) {
            if ($v === $row) {
                $this->rows[$k] = $f;
            }
        }
        $this->rows = array_values(
            array_filter(
                array_splice($this->rows, $position, 0, [ $row ]),
                function ($v) use ($f) {
                    return $v !== $f;
                }
            )
        );
        return $this;
    }
    public function removeRow(FormLayoutRow $row): self
    {
        foreach ($this->rows as $k => $v) {
            if ($v === $row) {
                unset($this->rows[$k]);
                $this->rows = array_values($this->rows);
            }
        }
        return $this;
    }

    public function getField(string $name): ?Field
    {
        return $this->form->getField($name);
    }
    /**
     * @return array<FormLayoutRow>
     */
    public function getRows(): array
    {
        return $this->rows;
    }
    public function getRow(int $index): ?FormLayoutRow
    {
        return $this->rows[$index] ?? null;
    }
    public function getRowIndex(FormLayoutRow $row): int
    {
        return (int)array_search($row, $this->rows, true);
    }
    public function getForm(): Form
    {
        return $this->form;
    }
    public function toArray(): array
    {
        $res = [];
        $parent = null;
        foreach ($this->rows as $row) {
            if ($parent !== $row->getParent()) {
                $res[] = $row->hasParent() ? $row->getParent() : explode(':', $parent ?? '')[0] . ':';
                $parent = $row->getParent();
            }
            if ($row->hasSeparatorBefore()) {
                $res[] = true;
            }
            if ($row->hasTitle()) {
                $res[] = $row->getTitle();
            }
            $temp = [];
            foreach ($row->getFields() as $field) {
                if ($field instanceof Field) {
                    $i = $field->getName();
                    $w = $field->getWidth();
                } else {
                    $i = $field;
                    $w = $row->getTextWidth($field);
                }
                $temp[] = $i . ($w ? ':' . $w : '');
            }
            if (count($temp)) {
                $res[] = $temp;
            }
            if ($row->hasSeparatorAfter()) {
                $res[] = true;
            }
        }
        return $res;
    }
}
