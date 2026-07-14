<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use webadmin\api\API;
use webadmin\api\Entity;
use webadmin\api\Field;
use webadmin\api\Parameter;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use Exception;

class CRUDAPI
{
    public function __construct(protected CRUDEntityDefinition $definition, protected API $api)
    {
    }
    /**
     * @param string[] $columns
     * @return array{local:array<int,string>,remote:array<string,array<int,string>>}
     */
    protected function columns(array $columns): array
    {
        $data = [
            'local'     => [],
            'remote'    => []
        ];

        foreach ($columns as $column) {
            $parts = explode('.', $column);

            if (count($parts) === 1) {
                $data['local'][] = $parts[0];
            } elseif (count($parts) === 2 && $this->definition->hasRelation($parts[0])) {
                if (!isset($data['remote'][$parts[0]])) {
                    $data['remote'][$parts[0]] = [];
                }
                $data['remote'][$parts[0]][] = $parts[1];
            }
        }

        return $data;
    }
    public function request(Form $form): Entity
    {
        $entity = new Entity();
        $validator = $form->getValidator();

        foreach ($form->getFields() as $field) {
            if ($field->getType() === 'hidden' || $field->isHidden()) {
                continue;
            }
            $required = false;
            foreach ($validator->rules($field->getName()) as $rule) {
                if ($rule->isRequired()) {
                    $required = true;
                    break;
                }
            }
            $item = [
                'name'      => str_replace('[]', '', $field->getName()),
                'type'      => 'string',
                'required'  => $required,
                'format'    => null,
                'example'   => null,
                'values'    => null
            ];

            switch ($field->getType()) {
                case 'text':
                case 'mail':
                case 'richtext':
                case 'tel':
                case 'textarea':
                case 'url':
                case 'color':
                    $item['type'] = 'string';
                    break;
                case 'password':
                    $item['type'] = 'string';
                    $item['format'] = 'password';
                    break;
                case 'number':
                case 'file':
                case 'image':
                    $item['type'] = 'integer';
                    $item['format'] = 'int64';
                    break;
                case 'select':
                case 'radio':
                    $values = $field->getOption('values', []);
                    $item['values'] = array_keys($values);
                    $item['type'] = count($item['values']) ?
                        gettype($item['values'][0]) :
                        'string';
                    break;
                case 'checkbox':
                    $item['type'] = 'boolean';
                    $item['values'] = [ true, false ];
                    break;
                case 'date':
                    $item['type'] = 'string';
                    $item['example'] = 'YYYY-mm-dd HH:ii:ss';
                    break;
                case 'datetime':
                    $item['type'] = 'string';
                    $item['example'] = 'YYYY-mm-dd';
                    break;
                case 'time':
                    $item['type'] = 'string';
                    $item['example'] = 'HH:ii:ss';
                    break;
                case 'relation':
                case 'files':
                case 'images':
                    $item['type'] = 'array';
                    $item['values'] = 'integer';
                    break;
                case 'module':
                case 'tree':
                    if ($field->getOption('multiple', false)) {
                        $item['type'] = 'array';
                        $item['values'] = 'integer';
                    } else {
                        $item['type'] = 'integer';
                        $item['format'] = 'int64';
                    }
                    break;
                case 'multipleselect':
                case 'tags':
                    $item['type'] = 'array';
                    $values = $field->getOption('values', []);
                    $item['values'] = count($values) ?
                        gettype($values[0]) :
                        'string';
                    break;
                case 'json':
                    $item['type'] = 'array';
                    $item['values'] = $this->request($field->getOption('form'));
                    break;
                default:
                    break;
            }

            $entity->addField(
                new Field(
                    $item['name'],
                    $item['type'],
                    $item['format'],
                    $item['required'],
                    $item['example'],
                    $item['values'] ?? null
                )
            );
        }

        return $entity;
    }
    /**
     * @param Entity $base
     * @param string[] $columns
     * @return Entity
     */
    public function response(Entity $base, array $columns): Entity
    {
        $entity = new Entity();

        $columns = $this->columns($columns);

        foreach ($columns['local'] as $column) {
            if ($base->getField($column)) {
                /** @psalm-suppress PossiblyNullArgument */
                $entity->addField($base->getField($column));
            }
        }
        foreach ($columns['remote'] as $table => $cols) {
            if (
                ($field = $base->getField($table)) &&
                in_array($field->getType(), [ 'array', 'object' ]) &&
                $field->getValues() instanceof Entity
            ) {
                /** @var Entity $values  */
                $values = $field->getValues();
                $rel = new Entity();

                foreach ($values->getFields() as $value) {
                    if (in_array($value->getName(), $cols)) {
                        $rel->addField($value);
                    }
                }

                $field->setValues($rel);
                $entity->addField($field);
            }
        }

        return $entity;
    }
    /**
     * @param Table $table
     * @param Entity $request
     * @return array{entity:Entity,params:array<int,Parameter>}
     * @throws Exception
     */
    public function list(Table $table, Entity $request, array $queryColumns): array
    {
        $entity = new Entity();
        $params = [];

        $name = $this->definition->getName();
        $component = $this->api->hasComponent($name) ?
            $this->api->getComponent($name) :
            null;
        $sortable = [];

        $columns = $this->columns($queryColumns);

        foreach ($queryColumns as $column) {
            if ($table->hasColumn($column)) {
                if (
                    $table->getColumn($column)->getFilter() &&
                    ($requestField = $request->getField($column))
                ) {
                    $params[] = new Parameter(
                        $requestField->getName(),
                        $requestField->getType(),
                        $requestField->getFormat(),
                        $requestField->getRequired(),
                        $requestField->getExample(),
                        $requestField->getValues()
                    );
                }
                if ($table->getColumn($column)->isSortable()) {
                    $sortable[] = $column;
                }
            }
        }

        foreach ($columns['local'] as $column) {
            if (!$component || !($field = $component->getField($column))) {
                if ($schemaColumn = $this->definition->getColumn($column)) {
                    $type = API::getTypeAndFormat($schemaColumn->getBasicType());

                    $field = new Field(
                        $column,
                        $type['type'],
                        $type['format'],
                        !$schemaColumn->isNullable()
                    );
                }
            }

            if (isset($field)) {
                $entity->addField($field);
            }
        }
        foreach ($columns['remote'] as $tableName => $columns) {
            if ($component && ($field = $component->getField($tableName))) {
                if ($field->getValues() instanceof Entity && $this->definition->getRelation($tableName)) {
                    /** @var Entity $rel */
                    $rel = clone $field->getValues();

                    foreach ($rel->getFields() as $item) {
                        if (!in_array($item->getName(), $columns)) {
                            $rel->removeField($item->getName());
                        }
                    }

                    /** @psalm-suppress PossiblyNullPropertyFetch */
                    $entity->addField(
                        new Field(
                            $tableName,
                            $this->definition->getRelation($tableName)->many ? 'array' : 'object',
                            null,
                            false,
                            null,
                            $rel
                        )
                    );
                }
            } else {
                $rel = new Entity();
                $related = $this->definition
                    ->getRelation($tableName)
                    ?->table;

                if ($related instanceof \vakata\database\schema\Table) {
                    foreach ($columns as $column) {
                        if ($related->getColumn($column)) {
                            /** @psalm-suppress PossiblyNullReference */
                            $type = API::getTypeAndFormat($related->getColumn($column)->getBasicType());
                            /** @psalm-suppress PossiblyNullReference */
                            $rel->addField(
                                new Field(
                                    $column,
                                    $type['type'],
                                    $type['format'],
                                    !$related->getColumn($column)->isNullable()
                                )
                            );
                        }
                    }
                }
            }
        }

        if (count($sortable)) {
            $params[] = new Parameter(
                'o',
                'string',
                null,
                false,
                null,
                $sortable,
                'Field to order by'
            );
            $params[] = new Parameter(
                'd',
                'integer',
                'int64',
                false,
                null,
                [ 0, 1 ],
                'ASC/DESC'
            );
        }
        if ($table->getAttr('x-data-search', true)) {
            $params[] = new Parameter(
                'q',
                'string'
            );
        }

        return [
            'entity'    => $entity,
            'params'    => $params
        ];
    }
}
