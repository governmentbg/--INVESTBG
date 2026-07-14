<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $type_expense
 * @property ?string $name
 * @property ?int $pos
 * @property \vakata\collection\Collection<int,WorkplaceEmplsEntity> $workplace_empls
 */
class NomTypeExpenseEntity extends Entity
{
}
