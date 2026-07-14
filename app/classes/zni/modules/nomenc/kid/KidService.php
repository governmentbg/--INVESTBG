<?php

declare(strict_types=1);

namespace zni\modules\nomenc\kid;

use vakata\intl\Intl;
use vakata\user\User;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use webadmin\modules\ModulesContainer;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDService<\schema\NomKidEntity>
 */
class KidService extends CRUDService
{
    public function __construct(
        KidModule $module,
        DBInterface $db,
        User $user,
        protected ModulesContainer $mc,
        protected Intl $intl
    ) {
        parent::__construct($module, $db, $user);
    }

    public function create(array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }

    public function update(mixed $id, array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }

    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }

    public function name(Entity $entity): string
    {
        if ($this->nameColumn) {
            return trim((string) $entity->code . '. ' . (string) $entity->name);
        }
        return implode(' ', $this->id($entity));
    }
}
