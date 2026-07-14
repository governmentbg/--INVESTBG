<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $usrpend
 * @property string $provider
 * @property string $id
 * @property string $name
 * @property string $mail
 * @property string $created
 * @property ?string $details
 */
class UserPendingEntity extends Entity
{
}
