<?php

declare(strict_types=1);

namespace webadmin\modules\site\tags;

use vakata\database\schema\TableQueryMapped;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDService<\schema\TagsEntity>
 */
class TagsService extends CRUDService
{
    protected function entities(): TableQueryMapped
    {
        return parent::entities()
            ->filter('lang', array_keys($this->user->languages));
    }
    public function getLanguages(): array
    {
        return $this->user->languages;
    }
}
