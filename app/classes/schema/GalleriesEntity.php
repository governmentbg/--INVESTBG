<?php

declare(strict_types=1);

namespace schema;

use vakata\collection\Collection;
use vakata\database\schema\Entity;

/**
 * @property int $gallery
 * @property int $lang
 * @property string $fordate
 * @property string $title
 * @property string $content
 * @property int $hidden
 * @property string $visible_beg
 * @property ?string $visible_end
 * @property ?int $site
 * @property \vakata\collection\Collection<int,TagsEntity> $tags via gallery_tags
 * @property LanguagesEntity $languages
 * @property ?SitesEntity $sites
 * @property \vakata\collection\Collection<int,GalleryImagesEntity> $gallery_images
 */
class GalleriesEntity extends Entity
{
    /**
     * @psalm-suppress all
     * @return array<\vakata\files\File>
     */
    public function images(): array
    {
        return Collection::from($this->relatedQuery('gallery_images')->sort('pos'))
            ->map(function (GalleryImagesEntity $e) {
                return $e->file();
            })
            ->toArray();
    }
}
