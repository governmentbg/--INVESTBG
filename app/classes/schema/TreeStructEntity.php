<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $id
 * @property int $lft
 * @property int $rgt
 * @property int $lvl
 * @property ?int $pid
 * @property int $pos
 * @property ?TreeStructEntity $pid_tree_struct
 * @property \vakata\collection\Collection<int,SitesEntity> $sites
 * @property \vakata\collection\Collection<int,TreeDataEntity> $tree_data
 * @property \vakata\collection\Collection<int,TreeStructEntity> $tree_struct_pid
 * @property \vakata\collection\Collection<int,TreeDataPubEntity> $tree_data_pub
 */
class TreeStructEntity extends Entity
{
}
