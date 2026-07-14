<?php

declare(strict_types=1);

namespace schema\mappers;

use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\database\schema\Mapper;
use vakata\database\schema\MapperInterface;
use vakata\database\schema\Table;
use vakata\di\DIInterface;

/**
 * A basic mapper to enable relation traversing and basic create / update / delete functionality
 *
 * @template T of Entity
 * @extends Mapper<T>
 * @implements MapperInterface<T>
 */
class DIMapper extends Mapper implements MapperInterface
{
    protected DIInterface $di;

    /**
     * @param DBInterface $db
     * @param string|Table|null $table
     * @param class-string<T> $clss
     * @return void
     */
    public function __construct(
        DIInterface $di,
        DBInterface $db,
        string|Table|null $table = '',
        string $clss = Entity::class
    ) {
        $this->di = $di;
        parent::__construct($db, $table, $clss);
    }
    /**
     * @param array<string,mixed> $data
     * @param array<string,callable> $lazy
     * @param array<string,callable> $relations
     * @return T
     */
    protected function instance(array $data = [], array $lazy = [], array $relations = []): object
    {
        return $this->di->instance(
            $this->clss,
            [
                'data' => $data,
                'lazy' => $lazy,
                'relations' => $relations
            ]
        );
    }
}
