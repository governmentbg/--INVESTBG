<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property string $tbl
 * @property string $id
 * @property string $created
 * @property ?string $entity
 * @property string $reason
 * @property int $usr
 * @property string $usr_name
 */
class VersionsEntity extends Entity
{
}
