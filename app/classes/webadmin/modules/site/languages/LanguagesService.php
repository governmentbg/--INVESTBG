<?php

declare(strict_types=1);

namespace webadmin\modules\site\languages;

use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDService<\schema\LanguagesEntity>
 */
class LanguagesService extends CRUDService
{
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
}
