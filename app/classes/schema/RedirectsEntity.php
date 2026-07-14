<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $redirect
 * @property string $url_from
 * @property string $url_to
 * @property string $rtype
 * @property ?int $site
 * @property ?SitesEntity $sites
 */
class RedirectsEntity extends Entity
{
}
