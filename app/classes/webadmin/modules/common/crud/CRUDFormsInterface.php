<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use vakata\database\schema\Entity;
use webadmin\components\html\Form;
use webadmin\components\html\Table;

/**
 * @template T of Entity
 */
interface CRUDFormsInterface
{
    public function base(): Form;
    /**
     * @param array<string,mixed> $data
     * @return Form
     */
    public function create(array $data = []): Form;
    /**
     * @param T $entity
     * @return Form
     */
    public function read(Entity $entity): Form;
    /**
     * @param T $entity
     * @param array<string,mixed> $data
     * @return Form
     */
    public function update(Entity $entity, array $data = []): Form;
    /**
     * @param T $entity
     * @return Form
     */
    public function delete(Entity $entity): Form;
    public function history(array $data = []): Form;
    /**
     * @param T $entity
     * @param array $data
     * @return Form
     */
    public function copy(Entity $entity, array $data = []): Form;
    /**
     * @param iterable<T> $entities
     * @return Table
     */
    public function listing(iterable $entities, array $params = [], ?int $count = null): Table;
}
