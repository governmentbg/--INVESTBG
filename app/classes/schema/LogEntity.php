<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $id
 * @property string $created
 * @property string $lvl
 * @property string $message
 * @property ?string $context
 * @property ?string $request
 * @property ?string $response
 * @property string $ip
 * @property ?int $usr
 * @property ?string $usr_name
 * @property ?UsersEntity $users
 */
class LogEntity extends Entity
{
}
