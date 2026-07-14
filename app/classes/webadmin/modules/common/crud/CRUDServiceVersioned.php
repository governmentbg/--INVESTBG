<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use vakata\database\schema\Entity;

/**
 * @template T of Entity
 * @extends CRUDService<T>
 * @implements CRUDServiceVersionedInterface<T>
 */
class CRUDServiceVersioned extends CRUDService implements CRUDServiceVersionedInterface
{
    /** @use CRUDServiceVersionTrait<T> */
    use CRUDServiceVersionTrait;

    /**
     * @param array $data
     * @return T
     */
    public function create(array $data = []): Entity
    {
        $entity = parent::create($data);
        $this->version($entity, 0);
        return $entity;
    }
    /**
     * @param mixed $id
     * @param array $data
     * @return T
     */
    public function update(mixed $id, array $data = []): Entity
    {
        $entity = parent::update($id, $data);
        $this->version($entity, 1);
        return $entity;
    }
    public function delete(mixed $id): void
    {
        $entity = $this->read($id);
        parent::delete($id);
        $this->version($entity, 2);
    }
}
