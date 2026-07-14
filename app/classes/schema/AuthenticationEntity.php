<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $authentication
 * @property string $authenticator
 * @property ?string $settings
 * @property int $position
 * @property int $disabled
 * @property ?string $conditions
 */
class AuthenticationEntity extends Entity
{
}
