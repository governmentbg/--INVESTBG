<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $grp
 * @property string $perm
 * @property string $created
 * @property GrpsEntity $grps
 * @property PermissionsEntity $permissions
 */
class GroupPermissionsEntity extends Entity
{
}
