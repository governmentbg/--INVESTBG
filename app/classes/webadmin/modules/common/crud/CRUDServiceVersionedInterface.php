<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use vakata\database\schema\Entity;

/**
 * @template T of Entity
 */
interface CRUDServiceVersionedInterface
{
    /**
     * @param T $entity
     * @param integer $reason
     * @param boolean $modifyLast
     * @return void
     */
    public function version(Entity $entity, int $reason = 0, bool $modifyLast = false): void;
    /**
     * @param T $entity
     * @param integer|null $version
     * @return array<array<string,mixed>>
     */
    public function versions(Entity $entity, ?int $version = null): array;
}
