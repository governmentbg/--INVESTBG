<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $employee
 * @property ?int $identifirer_type
 * @property ?string $identifirer
 * @property ?string $name
 * @property \vakata\collection\Collection<int,WorkplaceEmplsEntity> $workplace_empls
 */
class EmployeesEntity extends Entity
{
}
