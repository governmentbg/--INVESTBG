<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $sector
 * @property string $name
 * @property int $pos
 * @property \vakata\collection\Collection<int,ContractsEntity> $contracts
 * @property \vakata\collection\Collection<int,NomSectorActivitiesEntity> $nom_sector_activities
 */
class NomSectorsEntity extends Entity
{
}
