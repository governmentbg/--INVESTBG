<?php

declare(strict_types=1);

namespace webadmin\api;

class Parameter extends Field
{
    protected bool $deprecated;
    protected bool $allowEmptyValue;
    protected ?string $description;

    public function __construct(
        string $name,
        string $type,
        ?string $format = null,
        bool $required = false,
        ?string $example = null,
        mixed $values = null,
        ?string $description = null,
        bool $deprecated = false,
        bool $allowEmptyValue = false
    ) {
        parent::__construct($name, $type, $format, $required, $example, $values);
        $this->description = $description;
        $this->deprecated = $deprecated;
        $this->allowEmptyValue = $allowEmptyValue;
    }
    public function getSchema(?string $in = null): array
    {
        $schema = [
            'name'      => $this->name,
            'in'        => $in,
            'required'  => $this->required,
            'schema'    => [
                'type'          => $this->type,
                'format'        => $this->format
            ]
        ];

        if (is_array($this->values) && count($this->values)) {
            $schema['schema']['enum'] = $this->values;
        } elseif ($this->type === 'array') {
            if ($this->values instanceof Entity) {
                $schema['schema']['items'] = $this->values->getSchema();
            } else {
                $schema['schema']['items']['type'] = $this->values;
            }
        }

        if ($this->example) {
            $schema['example'] = $this->example;
        }
        if ($this->description) {
            $schema['description'] = $this->description;
        }

        return $schema;
    }
}
