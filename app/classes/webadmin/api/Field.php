<?php

declare(strict_types=1);

namespace webadmin\api;

class Field
{
    protected string $name;
    protected string $type;
    protected ?string $format;
    protected ?string $example;
    protected bool $required;
    protected mixed $values;

    public const array TYPES = [ 'null', 'boolean', 'object', 'array', 'number', 'string', 'integer' ];
    public const array FORMATS = [ 'int32', 'int64', 'float', 'double', 'password' ];

    public function __construct(
        string $name,
        string $type,
        ?string $format = null,
        bool $required = false,
        ?string $example = null,
        mixed $values = null
    ) {
        $this->name = $name;

        if (!in_array($type, static::TYPES)) {
            throw new \Exception('Invalid parameter type');
        }
        $this->type = $type;

        if (isset($format)) {
            if (!in_array($format, static::FORMATS)) {
                throw new \Exception('Invalid parameter type');
            }
        }
        $this->format = $format;
        $this->required = $required;
        $this->values = $values;
        $this->example = $example;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getType(): string
    {
        return $this->type;
    }
    public function getFormat(): ?string
    {
        return $this->format;
    }
    public function getRequired(): bool
    {
        return $this->required;
    }
    public function getSchema(): array
    {
        $schema = [
            'type'  => $this->getType()
        ];

        if ($this->getExample()) {
            $schema['example'] = $this->getExample();
        }
        if ($this->getFormat()) {
            $schema['format'] = $this->getFormat();
        }

        if ($this->type === 'array') {
            if ($this->values instanceof Entity) {
                if ($this->values->getName()) {
                    $schema['items'] = [
                        '$ref' => '#/components/schemas/' . $this->values->getName()
                    ];
                } else {
                    $schema['items'] = $this->values->getSchema();
                }
            } elseif (is_string($this->values)) {
                $schema['items']['type'] = $this->values;
            }
        } elseif ($this->type === 'object' && $this->values instanceof Entity) {
            $schema = $this->values->getSchema();
        } elseif (is_array($this->values)) {
            $schema['enum'] = $this->values;
        }

        return $schema;
    }
    public function getExample(): ?string
    {
        return $this->example;
    }
    public function setValues(mixed $values): static
    {
        $this->values = $values;

        return $this;
    }
    public function getValues(): mixed
    {
        return $this->values;
    }
}
