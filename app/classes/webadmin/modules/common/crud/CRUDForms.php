<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use vakata\collection\Collection;
use vakata\database\schema\Entity;
use vakata\database\schema\Table;
use vakata\database\schema\TableColumn;
use vakata\database\schema\TableQuery;
use webadmin\components\html\Button;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\Table as HtmlTable;
use webadmin\components\html\TableColumn as HtmlTableColumn;
use webadmin\components\html\TableRow;
use vakata\validation\Validator;

/**
 * @template T of Entity
 * @implements CRUDFormsInterface<T>
 */
class CRUDForms implements CRUDFormsInterface
{
    /** @var CRUDModuleInterface<T,CRUDServiceInterface<T>> $module */
    protected CRUDModuleInterface $module;
    /** @var CRUDServiceInterface<T> $service */
    protected CRUDServiceInterface $service;
    protected CRUDEntityDefinition $definition;

    /**
     * @param CRUDModuleInterface<T,CRUDServiceInterface<T>> $module
     * @param CRUDServiceInterface<T> $service
     * @return void
     */
    public function __construct(
        CRUDModuleInterface $module,
        CRUDServiceInterface $service
    ) {
        $this->module = $module;
        $this->service = $service;
        $this->definition = $service->definition();
    }
    protected function validator(): Validator
    {
        $validator = new Validator();
        foreach ($this->definition->getFullColumns() as $name => $column) {
            $default = $column->getDefault();
            if (
                $default === null &&
                !$column->isNullable() &&
                in_array($column->getType(), ['tinytext', 'text', 'mediumtext', 'longtext'])
            ) {
                $default = '';
            }
            if (!$column->isNullable() && $default === null) {
                $validator->required($name);
            } else {
                $validator->optional($name);
            }
            switch ($column->getBasicType()) {
                case 'int':
                    $validator->int('integer');
                    break;
                case 'float':
                    $validator->float('float');
                    break;
                case 'enum':
                    $validator->inArray($column->getValues());
                    break;
                case 'date':
                case 'datetime':
                    $validator->date(null, 'date');
                    break;
                case 'text':
                    if ($column->hasLength() && $column->getLength()) {
                        $validator->maxLength((int)$column->getLength());
                    }
                    break;
            }
        }
        // override parent function so that there is no validation on primary key columns
        // primary key validation can be added manually in classes that extend this one
        foreach ($this->definition->getPrimaryKey() as $name) {
            $validator->remove($name);
        }
        return $validator;
    }
    protected function relatedValue(string $name, Entity $entity): mixed
    {
        $rels = $this->definition->getRelations();
        $enabled = $this->definition->getModules();
        if (isset($rels[$name]) && count($enabled[$name])) {
            if ($rels[$name]->many) {
                $temp = [];
                foreach ($entity->{$name} as $e) {
                    $id = [];
                    foreach ($rels[$name]->table->getPrimaryKey() as $col) {
                        $id[] = $e->{$col};
                    }
                    $temp[] = implode(',', $id);
                }
                return $temp;
            } else {
                $e = $entity->{$name};
                if ($e) {
                    $id = [];
                    foreach ($rels[$name]->table->getPrimaryKey() as $col) {
                        $id[] = $e->{$col};
                    }
                    return implode(',', $id);
                } else {
                    return null;
                }
            }
        }
        return null;
    }
    public function base(): Form
    {
        $form = new Form();
        $form->setContext('type', 'base');
        $name = $this->module->getName();
        /** @var array<string,TableColumn> $cols */
        $cols = Collection::from($this->definition->getFullColumns())
            ->mapKey(function (TableColumn $v, string $k): string {
                return strtolower($k);
            })->toArray();
        $pkey = $this->definition->getPrimaryKey();
        $rels = $this->definition->getRelations();
        $modules = $this->definition->getModules();
        foreach ($cols as $k => $v) {
            $found = false;
            if (!in_array($k, $pkey)) {
                foreach ($rels as $r) {
                    if (
                        !$r->many &&
                        !$r->pivot &&
                        isset($r->keymap[$k]) &&
                        $r->table->getName() === 'uploads'
                    ) {
                        $form->addField(
                            new Field(
                                $k === 'image' ? 'image' : 'file',
                                [ 'name' => $k ],
                                [
                                    'label' => $name . '.columns.' . $k
                                ]
                            )
                        );
                        $found = true;
                        break;
                    }
                    if (
                        !$r->many &&
                        !$r->pivot &&
                        isset($r->keymap[$k]) &&
                        count($ms = $modules[$r->name] ?? [])
                    ) {
                        $form->addField(
                            new Field(
                                'relation',
                                [ 'name' => $k ],
                                [
                                    'label' => $name . '.columns.' . $k,
                                    'modules' => $ms,
                                    'id' => implode(',', $r->table->getPrimaryKey())
                                ]
                            )
                        );
                        $found = true;
                        break;
                    }
                }
            }
            if ($found) {
                continue;
            }
            switch ($v->getBasicType()) {
                case 'date':
                    $field = new Field('date', [ 'name' => $k ], [ 'label' => $name . '.columns.' . (string)$k ]);
                    break;
                case 'datetime':
                    $field = new Field('datetime', [ 'name' => $k ], [ 'label' => $name . '.columns.' . (string)$k ]);
                    break;
                case 'enum':
                    $field = new Field(
                        'select',
                        [ 'name' => $k ],
                        [ 'label' => $name . '.columns.' . (string)$k, 'values' => $v->getValues() ]
                    );
                    break;
                case 'int':
                    // do not use field type number! getting the value in JS is faulty
                    $field = new Field('text', [ 'name' => $k ], [ 'label' => $name . '.columns.' . (string)$k ]);
                    break;
                default:
                    $field = new Field(
                        'text',
                        [ 'name' => $k ],
                        [ 'label' => $name . '.columns.' . (string)$k ]
                    );
                    if ($v->hasLength() && $v->getLength()) {
                        $field->setAttr('maxlength', $v->getLength());
                    }
                    break;
            }
            if (in_array($k, $pkey)) {
                $field->hide();
            }
            $form->addField($field);
        }
        foreach ($rels as $r) {
            if ($r->many && $r->table->getName() === 'uploads') {
                $form->addField(
                    (new Field(
                        'files',
                        [ 'name' => $r->name . '[]' ],
                        [
                            'label' => $name . '.columns.' . $r->name
                        ]
                    ))->hide()
                );
                continue;
            }
            if ($r->many && ($m = $modules[$r->name] ?? null)) {
                $form->addField(
                    (new Field(
                        'relation',
                        [
                            'name' => $r->name . '[]',
                            'multiple' => 'multiple'
                        ],
                        [
                            'label' => $name . '.columns.' . $r->name,
                            'modules' => $m,
                            'multiple' => true,
                            'id' => implode(',', $r->table->getPrimaryKey())
                        ]
                    ))->hide()
                );
            }
        }
        return $form;
    }
    /**
     * @param array<string,mixed> $data
     * @return Form
     */
    public function create(array $data = []): Form
    {
        return $this->module->formCallback(
            $this->base()
                ->setContext('type', 'create')
                ->setContext('data', $data)
                ->populate($data)
                ->setValidator($this->validator())
        );
        // ->populate($data);
    }
    /**
     * @param T $entity
     * @return Form
     */
    public function read(Entity $entity): Form
    {
        $form = $this->base()
            ->setContext('entity', $entity)
            ->setContext('type', 'read')
            ->populate($this->service->toArray($entity))
            ->disable();
        $rels = $this->definition->getRelations();
        $modules = $this->definition->getModules();
        foreach ($rels as $r) {
            if ($r->many && isset($modules[$r->name]) && count($modules[$r->name])) {
                try {
                    $field = $form->getField($r->name . '[]');
                    if (in_array($field->getType(), [ 'relation', 'module'])) {
                        $name = str_replace('[]', '', (string)$field->getName());
                        $field->setValue(function () use ($name, $entity): mixed {
                            return $this->relatedValue($name, $entity);
                        });
                    }
                } catch (\Exception) {
                    // ignore missing field
                }
            }
        }
        return $this->module->formCallback($form);
    }
    /**
     * @param T $entity
     * @param array<string,mixed> $data
     * @return Form
     */
    public function update(Entity $entity, array $data = []): Form
    {
        $form = $this->base()
            ->setContext('entity', $entity)
            ->setContext('data', $data)
            ->setContext('type', 'update')
            ->populate($this->service->toArray($entity))
            ->populate($data)
            ->setValidator($this->validator());
        $rels = $this->definition->getRelations();
        $modules = $this->definition->getModules();
        foreach ($rels as $r) {
            if ($r->many && isset($modules[$r->name]) && count($modules[$r->name])) {
                try {
                    $field = $form->getField($r->name . '[]');
                    if ($field->isHidden() && in_array($field->getType(), [ 'relation', 'module'])) {
                        $name = str_replace('[]', '', (string)$field->getName());
                        $field->setValue(
                            isset($data[$name]) ?
                                $data[$name] :
                                function () use ($name, $entity): mixed {
                                    return $this->relatedValue($name, $entity);
                                }
                        );
                    }
                } catch (\Exception) {
                    // ignore missing field
                }
            }
        }
        return $this->module->formCallback($form);
    }

