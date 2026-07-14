<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $collection
 * @property string $name
 * @property int $owner
 * @property int $rw
 * @property \vakata\collection\Collection<int,UploadsEntity> $uploads via upload_collections
 * @property UsersEntity $users
 * @property \vakata\collection\Collection<int,CollectionGroupsEntity> $collection_groups
 */
class CollectionsEntity extends Entity
{
}
