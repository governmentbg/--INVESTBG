<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $id
 * @property int $lang
 * @property int $version
 * @property ?int $from_version
 * @property string $created
 * @property int $usr
 * @property string $title
 * @property int $hidden
 * @property string $url
 * @property string $redirect
 * @property ?string $settings
 * @property ?string $content
 * @property ?string $permissions
 * @property int $template
 * @property ?int $menu
 * @property int $published
 * @property LanguagesEntity $languages
 * @property ?MenusEntity $menus
 * @property TreeStructEntity $tree_struct
 * @property TemplatesEntity $templates
 * @property UsersEntity $users
 */
class TreeDataPubEntity extends Entity
{
}
