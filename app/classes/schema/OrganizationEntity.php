<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $org
 * @property int $lft
 * @property int $rgt
 * @property int $lvl
 * @property ?int $pid
 * @property int $pos
 * @property string $title
 * @property ?string $properties
 * @property \vakata\collection\Collection<int,UsersEntity> $users via user_organizations
 * @property ?OrganizationEntity $pid_organization
 * @property \vakata\collection\Collection<int,OrganizationEntity> $organization_pid
 */
class OrganizationEntity extends Entity
{
}
