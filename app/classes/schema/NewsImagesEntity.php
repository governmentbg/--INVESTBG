<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;
use vakata\files\File;

/**
 * @property int $news
 * @property int $img
 * @property ?int $pos
 * @property NewsEntity $news_news
 * @property UploadsEntity $uploads
 */
class NewsImagesEntity extends Entity
{
    public function file(): File
    {
        return $this->uploads->file();
    }
}
