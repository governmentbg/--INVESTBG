<?php

declare(strict_types=1);

namespace webadmin\api;

class Entity
{
    protected ?string $name;
    protected ?string $description;
    /** @var array<string,Field> */
    protected array $fields;
    protected string $type;
    public function __construct(?string $name = null, ?string $description = null, string $type = 'object')
    {
        $this->name = $name;
        $this->description = $description;
        $this->fields = [];
        $this->setType($type);
    }
    public function setType(string $type): static
    {
        if (!in_array($type, [ 'object', 'array' ])) {
            throw new \Exception('Invalid entity type');
        }
        $this->type = $type;

        return $this;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function addField(Field $field): static
    {
        if (isset($this->fields[$field->getName()])) {
            throw new \Exception('Field already exists');
        }
        $this->fields[$field->getName()] = $field;

        return $this;
    }
    /** @return array<string,Field>  */
    public function getFields(): array
    {
        return $this->fields;
    }
    public function getSchema(): array
    {
        if ($this->type === 'object') {
            $schema = [
                'type'          => 'object',
                'properties'    => [],
                'required'      => []
            ];
            foreach ($this->getFields() as $field) {
                $schema['properties'][$field->getName()] = $field->getSchema();

                if ($field->getRequired()) {
                    $schema['required'][] = $field->getName();
                }
            }
        } else {
            $schema = [
                'type'          => 'array',
                'items'         => [
                    'properties'    => [],
                    'required'      => []
                ]
            ];
            foreach ($this->getFields() as $field) {
                $schema['items']['properties'][$field->getName()] = $field->getSchema();

                if ($field->getRequired()) {
                    $schema['items']['required'][] = $field->getName();
                }
            }
        }

        return $schema;
    }
    public function getField(string $name): ?Field
    {
        return $this->fields[$name] ?? null;
    }
    public function removeField(string $name): static
    {
        unset($this->fields[$name]);

        return $this;
    }
    public function __clone()
    {
        $this->name = null;
    }
}
