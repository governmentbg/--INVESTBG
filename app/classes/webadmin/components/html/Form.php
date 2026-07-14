<?php

declare(strict_types=1);

namespace webadmin\components\html;

use Exception;
use vakata\validation\Validator;

class Form
{
    use ElementTrait;

    protected ?Validator $validator = null;
    protected ?Validator $validator_full = null;
    protected ?FormLayout $layout = null;
    protected array $context = [];
    /**
     * @var array<int,Field>
     */
    protected array $fields = [];
    /**
     * @var array<string,Field>
     */
    protected array $fieldmap = [];
    /**
     * @var array<string,mixed>
     */
    protected array $value = [];

    public function __clone()
    {
        if ($this->validator) {
            $this->validator = clone $this->validator;
        }
        if ($this->layout) {
            $this->layout = clone $this->layout;
        }
        foreach ($this->fields as $k => $v) {
            $this->fields[$k] = clone $v;
            $this->fieldmap[$this->fields[$k]->getName()] = $this->fields[$k];
            $this->fields[$k]->setForm($this);
        }
    }

    public function refreshFieldMap(): void
    {
        $this->fieldmap = [];
        foreach ($this->fields as $v) {
            $this->fieldmap[$v->getName()] = $v;
        }
    }

    public function addField(Field $field): Form
    {
        if (in_array($field, $this->fields, true)) {
            return $this;
        }
        $this->fields[] = $field;
        $field->setForm($this);
        $this->valueFromField($field);
        $this->fieldmap[$field->getName()] = $field;
        if ($this->hasValidator() && $this->validator_full) {
            $this->setValidator($this->validator_full);
        }
        return $this;
    }
    public function removeField(string $name): Form
    {
        foreach ($this->fields as $k => $v) {
            if ($v->getName() === $name) {
                unset($this->fields[$k]);
                unset($this->fieldmap[$name]);
                $this->setValue($v->getName(''), null, false);
                $v->getRow()?->removeField($name);
                $v->setForm(null);
            }
        }
        if ($this->hasValidator() && $this->validator_full) {
            $this->setValidator($this->validator_full);
        }
        return $this;
    }
    /**
     * @return array<Field>
     */
    public function getFields(): array
    {
        return $this->fields;
    }
    public function setFields(array $fields): Form
    {
        $this->fields = [];
        $this->fieldmap = [];
        foreach ($fields as $field) {
            $this->addField($field);
        }
        return $this;
    }
    public function hasField(string $name): bool
    {
        if (isset($this->fieldmap[$name])) {
            return true;
        }
        foreach ($this->fields as $field) {
            if ($field->getName() === $name) {
                return true;
            }
        }
        return false;
    }
    public function getField(string $name): Field
    {
        if (isset($this->fieldmap[$name])) {
            return $this->fieldmap[$name];
        }
        foreach ($this->fields as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }
        throw new Exception('Field not found');
    }

    public function hasLayout(): bool
    {
        return $this->layout !== null;
    }
    public function getLayout(bool $createDefault = false): ?FormLayout
    {
        if (!$this->hasLayout() && $createDefault) {
            $this->createDefaultLayout();
        }
        return $this->layout;
    }
    public function getLayoutArray(bool $createDefault = false): array
    {
        if (!$this->hasLayout() && $createDefault) {
            $this->createDefaultLayout();
        }
        return $this->layout ? $this->layout->toArray() : [];
    }
    public function setLayout(FormLayout|array|null $layout = null): self
    {
        $this->layout = is_array($layout) ? FormLayout::fromArray($this, $layout) : $layout;
        return $this;
    }
    public function createDefaultLayout(): self
    {
        $layout = [];
        foreach ($this->getFields() as $field) {
            $layout[] = [ $field->getName() ];
        }
        return $this->setLayout($layout);
    }

