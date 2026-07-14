<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $sample_document
 * @property string $name
 * @property int $file
 * @property UploadsEntity $uploads
 */
class SampleDocumentsEntity extends Entity
{
}
