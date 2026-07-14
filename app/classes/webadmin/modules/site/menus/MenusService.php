<?php

declare(strict_types=1);

namespace webadmin\modules\site\menus;

use webadmin\modules\common\crud\CRUDService;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\intl\Intl;
use vakata\user\User;
use webadmin\Jobs;
use webadmin\modules\common\crud\CRUDException;

/**
 * @extends CRUDService<\schema\MenusEntity>
 */
class MenusService extends CRUDService
{
    private Intl $intl;
    private Jobs $jobs;

    public function __construct(
        MenusModule $module,
        Intl $intl,
        DBInterface $db,
        User $user,
        Jobs $jobs
    ) {
        if (!$user->site) {
            throw new CRUDException('No site configured for user');
        }
        if (!count($user->languages ?? [])) {
            throw new CRUDException('No languages configured for user');
        }
        parent::__construct($module, $db, $user);
        $this->intl = $intl;
        $this->jobs = $jobs;
    }
    protected function entities(): TableQueryMapped
    {
        return parent::entities()
            ->filter('site', $this->user->site)
            ->filter('lang', array_keys($this->user->languages));
    }
    public function listQuery(): TableQueryMapped
    {
        /** @var TableQueryMapped<\schema\MenusEntity> */
        return parent::listQuery()->columns([ 'lang', 'name', 'slug', 'is_default' ]);
    }
    public function items(int $lang = 1): array
    {
        $nodes = $this->db->rows(
            $this->db->driverName() === 'oracle' ?
                "SELECT
                    id,
                    lvl,
                    (
                        SELECT title FROM tree_data
                        WHERE id = tree_struct.id AND lang = ?
                        ORDER BY published DESC, version DESC FETCH FIRST 1 ROW ONLY
                    ) AS name
                FROM tree_struct
                ORDER BY lft ASC" :
                "SELECT
                    id,
                    lvl,
                    (
                        SELECT title FROM tree_data
                        WHERE id = tree_struct.id AND lang = ?
                        ORDER BY published DESC, version DESC LIMIT 1
                    ) AS name
                FROM tree_struct
                ORDER BY lft ASC",
            [ $lang ]
        )->toArray();
        $items = [];
        $items['text'] = $this->intl->get('menus.text');
        foreach ($nodes as $node) {
            $items[(int)$node['id']] = str_repeat(' ', (int)$node['lvl'] * 4) .
                $node['name'] . ' (' . $node['id'] . ')';
        }
        return $items;
    }
    public function getLanguages(): array
    {
        return $this->user->languages;
    }
    public function create(array $data = []): Entity
    {
        $data['site'] = $this->user->site;
        $entity = parent::create($data);
        $this->jobs->cachePublic();
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        unset($data['site']);
        $entity = parent::update($id, $data);
        $this->jobs->cachePublic();
        return $entity;
    }
}
