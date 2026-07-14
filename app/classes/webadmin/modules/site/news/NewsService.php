<?php

declare(strict_types=1);

namespace webadmin\modules\site\news;

use vakata\database\DBInterface;
use webadmin\modules\common\crud\CRUDServiceVersionedInterface;
use webadmin\modules\common\crud\CRUDServiceVersionTrait;
use webadmin\modules\common\crud\CRUDService;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\user\User;
use webadmin\modules\common\crud\CRUDException;

/**
 * @extends CRUDService<\schema\NewsEntity>
 * @implements CRUDServiceVersionedInterface<\schema\NewsEntity>
 */
class NewsService extends CRUDService implements CRUDServiceVersionedInterface
{
    /** @use CRUDServiceVersionTrait<\schema\NewsEntity> */
    use CRUDServiceVersionTrait;

    public function __construct(NewsModule $module, DBInterface $db, User $user)
    {
        if (!$user->site) {
            throw new CRUDException('No site configured for user');
        }
        if (!count($user->languages ?? [])) {
            throw new CRUDException('No languages configured for user');
        }
        parent::__construct($module, $db, $user);
    }
    protected function entities(): TableQueryMapped
    {
        return parent::entities()
            ->filter('site', $this->user->site)
            ->filter('lang', array_keys($this->user->languages))
            ->with('tags');
    }
    public function listQuery(): TableQueryMapped
    {
        return parent::listQuery()
            ->limitOnMainTable(true)
            ->columns([ 'lang', 'fordate', 'title', 'hidden', 'tags.tag', 'tags.name' ]);
    }
    public function create(array $data = []): Entity
    {
        $data['site'] = $this->user->site;
        if (!isset($data['image']) || !(int)$data['image']) {
            $data['image'] = null;
        }
        $entity = parent::create($data);
        $this->version($entity, 0, true);
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        unset($data['site']);
        if (!isset($data['image']) || !(int)$data['image']) {
            $data['image'] = null;
        }
        $entity = parent::update($id, $data);
        $this->version($entity, 1, true);
        return $entity;
    }
    public function getLanguages(): array
    {
        return $this->user->languages;
    }
    public function tags(): array
    {
        return $this->db->rows("SELECT tag, name FROM tags")->toArray('tag', 'name');
    }
}
