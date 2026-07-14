<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $usr
 * @property string $name
 * @property string $mail
 * @property int $tfa
 * @property int $disabled
 * @property ?int $avatar
 * @property ?string $avatar_data
 * @property ?string $data
 * @property ?string $push
 * @property ?string $sessions
 * @property \vakata\collection\Collection<int,ContractsEntity> $contracts via contract_users
 * @property \vakata\collection\Collection<int,UploadsEntity> $uploads via upload_user
 * @property \vakata\collection\Collection<int,OrganizationEntity> $organization via user_organizations
 * @property ?UploadsEntity $avatar_uploads
 * @property \vakata\collection\Collection<int,CollectionsEntity> $collections
 * @property \vakata\collection\Collection<int,LogEntity> $log
 * @property \vakata\collection\Collection<int,LogSystemEntity> $log_system
 * @property \vakata\collection\Collection<int,UserGroupsEntity> $user_groups
 * @property \vakata\collection\Collection<int,UserGroupsProvisionalEntity> $user_groups_provisional
 * @property \vakata\collection\Collection<int,UserProvidersEntity> $user_providers
 * @property \vakata\collection\Collection<int,ReportsImportsEntity> $reports_imports
 */
class UsersEntity extends Entity
{
}
