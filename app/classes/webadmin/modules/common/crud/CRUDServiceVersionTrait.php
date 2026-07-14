<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use vakata\database\schema\Entity;

/**
 * @template T of Entity
 */
trait CRUDServiceVersionTrait
{
    protected function versionsTable(): string
    {
        return 'versions';
    }
    /**
     * @param T $entity
     * @return array<string,mixed>
     */
    protected function versionsArray(Entity $entity): array
    {
        try {
            $entity = $this->read($this->id($entity));
        } catch (\Exception) {
            // it is possible to save a partial object
            // the partial object does not yet match repository filters, so the user can't read it YET
            // for example - filter on related tables
        }
        return $this->toArray($entity);
    }
    /**
     * @param T $entity
     * @param integer $reason
     * @param boolean $modifyLast
     * @return void
     */
    public function version(Entity $entity, int $reason = 0, bool $modifyLast = false): void
    {
        $reasons = [ 'created', 'updated', 'deleted' ];
        $tbl = $this->table->getName();
        $id = json_encode($this->id($entity));
        $data = $this->versionsArray($entity);
        $json = json_encode($data);
        $table = $this->versionsTable();
        if ($modifyLast) {
            $created = $this->db->val(
                "SELECT MAX(created) FROM {$table} WHERE tbl = ? AND id = ?",
                [ $tbl, $id ]
            );
            $this->db->table($table)
                ->filter('tbl', $tbl)
                ->filter('id', $id)
                ->filter('created', $created)
                ->update([
                    'entity'   => $json
                ]);
        } else {
            $this->db->table($table)
                ->insert([
                    'tbl'      => $tbl,
                    'id'       => $id,
                    'created'  => date('Y-m-d H:i:s'),
                    'entity'   => $json,
                    'reason'   => $reasons[$reason],
                    'usr'      => $this->user->getID(),
                    'usr_name' => $this->user->name
                ]);
        }
    }
    /**
     * @param T $entity
     * @param integer|null $version
     * @return array<array<string,mixed>>
     */
    public function versions(Entity $entity, ?int $version = null): array
    {
        $table = $this->versionsTable();
        // not using TableQuery if not version, as we need only a 2 columns, but the table has no PK
        return isset($version) ?
            $this->db->table($table)
                ->filter('tbl', $this->table->getName())
                ->filter('id', json_encode($this->id($entity)))
                ->sort('created')
                ->limit($version ? 2 : 1, max(0, $version - 1))
                ->select() :
            $this->db->rows(
                "SELECT usr_name, created FROM {$table} WHERE tbl = ? AND id = ? ORDER BY created DESC",
                [ $this->table->getName(), json_encode($this->id($entity)) ]
            )->toArray();
    }
}
