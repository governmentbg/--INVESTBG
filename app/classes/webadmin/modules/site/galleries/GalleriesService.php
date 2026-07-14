<?php

declare(strict_types=1);

namespace webadmin\modules\site\galleries;

use vakata\collection\Collection;
use vakata\database\DBInterface;
use webadmin\modules\common\crud\CRUDServiceVersionedInterface;
use webadmin\modules\common\crud\CRUDServiceVersionTrait;
use webadmin\modules\common\crud\CRUDService;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\user\User;
use webadmin\modules\common\crud\CRUDException;

/**
 * @extends CRUDService<\schema\GalleriesEntity>
 * @implements CRUDServiceVersionedInterface<\schema\GalleriesEntity>
 */
class GalleriesService extends CRUDService implements CRUDServiceVersionedInterface
{
    /** @use CRUDServiceVersionTrait<\schema\GalleriesEntity> */
    use CRUDServiceVersionTrait;

    public function __construct(GalleriesModule $module, DBInterface $db, User $user)
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
    public function tags(): array
    {
        return $this->db->rows("SELECT tag, name FROM tags")->toArray('tag', 'name');
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $arr = parent::toArray($entity, $relations);
        $arr['images'] = $entity->images();
        return $arr;
    }
    protected function fromArray(Entity $entity, array $data = []): void
    {
        if (isset($data['images'])) {
            if (!is_array($data['images'])) {
                $data['images'] = [];
            }
            $pos = 0;
            $data['gallery_images'] = [];
            foreach ($data['images'] as $image) {
                $data['gallery_images'][] = [ 'id' => $image, 'pos' => ++$pos ];
            }
        }
        parent::fromArray($entity, $data);
    }
    public function create(array $data = []): Entity
    {
        $data['site'] = $this->user->site;
        $entity = parent::create($data);
        $images = array_filter(explode(',', $data['images'] ?? ''));
        $pos = 0;
        foreach ($images as $image) {
            $this->db->table('gallery_images')
                ->insert([ 'gallery' => $entity->gallery, 'upload' => $image, 'pos' => ++$pos ]);
        }
        $this->version($entity, 0, true);
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        unset($data['site']);
        $entity = parent::update($id, $data);
        $images = array_filter(explode(',', $data['images'] ?? ''));
        $this->db->table('gallery_images')->filter("gallery", $entity->gallery)->delete();
        $pos = 0;
        foreach ($images as $image) {
            $this->db->table('gallery_images')
                ->insert([ 'gallery' => $entity->gallery, 'upload' => $image, 'pos' => ++$pos ]);
        }
        $this->version($entity, 1, true);
        return $entity;
    }
    public function getLanguages(): array
    {
        return $this->user->languages;
    }
}
