<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $id
 * @property string $created
 * @property string $lvl
 * @property string $message
 * @property string $context
 * @property ?string $module
 * @property ?string $module_id
 * @property ?int $usr
 * @property ?UsersEntity $users
 */
class LogSystemEntity extends Entity
{
}
