<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $municipality
 * @property string $code
 * @property string $name
 * @property string $name_en
 * @property int $region
 * @property int $pos
 * @property RegionsEntity $regions
 * @property \vakata\collection\Collection<int,CitiesEntity> $cities
 * @property \vakata\collection\Collection<int,CompaniesEntity> $companies
 */
class MunicipalitiesEntity extends Entity
{
}
