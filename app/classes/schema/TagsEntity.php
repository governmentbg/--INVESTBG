<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $tag
 * @property int $lang
 * @property string $name
 * @property \vakata\collection\Collection<int,GalleriesEntity> $galleries via gallery_tags
 * @property \vakata\collection\Collection<int,NewsEntity> $news via news_tags
 * @property LanguagesEntity $languages
 */
class TagsEntity extends Entity
{
}
