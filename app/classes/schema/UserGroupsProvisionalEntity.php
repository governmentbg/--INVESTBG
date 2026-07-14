<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $usr
 * @property int $grp
 * @property string $created
 * @property GrpsEntity $grps
 * @property UsersEntity $users
 */
class UserGroupsProvisionalEntity extends Entity
{
}