    public function enable(): self
    {
        foreach ($this->fields as $field) {
            $field->enable();
        }
        return $this;
    }
    public function disable(): self
    {
        foreach ($this->fields as $field) {
            $field->disable();
        }
        return $this;
    }
    public function populate(mixed $data): self
    {
        return $this->setValue($data);
    }
    public function getValue(): array
    {
        return $this->value;
    }
    public function setValue(mixed $key, mixed $value = null, bool $updateFields = true): self
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setValue($k, $value ?? $v, false);
            }
            if ($updateFields) {
                $this->valueToFields();
            }
            return $this;
        }
        $name  = explode(']', str_replace(['][', '['], ']', (string)$key));
        $name  = count($name) > 1 ? array_slice($name, 0, -1) : $name;
        /** @psalm-suppress UnsupportedPropertyReferenceUsage */
        $tmp = &$this->value;
        foreach ($name as $k) {
            if ($k === "") {
                continue;
            }
            if (!is_array($tmp)) {
                $tmp = [];
            }
            if (!isset($tmp[$k])) {
                $tmp[$k] = [];
            }
            $tmp = &$tmp[$k];
        }
        /** @psalm-suppress InvalidArrayOffset */
        if ($name[count($name) - 1] == '') {
            if (!is_array($tmp)) {
                $tmp = [];
            }
            $tmp[] = $value;
        } else {
            $tmp = $value;
        }
        if ($updateFields) {
            $this->valueToFields();
        }
        return $this;
    }
    public function removeValue(): void
    {
        $this->value = [];
        $this->valueToFields();
    }
    public function valueToFields(): self
    {
        // TODO: handle groups and array values - for each group - add the necessary amount of copies
        foreach ($this->fields as $field) {
            $name = $field->getName();
            if ($name) {
                $name = str_replace(['][', ']'], ['[', ''], $name);
                $name = array_filter(
                    explode('[', $name),
                    function ($v) {
                        return $v === '0' || !empty($v);
                    }
                );
                $temp = $this->value;
                foreach ($name as $part) {
                    if (is_array($temp) && isset($temp[$part])) {
                        $temp = $temp[$part];
                    } elseif (is_object($temp) && ($temp->{$part} ?? null) !== null) {
                        $temp = $temp->{$part};
                    } else {
                        $temp = null;
                        break;
                    }
                }
                $field->setValue($temp);
            }
        }
        return $this;
    }
    public function valueFromFields(): void
    {
        $this->value = [];
        foreach ($this->fields as $v) {
            $this->valueFromField($v);
        }
    }
    public function valueFromField(Field $field): void
    {
        $name = $field->getName('');
        if ($name === '') {
            return;
        }
        $this->setValue($name, $field->getValue(null), false);
    }
    public function hasValidator(): bool
    {
        return isset($this->validator);
    }
    public function getValidator(): Validator
    {
        if (!$this->validator) {
            $this->validator = new Validator();
        }
        return $this->validator;
    }
    public function setValidator(Validator $validator): self
    {
        $this->removeValidator();
        $this->validator = $validator;
        $this->validator_full = $validator;
        $validator = json_decode(json_encode($validator, JSON_THROW_ON_ERROR), true);
        foreach ($validator as $key => $data) {
            $used = false;
            $prts = explode('.', $key);
            $regx = implode('', array_map(function ($v, $k) {
                return $k ? preg_quote('[') . ($v === '*' ? '.*?' : preg_quote($v)) . preg_quote(']') : preg_quote($v);
            }, $prts, array_keys($prts)));
            foreach ($this->fields as $field) {
                if (
                    $field->getName('') === $key ||
                    preg_match('(^' . $regx . '$)', $field->getName(''))
                ) {
                    $field->setAttr('data-validate', array_values($data));
                    $used = true;
                }
            }
            if (!$used) {
                $this->validator->remove($key);
            }
        }
        return $this;
    }
    public function removeValidator(): self
    {
        foreach ($this->getFields() as $field) {
            $field->delAttr('data-validate');
        }
        $this->validator = null;
        $this->validator_full = null;
        return $this;
    }
    public function setContext(mixed $key, mixed $value = null): self
    {
        if ($value === null && is_array($key)) {
            $this->context = $key;
        } else {
            $this->context[$key] = $value;
        }
        return $this;
    }
    /**
     * @return mixed
     */
    public function getContext(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->context : ($this->context[$key] ?? $default);
    }
    public function removeContext(string $key): self
    {
        unset($this->context[$key]);
        return $this;
    }
}
