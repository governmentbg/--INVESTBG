<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $company
 * @property string $egn
 * @property int $moderator
 * @property ?string $name
 * @property CompaniesEntity $companies
 */
class CompanyEgnsEntity extends Entity
{
}
