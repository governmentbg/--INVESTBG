<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $migration
 * @property string $package
 * @property string $installed
 * @property ?string $removed
 */
class MigrationsEntity extends Entity
{
}
