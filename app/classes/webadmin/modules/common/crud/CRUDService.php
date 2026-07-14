<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use vakata\database\DBInterface;
use vakata\database\DBException;
use vakata\database\schema\Entity;
use vakata\database\schema\MapperInterface;
use vakata\database\schema\Table;
use vakata\database\schema\TableQueryMapped;
use vakata\user\User;

/**
 * @template T of Entity
 * @implements CRUDServiceInterface<T>
 * @SuppressWarnings("PHPMD.NumberOfChildren")
 */
class CRUDService implements CRUDServiceInterface
{
    /** @var CRUDModuleInterface<T,CRUDServiceInterface<T>> */
    protected CRUDModuleInterface $module;
    protected DBInterface $db;
    protected User $user;
    protected Table $table;
    /** @var MapperInterface<T> $mapper */
    protected MapperInterface $mapper;
    protected string $nameColumn = '';

    /**
     * @param CRUDModuleInterface<T,CRUDServiceInterface<T>> $module
     */
    public function __construct(CRUDModuleInterface $module, DBInterface $db, User $user)
    {
        $this->module = $module;
        $this->db = $db;
        $this->user = $user;
        $this->table = $this->db->definition($this->module->getTable());
        /** @var MapperInterface<T> $temp */
        $temp = $this->db->getMapper($this->table);
        $this->mapper = $temp;
        foreach ($this->table->getFullColumns() as $column => $definition) {
            if (
                !in_array($column, $this->table->getPrimaryKey()) &&
                $definition->getBasicType() === 'text'
            ) {
                $this->nameColumn = $column;
                break;
            }
        }
    }
    public function creatable(?array $data = null): bool
    {
        return true;
    }
    public function updatable(Entity $entity, ?array $data = null): bool
    {
        return true;
    }
    public function deletable(Entity $entity): bool
    {
        return true;
    }
    public function readable(Entity $entity): bool
    {
        return true;
    }
    public function definition(): CRUDEntityDefinition
    {
        $definition = CRUDEntityDefinition::fromTable($this->table);
        if ($this->module instanceof CRUDModule) {
            $modules = $this->module::$modules;
            foreach ($this->table->getRelations() as $rname => $relation) {
                $rmodules = $modules?->byTable($relation->table->getName()) ?? [];
                foreach ($rmodules as $rmodule) {
                    /** @psalm-suppress all */
                    $definition->addModule($rname, $rmodule);
                }
            }
        }
        return $definition;
    }
    /**
     * @return TableQueryMapped<T>
     */
    protected function entities(): TableQueryMapped
    {
        /** @var TableQueryMapped<T> */
        return $this->db->tableMapped($this->table->getFullName());
    }
    /**
     * @return TableQueryMapped<T>
     */
    public function listQuery(): TableQueryMapped
    {
        /** @var TableQueryMapped<T> */
        return $this->entities()->limit(25);
    }
    /**
     * @return TableQueryMapped<T>
     */
    public function readQuery(): TableQueryMapped
    {
        return $this->entities();
    }
    /**
     * @param array<string,mixed> $data
     * @return T
     */
    protected function entity(array $data = []): Entity
    {
        /** @var T */
        return $this->db->tableMapped($this->table->getFullName())->create($data);
    }
    /**
     * @param T $entity
     * @param array<string,mixed> $data
     * @return void
     */
    protected function fromArray(Entity $entity, array $data = []): void
    {
        foreach ($this->table->getFullColumns() as $k => $v) {
            if ($v->getBasicType() === 'text' && isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = trim($data[$k]);
            }
        }
        $definition = $this->definition();
        foreach (array_keys($data) as $k) {
            if (!$definition->getColumn($k) && !$definition->hasRelation($k)) {
                unset($data[$k]);
            }
        }
        // TODO: add strictClean method without calling it
        // go over passed objects and select them from the corresponding service
        // the same goes for removing - maybe use the relation logic from the controller
        $this->mapper->fromArray($entity, $data);
    }
    /**
     * @param string $q
     * @param TableQueryMapped<T> $repository
     * @return void
     */
    protected function search(string $q, TableQueryMapped $repository): void
    {
        try {
            $sql = [];
            $par = [];
            $table = $this->table->getName();
            $fulltext = false;
            if ($this->db->driverName() === 'postgre' && $this->table->getColumn('tsindex', true) !== null) {
                $sql[] = $table . '.tsindex @@ websearch_to_tsquery(?, ?)';
                $par[] = preg_match('([а-я]+)ui', $q) ? 'bulgarian' : 'english';
                $par[] = $q;
                $fulltext = true;
            }
            foreach ($this->table->getFullColumns() as $name => $column) {
                if ($column->getBasicType() === 'text' && !$fulltext) {
                    $sql[] = $table . '.' . $name . ' = ?';
                    $par[] = $q;
                    switch ($this->db->driverName()) {
                        case 'postgre':
                            $sql[] = $table . '.' . $name . ' ILIKE ?';
                            $par[] = '%' . str_replace(['%', '_'], ['\\%','\\_'], $q) . '%';
                            break;
                        case 'oracle':
                            $sql[] = 'UPPER(' . $table . '.' . $name . ') LIKE ?';
                            $par[] = '%' . str_replace(['%', '_'], ['\\%','\\_'], mb_strtoupper($q)) . '%';
                            break;
                        default:
                            $sql[] = $table . '.' . $name . ' LIKE ?';
                            $par[] = '%' . str_replace(['%', '_'], ['\\%','\\_'], $q) . '%';
                            break;
                    }
                }
                if ($column->getBasicType() === 'int' && is_numeric($q)) {
                    if ($this->db->driverName() === 'postgre') {
                        $sql[] = $table . '.' . $name . ' = CAST(? AS NUMERIC)';
                        $par[] = (string)$q;
                    } else {
                        $sql[] = $table . '.' . $name . ' = ?';
                        $par[] = (int)$q;
                    }
                }
            }
            if (count($sql)) {
                $repository->where("(" . implode(" OR ", $sql) . ")", $par);
            }
        } catch (DBException) {
        }
    }
    /**
     * @param T $entity
     * @return T
     */
    protected function validate(Entity $entity): Entity
    {
        return $entity;
    }
    /**
     * @param T $entity
     * @param bool $relations should relations be saved as well
     * @return void
     */
    protected function save(Entity $entity, bool $relations = false): void
    {
        $this->mapper->save($this->validate($entity), $relations);
    }

