<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property string $perm
 * @property string $created
 * @property \vakata\collection\Collection<int,GroupPermissionsEntity> $group_permissions
 */
class PermissionsEntity extends Entity
{
}
