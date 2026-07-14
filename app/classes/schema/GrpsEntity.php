<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $grp
 * @property string $name
 * @property string $created
 * @property \vakata\collection\Collection<int,CollectionGroupsEntity> $collection_groups
 * @property \vakata\collection\Collection<int,GroupPermissionsEntity> $group_permissions
 * @property \vakata\collection\Collection<int,UserGroupsEntity> $user_groups
 * @property \vakata\collection\Collection<int,UserGroupsProvisionalEntity> $user_groups_provisional
 */
class GrpsEntity extends Entity
{
}
