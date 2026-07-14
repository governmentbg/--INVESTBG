<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $collection
 * @property int $grp
 * @property int $rw
 * @property CollectionsEntity $collections
 * @property GrpsEntity $grps
 */
class CollectionGroupsEntity extends Entity
{
}
