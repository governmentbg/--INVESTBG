<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;
use vakata\files\File;

/**
 * @property int $gallery
 * @property int $upload
 * @property int $pos
 * @property GalleriesEntity $galleries
 * @property UploadsEntity $uploads
 */
class GalleryImagesEntity extends Entity
{
    public function file(): File
    {
        return $this->uploads->file();
    }
}
