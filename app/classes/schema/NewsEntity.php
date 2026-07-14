<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $news
 * @property int $lang
 * @property string $fordate
 * @property string $title
 * @property ?int $image
 * @property string $content
 * @property int $hidden
 * @property string $visible_beg
 * @property ?string $visible_end
 * @property ?int $site
 * @property \vakata\collection\Collection<int,TagsEntity> $tags via news_tags
 * @property ?UploadsEntity $uploads
 * @property LanguagesEntity $languages
 * @property ?SitesEntity $sites
 */
class NewsEntity extends Entity
{
}
