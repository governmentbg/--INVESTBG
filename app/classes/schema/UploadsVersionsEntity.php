<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $id
 * @property int $upload
 * @property string $version
 * @property string $name
 * @property string $location
 * @property int $bytesize
 * @property string $uploaded
 * @property string $hash
 * @property ?string $data
 * @property ?string $settings
 * @property UploadsEntity $uploads
 */
class UploadsVersionsEntity extends Entity
{
}
