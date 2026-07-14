<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $menu
 * @property string $name
 * @property string $slug
 * @property int $site
 * @property int $lang
 * @property int $is_default
 * @property ?string $items
 * @property \vakata\collection\Collection<int,TreeDataEntity> $tree_data
 * @property \vakata\collection\Collection<int,TreeDataPubEntity> $tree_data_pub
 */
class MenusEntity extends Entity
{
}
