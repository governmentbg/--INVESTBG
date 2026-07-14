<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property string $si
 * @property string $url
 * @property string $title
 * @property string $data
 * @property string $meta
 * @property string $indexed
 * @property int $remove
 * @property ?int $site
 * @property ?int $lang
 * @property ?LanguagesEntity $languages
 * @property ?SitesEntity $sites
 */
class SearchIndexEntity extends Entity
{
}
