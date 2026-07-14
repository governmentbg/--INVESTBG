<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $site
 * @property string $name
 * @property string $domains
 * @property ?int $tree
 * @property int $disabled
 * @property int $dflt
 * @property \vakata\collection\Collection<int,LanguagesEntity> $languages via site_lang
 * @property \vakata\collection\Collection<int,UsersEntity> $users via user_site
 * @property ?TreeStructEntity $tree_struct
 * @property \vakata\collection\Collection<int,GalleriesEntity> $galleries
 * @property \vakata\collection\Collection<int,NewsEntity> $news
 * @property \vakata\collection\Collection<int,RedirectsEntity> $redirects
 * @property \vakata\collection\Collection<int,SearchIndexEntity> $search_index
 * @property \vakata\collection\Collection<int,SiteDomainEntity> $site_domain
 */
class SitesEntity extends Entity
{
}
