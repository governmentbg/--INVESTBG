<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property string $name
 * @property ?string $slug
 * @property ?int $loaded
 * @property ?string $classname
 * @property ?int $pos
 * @property ?string $settings
 */
class ModulesEntity extends Entity
{
}