    /**
     * @param T $entity
     * @return array<string,scalar|null>
     */
    public function id(Entity $entity): array
    {
        return $this->mapper->id($entity);
    }
    /**
     * @param T $entity
     * @return string
     */
    public function name(Entity $entity): string
    {
        if ($this->nameColumn) {
            return (string)$entity->{$this->nameColumn};
        }
        return implode(' ', $this->id($entity));
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $data = $relations ?
            $this->mapper->toArray($entity, null, null, false) :
            $this->mapper->toArray($entity);
        $definition = $this->definition();
        foreach (array_keys($data) as $k) {
            if (!$definition->getColumn((string)$k) && !$definition->hasRelation((string)$k)) {
                unset($data[$k]);
            }
        }
        return $data;
    }
    /**
     * @param array<string,mixed> $data
     * @return T
     */
    public function create(array $data = []): Entity
    {
        if (!$this->module->canCreate() || !$this->creatable($data)) {
            throw new CRUDException('Invalid operation', 400);
        }
        $entity = $this->entity($data);
        $this->fromArray($entity, $data);
        $this->save($entity, true);
        return $entity;
    }
    /**
     * @param mixed $id
     * @return T
     */
    public function read(mixed $id): Entity
    {
        $entity = $this->readQuery()->find($id);
        if (!$entity || !$this->readable($entity)) {
            throw new CRUDNotFoundException('Record not found', 404);
        }
        return $entity;
    }
    /**
     * @param mixed $id
     * @param array<string,mixed> $data
     * @return T
     */
    public function update(mixed $id, array $data = []): Entity
    {
        if (!$this->module->canUpdate()) {
            throw new CRUDException('Invalid operation', 400);
        }
        $entity = $this->read($id);
        if (!$this->updatable($entity, $data)) {
            throw new CRUDException('Invalid operation', 400);
        }
        $this->fromArray($entity, $data);
        $this->save($entity, true);
        return $entity;
    }
    public function delete(mixed $id): void
    {
        if (!$this->module->canDelete()) {
            throw new CRUDException('Invalid operation', 400);
        }
        $entity = $this->read($id);
        if (!$this->deletable($entity)) {
            throw new CRUDException('Invalid operation', 400);
        }
        $this->mapper->delete($entity, true);
    }
    /**
     * @param array<string,mixed> $options
     * @return TableQueryMapped<T>
     */
    public function list(array $options): TableQueryMapped
    {
        $repository = $this->listQuery();
        if (!isset($options['p']) || (int)$options['p'] < 1) {
            $options['p'] = 1;
        }
        if (!isset($options['l'])) {
            $options['l'] = 25;
        }
        if ($options['l'] !== 'all') {
            $options['l'] = (int)$options['l'];
            if (!$options['l']) {
                $options['l'] = 25;
            }
        }
        if ($options['l'] !== 'all') {
            $repository->limit($options['l'], ((int)$options['p'] - 1) * $options['l']);
        }
        foreach ($options as $k => $v) {
            switch ($k) {
                case 'd':
                case 'p':
                case 'l':
                    break;
                case 'o':
                    try {
                        $repository->sort($v, isset($options['d']) && (int)$options['d'] ? true : false);
                    } catch (DBException) {
                    }
                    break;
                case 'q':
                    if (strlen($v)) {
                        $this->search($v, $repository);
                    }
                    break;
                default:
                    try {
                        $repository->filter($k, $v);
                    } catch (DBException) {
                    }
                    break;
            }
        }
        return $repository;
    }
}
