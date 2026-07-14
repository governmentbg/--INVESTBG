<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $template
 * @property string $name
 * @property string $base
 * @property int $is_default
 * @property ?int $child_default
 * @property ?string $widgets
 * @property ?string $zones
 * @property ?TemplatesEntity $child_default_templates
 * @property \vakata\collection\Collection<int,TemplatesEntity> $templates_child_default
 * @property \vakata\collection\Collection<int,TreeDataEntity> $tree_data
 * @property \vakata\collection\Collection<int,TreeDataPubEntity> $tree_data_pub
 */
class TemplatesEntity extends Entity
{
}
