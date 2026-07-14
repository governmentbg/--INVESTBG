<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\base;

use vakata\database\DBInterface;

abstract class BaseService
{
    public function __construct(protected DBInterface $db)
    {
    }
    /**
     * @param array $params
     * @return array
     */
    abstract public function select(array $params): array;
    abstract public function columns(): array;
}
