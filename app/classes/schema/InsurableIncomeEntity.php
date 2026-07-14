<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $ii
 * @property string $from_date
 * @property string $to_date
 * @property ?int $category
 * @property int $max_income
 * @property int $percent_insurance
 */
class InsurableIncomeEntity extends Entity
{
}
