<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $region
 * @property string $code
 * @property string $name
 * @property string $name_en
 * @property int $pos
 * @property \vakata\collection\Collection<int,CompaniesEntity> $companies
 * @property \vakata\collection\Collection<int,MunicipalitiesEntity> $municipalities
 */
class RegionsEntity extends Entity
{
}
