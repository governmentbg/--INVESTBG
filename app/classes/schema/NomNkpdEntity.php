<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $nkpd
 * @property ?string $code
 * @property ?string $name
 * @property \vakata\collection\Collection<int,WorkplacesEntity> $workplaces
 */
class NomNkpdEntity extends Entity
{
}