    /**
     * @param T $entity
     * @return Form
     */
    public function delete(Entity $entity): Form
    {
        $form = $this->base()
            ->setContext('entity', $entity)
            ->setContext('type', 'delete')
            ->populate($this->service->toArray($entity))
            ->disable();
        $rels = $this->definition->getRelations();
        $modules = $this->definition->getModules();
        foreach ($rels as $r) {
            if ($r->many && isset($modules[$r->name]) && count($modules[$r->name])) {
                try {
                    $field = $form->getField($r->name . '[]');
                    if ($field->isHidden() && in_array($field->getType(), [ 'relation', 'module'])) {
                        $name = str_replace('[]', '', (string)$field->getName());
                        $field->setValue(function () use ($name, $entity): mixed {
                            return $this->relatedValue($name, $entity);
                        });
                    }
                } catch (\Exception) {
                    // ignore missing field
                }
            }
        }
        return $this->module->formCallback($form);
    }
    /**
     * @return Form
     */
    public function history(array $data = []): Form
    {
        return $this->module->formCallback(
            $this->base()->setContext('type', 'history')->setContext('data', $data)->populate($data)
        )->disable()->populate($data);
    }
    /**
     * @param T $entity
     * @param array $data
     * @return Form
     */
    public function copy(Entity $entity, array $data = []): Form
    {
        $form = $this->base()
            ->setContext('entity', $entity)
            ->setContext('data', $data)
            ->setContext('type', 'copy')
            ->populate($this->service->toArray($entity))
            ->populate($data)
            ->setValidator($this->validator());
        $rels = $this->definition->getRelations();
        $modules = $this->definition->getModules();
        foreach ($rels as $r) {
            if ($r->many && isset($modules[$r->name]) && count($modules[$r->name])) {
                try {
                    $field = $form->getField($r->name . '[]');
                    if ($field->isHidden() && in_array($field->getType(), [ 'relation', 'module'])) {
                        $name = str_replace('[]', '', (string)$field->getName());
                        $field->setValue(
                            isset($data[$name]) ?
                                $data[$name] :
                                function () use ($name, $entity): mixed {
                                    return $this->relatedValue($name, $entity);
                                }
                        );
                    }
                } catch (\Exception) {
                    // ignore missing field
                }
            }
        }
        return $this->module->formCallback($form);
    }
    /**
     * @param iterable<T> $entities
     * @return HtmlTable
     */
    public function listing(iterable $entities, array $params = [], ?int $count = null): HtmlTable
    {
        $name = $this->module->getName();
        $slug = $this->module->getSlug();
        $table = new HtmlTable();
        $table->setAttr('x-data-name', $name);
        $table->setAttr('x-data-paging', true);
        $table->setAttr('x-data-params', $params);
        $table->setAttr('x-data-filters', []);
        $table->setAttr('x-data-count', $count);
        $table->addClass('basic selectable compact table-read');
        if ($this->module->canCreate() && $this->service->creatable()) {
            $table->addOperation(
                (new Button("create"))
                    ->setLabel($name . '.operations.create')
                    ->setIcon('plus')
                    ->setClass('green icon labeled button')
                    ->setAttr('href', $slug . '/create')
            );
            $table->addOperation(
                (new Button("import"))
                    ->setIcon('download')
                    ->setClass('yellow icon button')
                    ->setAttr('href', $slug . '/import')
                    ->hide()
            );
        }
        $table->addOperation(
            (new Button("export"))
                ->setIcon('upload')
                ->setClass('olive icon button export-button')
                ->setAttr('href', $slug . '/export')
                ->hide()
        );
        $table->addOperation(
            (new Button("thumb"))
                ->setIcon('th list')
                ->setClass('purple icon button thumb-button')
                ->setAttr('href', '')
                ->hide()
        );
        $visible = $this->definition->getColumns();
        $schema = $entities instanceof TableQuery ? $entities->schema() : null;
        $pkey = $this->definition->getPrimaryKey();
        Collection::from($this->definition->getFullColumns())
            ->mapKey(function (TableColumn $v, string $k): string {
                return strtolower($k);
            })
            ->filter(function (TableColumn $v, string $k) use ($visible, $pkey) {
                return in_array($k, $visible) && !in_array($k, $pkey);
            })
            ->filter(function (TableColumn $v, string $k) use ($schema, $pkey) {
                return !isset($schema) || (in_array($k, $schema) && !in_array($k, $pkey));
            })
            ->map(function (TableColumn $v, string $k) use ($name): HtmlTableColumn {
                $column = new HtmlTableColumn($k);
                $modules = [];
                $relation = null;
                foreach ($this->definition->getRelations() as $relation) {
                    if (isset($relation->keymap[$k])) {
                        $modules = $this->definition->getModule($relation->name);
                        break;
                    }
                }
                if (count($modules) && $relation) {
                    $column->setQuickFilter($v->getName());
                    $column->setFilter(
                        (new Form())
                            ->addField(
                                (new Field(
                                    'relation',
                                    [
                                        'name' => $k . '[]',
                                        'multiple' => 'multiple'
                                    ],
                                    [
                                        'label' => $name . '.filters.' . $v->getName(),
                                        'modules' => $modules,
                                        'multiple' => true,
                                        'id' => implode(',', $relation->table->getPrimaryKey())
                                    ]
                                ))
                            )
                    );
                } elseif ($v->getBasicType() === 'date') {
                    $column->setQuickFilter($v->getName());
                    $column->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "date",
                                ['name' => $v->getName() . '[beg]'],
                                ['label' => $name . '.filters.' . $v->getName() . '.beg' ]
                            ))
                            ->addField(new Field(
                                "date",
                                ['name' => $v->getName() . '[end]'],
                                ['label' => $name . '.filters.' . $v->getName() . '.end' ]
                            ))
                            ->setLayout([[$v->getName() . '[beg]', $v->getName() . '[end]']])
                    );
                } elseif ($v->getBasicType() === 'datetime') {
                    $column->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "datetime",
                                ['name' => $v->getName() . '[beg]'],
                                ['label' => $name . '.filters.' . $v->getName() . '.beg' ]
                            ))
                            ->addField(new Field(
                                "datetime",
                                ['name' => $v->getName() . '[end]'],
                                ['label' => $name . '.filters.' . $v->getName() . '.end' ]
                            ))
                            ->setLayout([[$v->getName() . '[beg]', $v->getName() . '[end]']])
                    );
                } elseif ($v->getBasicType() === 'enum') {
                    $column->setQuickFilter($v->getName());
                    $column->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "multipleselect",
                                ['name' => $v->getName() . '[]'],
                                [
                                    'label' => $name . '.filters.' . $v->getName(),
                                    'values' => array_combine($v->getValues(), $v->getValues())
                                ]
                            ))
                    );
                } elseif ($v->getBasicType() === 'int') {
                    $column->setQuickFilter($v->getName());
                    $column->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "text",
                                ['name' => $v->getName()],
                                [
                                    'label' => $name . '.filters.' . $v->getName()
                                ]
                            ))
                    );
                } elseif ($v->getBasicType() === 'text') {
                    $column->setQuickFilter($v->getName());
                    $column->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "select",
                                [ 'class' => 'filter-modifier' ],
                                [
                                    'label' => $name . '.filters.' . $v->getName(),
                                    'translate' => true,
                                    'values' => [
                                        $v->getName() => 'filter_equals',
                                        $v->getName() . '[like]' => 'filter_like',
                                        $v->getName() . '[ilike]' => 'filter_ilike',
                                        $v->getName() . '[contains]' => 'filter_contains',
                                        $v->getName() . '[icontains]' => 'filter_icontains',
                                        $v->getName() . '[ends]' => 'filter_ends',
                                        $v->getName() . '[iends]' => 'filter_iends',
                                        $v->getName() . '[not]' => 'filter_not_equals',
                                        $v->getName() . '[not][like]' => 'filter_not_like',
                                        $v->getName() . '[not][ilike]' => 'filter_not_ilike',
                                        $v->getName() . '[not][contains]' => 'filter_not_contains',
                                        $v->getName() . '[not][icontains]' => 'filter_not_icontains',
                                        $v->getName() . '[not][ends]' => 'filter_not_ends',
                                        $v->getName() . '[not][iends]' => 'filter_not_iends',
                                    ]
                                ]
                            ))
                            ->addField(new Field(
                                "text",
                                ['name' => $v->getName(), 'class' => 'filter-modified']
                            ))
                    );
                }
                return $column;
            })
            ->each(function (HtmlTableColumn $v) use ($table) {
                $table->addColumn($v);
            });
        foreach ($entities as $v) {
            $table->addRow(
                $this->row($v, implode('|', $this->service->id($v)))
            );
        }
        return $this->module->listingCallback($table);
    }
    /**
     * @param T $entity
     * @param mixed $id
     * @return TableRow
     */
    protected function row(Entity $entity, mixed $id): TableRow
    {
        $name = $this->module->getName();
        $slug = $this->module->getSlug();
        $row = (new TableRow($entity))
            ->setAttr('id', $id)
            ->setData($entity);
        if ($this->module->canRead() && $this->service->readable($entity)) {
            $row->addOperation(
                (new Button("read"))
                    ->setLabel($name . '.operations.read')
                    ->setIcon('eye')
                    ->setClass('mini teal icon button')
                    ->setAttr('href', $slug . '/read/' . $id)
            );
        }
        if ($this->module->canUpdate() && $this->service->updatable($entity)) {
            $row->addOperation(
                (new Button("update"))
                    ->setLabel($name . '.operations.update')
                    ->setIcon('pencil')
                    ->setClass('mini orange icon button')
                    ->setAttr('href', $slug . '/update/' . $id)
            );
        }
        if ($this->module->canCopy()) {
            $row->addOperation(
                (new Button("copy"))
                    ->setLabel($name . '.operations.copy')
                    ->setIcon('copy')
                    ->setClass('mini purple icon button')
                    ->setAttr('href', $slug . '/copy/' . $id)
                    ->hide()
            );
        }
        if ($this->module->canDelete() && $this->service->deletable($entity)) {
            $row->addOperation(
                (new Button("delete"))
                    ->setLabel($name . '.operations.delete')
                    ->setIcon('remove')
                    ->setClass('mini red icon button')
                    ->setAttr('href', $slug . '/delete/' . $id)
            );
        }
        if ($this->module->hasHistory()) {
            $row->addOperation(
                (new Button("history"))
                    ->setLabel($name . '.operations.history')
                    ->setIcon('history')
                    ->setClass('mini grey icon button')
                    ->setAttr('href', $slug . '/history/' . $id)
                    ->hide()
            );
        }
        foreach ($this->definition->getModules() as $name => $relation) {
            if ($this->definition->getRelation($name)?->many) {
                foreach ($relation as $n => $m) {
                    $row->addOperation(
                        (new Button($name))
                            ->setLabel($m->getName() . '.title')
                            ->setIcon($m->getIcon())
                            ->setClass('mini ' . $m->getColor() . ' icon button')
                            ->setAttr('href', $slug . '/relation/' . $id . '/' . $name . '/' . $n)
                            ->hide()
                    );
                }
            }
        }
        return $row;
    }
}
