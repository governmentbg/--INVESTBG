<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $city
 * @property string $name
 * @property string $name_en
 * @property int $type
 * @property int $municipality
 * @property int $pos
 * @property ?int $parent
 * @property ?CitiesEntity $parent_cities
 * @property MunicipalitiesEntity $municipalities
 * @property \vakata\collection\Collection<int,CitiesEntity> $cities_parent
 */
class CitiesEntity extends Entity
{
}
