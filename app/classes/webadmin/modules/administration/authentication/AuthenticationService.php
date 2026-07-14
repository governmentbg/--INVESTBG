<?php

declare(strict_types=1);

namespace webadmin\modules\administration\authentication;

use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDService;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;

/**
 * @extends CRUDService<\schema\AuthenticationEntity>
 */
class AuthenticationService extends CRUDService
{
    public function create(array $data = []): Entity
    {
        throw new CRUDException('Not allowed');
    }
    public function delete(mixed $id): void
    {
        throw new CRUDException('Not allowed');
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $arr = parent::toArray($entity, $relations);
        $arr['settings'] = $arr['settings'] && json_decode($arr['settings'], true) ?
            (json_encode(
                json_decode($arr['settings'], true),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ) ?: '') :
            '';
        $arr['conditions'] = $arr['conditions'] && json_decode($arr['conditions'], true) ?
            (json_encode(
                json_decode($arr['conditions'], true),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ) ?: '') :
            '';
        return $arr;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        if (trim($data['settings'])) {
            if (!json_decode($data['settings'], true)) {
                throw new CRUDException('Invalid settings JSON');
            }
        } else {
            $data['settings'] = '{}';
        }
        if (trim($data['conditions'])) {
            if (!json_decode($data['conditions'], true)) {
                throw new CRUDException('Invalid conditions JSON');
            }
        } else {
            $data['conditions'] = null;
        }
        return parent::update($id, $data);
    }
    public function list(array $options): TableQueryMapped
    {
        $options['o'] = 'position';
        $options['d'] = '0';
        $options['p'] = '1';
        $options['l'] = 'all';
        return parent::list($options);
    }
}
