<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $employee_salary
 * @property ?int $workplace_empl
 * @property ?int $salary
 * @property ?int $insurance
 * @property ?int $percent
 * @property ?int $month
 * @property ?int $year
 * @property ?WorkplaceEmplsEntity $workplace_empls
 */
class EmployeeSalaryEntity extends Entity
{
}
