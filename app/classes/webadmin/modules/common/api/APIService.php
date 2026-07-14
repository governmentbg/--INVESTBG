<?php

declare(strict_types=1);

namespace webadmin\modules\common\api;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use vakata\cache\CacheInterface;
use vakata\config\Config;
use vakata\database\DBInterface;
use webadmin\api\API;
use webadmin\api\APIProviderInterface;
use webadmin\api\Entity;
use webadmin\api\Error;
use webadmin\api\Field;
use webadmin\modules\ModulesContainer;
use vakata\database\DBException;

class APIService
{
    public function __construct(
        protected Config $config,
        protected CacheInterface $cache,
        protected DBInterface $db
    ) {
    }
    /**
     * @return array<string,
     * array<int,array{name:string,type:string,format:?string,required:bool,example:?string,values:mixed}>>
     * @throws DBException
     */
    public function responseEnties(): array
    {
        $schema = $this->db->getSchema();
        $tables = [];
        $pivots = [];
        foreach ($schema->getTables() as $table) {
            $tables[] = $table->getName();
            foreach ($table->getRelations() as $relation) {
                $pivots[] = $relation->pivot?->getName();
            }
        }
        $tables = array_unique(array_filter($tables));
        $pivots = array_unique(array_filter($pivots));

        $fields = [];
        foreach ($tables as $table) {
            if (in_array($table, $pivots)) {
                continue;
            }
            /** @var class-string<\vakata\database\schema\Entity> $clss */
            $clss = 'schema\\' . implode('', array_map('ucfirst', array_filter(explode('_', $table)))) . "Entity";

            $reflection = new ReflectionClass($clss);
            $comments = explode("\n", (string) $reflection->getDocComment());

            foreach ($comments as $comment) {
                $comment = trim($comment);
                $comment = ltrim($comment, '*');
                $comment = ltrim($comment);

                if (strpos($comment, '@property') === 0) {
                    $field = [
                        'name'      => null,
                        'type'      => null,
                        'format'    => null,
                        'required'  => false,
                        'example'   => null,
                        'values'    => null
                    ];
                    $comment = explode(' ', $comment);

                    if (strpos($comment[1], '?') !== 0) {
                        $field['required'] = true;
                        $type = $comment[1];
                    } else {
                        $type = ltrim($comment[1], '?');
                    }
                    $field['name'] = ltrim($comment[2], '$');

                    switch ($type) {
                        case 'int':
                            $field['type'] = 'number';
                            $field['format'] = 'int64';
                            break;
                        case 'float':
                            $field['type'] = 'number';
                            $field['format'] = 'float';
                            break;
                        case 'bool':
                            $field['type'] = 'boolean';
                            break;
                        case 'string':
                            $field['type'] = 'string';
                            $column = $schema->getTable($table)->getColumn($field['name']);

                            if (!$column) {
                                break;
                            }

                            switch ($column->getBasicType()) {
                                case 'datetime':
                                    $field['example'] = 'YYYY-mm-dd HH:ii:ss';
                                    break 2;
                                case 'date':
                                    $field['example'] = 'YYYY-mm-dd';
                                    break 2;
                                case 'time':
                                    $field['example'] = 'HH:ii:ss';
                                    break 2;
                                default:
                                    break 2;
                            }
                        default:
                            if (strpos($type, '\vakata\collection\Collection') === 0) {
                                $field['type'] = 'array';
                                $field['values'] = trim(explode(',', $type)[1], '>');
                            } else {
                                $field['type'] = 'object';
                                $field['values'] = $type;
                            }
                            break;
                    }
                    $fields[$table][] = $field;
                }
            }

            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if (!($property->getModifiers() & ReflectionProperty::IS_READONLY)) {
                    $field = [
                        'name'      => $property->getName(),
                        'type'      => null,
                        'format'    => null,
                        'required'  => !$property->getType()?->allowsNull(),
                        'example'   => null,
                        'values'    => null
                    ];
                    if ($property->getType() instanceof ReflectionNamedType) {
                        $type = API::getTypeAndFormat($property->getType()->getName());
                        $field['type'] = $type['type'];
                        $field['format'] = $type['format'];
                    } else {
                        $field['type'] = 'string';
                    }

                    $fields[$table][] = $field;
                }
            }
        }

        return $fields;
    }
    public function api(string $path, ModulesContainer $modules): API
    {
        $api = new API(
            $this->config->getString('API_TITLE'),
            $this->config->getString('VERSION'),
            'application/json'
        );
        $api->addServer($path)
            ->registerComponent(
                (new Error('400', 'Bad request', 400))
                    ->addField(
                        new Field(
                            'errors',
                            'array',
                            null,
                            true,
                            null,
                            'string'
                        )
                    )
            )
            ->registerComponent(
                (new Error('403', 'Forbidden', 403))
                    ->addField(
                        new Field(
                            'errors',
                            'array',
                            null,
                            true,
                            null,
                            'string'
                        )
                    )
            )
            ->registerComponent(new Error('500', 'Internal Server Error', 500))
            ->registerComponent(
                (new Error('404', 'Not Found', 404))
                    ->addField(
                        new Field(
                            'errors',
                            'array',
                            null,
                            true,
                            null,
                            'string'
                        )
                    )
            );

        $fields = $this->responseEnties();
        foreach ($fields as $table => $items) {
            $object = new Entity($table);
            foreach ($items as $field) {
                $object->addField(
                    new Field(
                        $field['name'],
                        $field['type'],
                        $field['format'],
                        $field['required'],
                        $field['example']
                    )
                );
            }
            $api->registerComponent($object);
        }
        foreach ($fields as $table => $items) {
            if ($api->hasComponent($table)) {
                $object = $api->getComponent($table);

                foreach ($items as $field) {
                    if (
                        in_array($field['type'], [ 'array', 'object' ]) &&
                        ($f = $object->getField($field['name'])) &&
                        $api->hasComponent($field['name'])
                    ) {
                        $temp = clone $api->getComponent($field['name']);
                        foreach ($fields[$field['name']] as $item) {
                            if ($item['type'] === 'array' || $item['type'] === 'object') {
                                $temp->removeField($item['name']);
                            }
                        }

                        $f->setValues($temp);
                    }
                }
            }
        }
        foreach ($modules as $module) {
            if (
                $module instanceof APIProviderInterface
            ) {
                $module->getEndpoints($api);
            }
        }

        return $api;
    }
}
