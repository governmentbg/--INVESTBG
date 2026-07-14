<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;
use vakata\files\File;

/**
 * @property int $ri
 * @property int $report
 * @property int $usr
 * @property ?string $created_at
 * @property int $file_id
 * @property ?int $type
 * @property UploadsEntity $uploads
 * @property ReportsEntity $reports
 * @property UsersEntity $users
 */
class ReportsImportsEntity extends Entity
{
    public function file(): File
    {
        return $this->uploads->file();
    }
}
