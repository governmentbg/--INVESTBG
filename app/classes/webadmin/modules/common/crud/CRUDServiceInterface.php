<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use vakata\database\schema\Entity;
use vakata\database\schema\Table;
use vakata\database\schema\TableQueryMapped;

/**
 * @template T of Entity
 */
interface CRUDServiceInterface
{
    /**
     * @param array<string,mixed> $data
     * @return T
     */
    public function create(array $data = []): Entity;
    /**
     * @param mixed $id
     * @return T
     */
    public function read(mixed $id): Entity;
    /**
     * @param mixed $id
     * @param array<string,mixed> $data
     * @return T
     */
    public function update(mixed $id, array $data = []): Entity;
    public function delete(mixed $id): void;
    /**
     * @param array<string,mixed> $options
     * @return TableQueryMapped<T>
     */
    public function list(array $options): TableQueryMapped;
    /**
     * @param T $entity
     * @return array<string,scalar|null>
     */
    public function id(Entity $entity): array;
    /**
     * @param T $entity
     * @return string
     */
    public function name(Entity $entity): string;
    /**
     * @param T $entity
     * @return array
     */
    public function toArray(Entity $entity): array;
    public function definition(): CRUDEntityDefinition;
    /**
     * @return TableQueryMapped<T>
     */
    public function listQuery(): TableQueryMapped;
    /**
     * @return TableQueryMapped<T>
     */
    public function readQuery(): TableQueryMapped;
    public function creatable(?array $data = null): bool;
    public function updatable(Entity $entity, ?array $data = null): bool;
    public function deletable(Entity $entity): bool;
    public function readable(Entity $entity): bool;
}
