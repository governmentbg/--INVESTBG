<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $usrprov
 * @property string $provider
 * @property string $id
 * @property int $usr
 * @property string $name
 * @property ?string $data
 * @property string $created
 * @property ?string $used
 * @property int $disabled
 * @property ?string $details
 * @property UsersEntity $users
 */
class UserProvidersEntity extends Entity
{
}
