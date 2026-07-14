<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $sector_activity
 * @property int $sector
 * @property string $name
 * @property int $pos
 * @property NomSectorsEntity $nom_sectors
 * @property \vakata\collection\Collection<int,ContractsEntity> $contracts
 */
class NomSectorActivitiesEntity extends Entity
{
}
