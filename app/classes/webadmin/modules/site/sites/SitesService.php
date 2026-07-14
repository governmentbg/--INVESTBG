<?php

declare(strict_types=1);

namespace webadmin\modules\site\sites;

use schema\LanguagesEntity;
use vakata\database\DBInterface;
use webadmin\modules\common\crud\CRUDService;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\phptree\Node;
use vakata\phptree\Tree;
use vakata\user\User;
use webadmin\Jobs;

/**
 * @extends CRUDService<\schema\SitesEntity>
 */
class SitesService extends CRUDService
{
    private Jobs $jobs;

    public function __construct(
        SitesModule $module,
        DBInterface $db,
        User $user,
        Jobs $jobs
    ) {
        parent::__construct($module, $db, $user);
        $this->jobs = $jobs;
    }
    public function listQuery(): TableQueryMapped
    {
        /** @var TableQueryMapped<\schema\SitesEntity> */
        return parent::listQuery()->columns(['name']);
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    /**
     * @return array<int,string>
     */
    public function getAvailableLangs(): array
    {
        return $this->db->rows("SELECT l.lang, l.local FROM languages l ORDER BY l.local")->toArray('lang', 'local');
    }
    protected function createNode(string $name, array $langs = []): int
    {
        $tree = Tree::fromDatabase(
            $this->db,
            'tree_struct',
            [
                'id'       => 'id',
                'parent'   => 'pid',
                'position' => 'pos',
                'level'    => 'lvl',
                'left'     => 'lft',
                'right'    => 'rgt'
            ]
        );
        $node = new Node();
        $home = new Node();
        $node->addChild($home);
        $tree->getRoot()->addChild($node);
        $tree->toDatabase(
            $this->db,
            'tree_struct',
            [
                'id'       => 'id',
                'parent'   => 'pid',
                'position' => 'pos',
                'level'    => 'lvl',
                'left'     => 'lft',
                'right'    => 'rgt'
            ]
        );
        foreach ($this->db->all("SELECT lang FROM languages") as $lang) {
            if (!in_array($lang, $langs)) {
                continue;
            }
            foreach (['tree_data', 'tree_data_pub'] as $table) {
                $this->db->query(
                    "INSERT INTO {$table} (
                    id,
                    lang,
                    version,
                    from_version,
                    created,
                    usr,
                    title,
                    hidden,
                    url,
                    redirect,
                    settings,
                    content,
                    permissions,
                    template,
                    menu,
                    published
                    ) VALUES (?, ?, 1, NULL, ?, 1, ?, 0, '', '', '{}', '{}', NULL, 1, NULL, 1)",
                    [ $node->id, $lang, date('Y-m-d H:i:s'), $name ]
                );
                $this->db->query(
                    "INSERT INTO {$table} (
                    id,
                    lang,
                    version,
                    from_version,
                    created,
                    usr,
                    title,
                    hidden,
                    url,
                    redirect,
                    settings,
                    content,
                    permissions,
                    template,
                    menu,
                    published
                    ) VALUES (?, ?, 1, NULL, ?, 1, ?, 0, '', '', '{}', '{}', NULL, 1, NULL, 1)",
                    [ $home->id, $lang, date('Y-m-d H:i:s'), 'Начало' ]
                );
            }
        }
        return (int)$home->id;
    }
    public function create(array $data = []): Entity
    {
        $domains = array_filter(explode("\n", str_replace("\r", "", (string)$data['domains'])));
        foreach ($domains as $k => $v) {
            $domains[$k] = trim(preg_replace('(^https?\:)i', '', $v) ?? '', ' /');
        }
        if (!isset($data['langs']) || !is_array($data['langs'])) {
            $data['langs'] = [];
        }
        if (!(int)$data['tree']) {
            $data['tree'] = $this->createNode($data['name'], $data['langs']);
        }
        $domains = array_unique($domains);
        $data['domains'] = implode("\n", $domains);
        $entity = parent::create($data);
        if (count($data['langs'])) {
            $available = $this->getAvailableLangs();
            foreach ($data['langs'] as $lang) {
                if (isset($available[$lang])) {
                    $this->db->table('site_lang')->insert([
                        'site' => $entity->site,
                        'lang' => $lang
                    ]);
                }
            }
        }
        $this->db->table('site_domain')->filter("site", $entity->site)->delete();
        foreach ($domains as $domain) {
            $this->db->table('site_domain')->insert([ 'site' => $entity->site, 'domain' => $domain ]);
        }
        if ($entity->dflt) {
            $this->db->table('sites')->filter("site", $entity->site, true)->update(["dflt" => 0]);
        }
        $this->jobs->cacheClean();
        $this->jobs->cachePublic();
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        $domains = array_filter(explode("\n", str_replace("\r", "", (string)$data['domains'])));
        foreach ($domains as $k => $v) {
            $domains[$k] = trim(preg_replace('(^https?\:)i', '', $v) ?? '', ' /');
        }
        $data['domains'] = implode("\n", $domains);
        if (!isset($data['langs']) || !is_array($data['langs'])) {
            $data['langs'] = [];
        }
        if (!(int)$data['tree']) {
            $data['tree'] = $this->createNode($data['name'], $data['langs']);
        }
        $entity = parent::update($id, $data);
        $available = $this->getAvailableLangs();
        foreach ($available as $k => $v) {
            if (!in_array($k, $data['langs'])) {
                $this->db->table('site_lang')
                    ->filter('site', $entity->site)
                    ->filter('lang', $k)
                    ->delete();
            }
        }
        foreach ($data['langs'] as $lang) {
            if (
                isset($available[$lang]) &&
                !$this->db->val("SELECT site FROM site_lang WHERE site = ? AND lang = ?", [ $entity->site, $lang ])
            ) {
                $this->db->table('site_lang')->insert([
                    'site' => $entity->site,
                    'lang' => $lang
                ]);
                $tree = Tree::fromDatabase(
                    $this->db,
                    'tree_struct',
                    [
                        'id'       => 'id',
                        'parent'   => 'pid',
                        'position' => 'pos',
                        'level'    => 'lvl',
                        'left'     => 'lft',
                        'right'    => 'rgt'
                    ]
                );
                $nodes = [ (int)$data['tree'] ];
                foreach ($tree->getNode((int)$data['tree'])?->getDescendants() ?? [] as $node) {
                    $nodes[] = $node->id;
                }
                $code = $this->db->one("SELECT code FROM languages WHERE lang = ?", $lang);
                foreach ($nodes as $node) {
                    foreach (['tree_data', 'tree_data_pub'] as $table) {
                        if ($this->db->one("SELECT 1 FROM {$table} WHERE id = ? and lang = ?", [ $node, $lang ])) {
                            continue;
                        }
                        $this->db->query(
                            "INSERT INTO {$table} (
                            id,
                            lang,
                            version,
                            from_version,
                            created,
                            usr,
                            title,
                            hidden,
                            url,
                            redirect,
                            settings,
                            content,
                            permissions,
                            template,
                            menu,
                            published
                            ) VALUES (?, ?, 1, NULL, ?, 1, ?, 1, ?, '', '{}', '{}', NULL, 1, NULL, 1)",
                            [
                                $node,
                                $lang,
                                date('Y-m-d H:i:s'),
                                $node,
                                $code . '/' . $node
                            ]
                        );
                    }
                }
            }
        }
        $this->db->table('site_domain')->filter("site", $entity->site)->delete();
        foreach ($domains as $domain) {
            $this->db->table('site_domain')->insert([ 'site' => $entity->site, 'domain' => $domain ]);
        }
        if ($entity->dflt) {
            $this->db->table('sites')->filter("site", $entity->site, true)->update(["dflt" => 0]);
        }
        $this->jobs->cacheClean();
        $this->jobs->cachePublic();
        return $entity;
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $arr = parent::toArray($entity, $relations);
        $arr['langs'] = $entity->languages
            ->clone()
            ->map(function (LanguagesEntity $v): int {
                return $v->lang;
            })
            ->toArray();
        return $arr;
    }
    /**
     * @return array<array{id:int,text:string,parent:int|string}>
     */
    public function tree(): array
    {
        $tree = $this->db->rows(
            "SELECT
            id,
            (SELECT title FROM tree_data WHERE id = tree_struct.id AND lang = 1 AND published = 1) AS text,
            pid as parent,
            lvl
            FROM tree_struct
            ORDER BY lft"
        )->toArray();
        $root = null;
        foreach ($tree as $k => $v) {
            $tree[$k]['icon'] = $v['id'] === 1 ?
            'ui purple sitemap icon' :
            ($v['lvl'] > 1 ? 'ui grey file icon' : 'ui orange globe icon');
            if ($v['parent'] === null) {
                $tree[$k]['parent'] = '#';
                $root = $v['id'];
            }
        }
        $tree[] = [
            'id' => 0,
            'text' => '<b>СЪЗДАЙ НОВА БРЪНКА</b>',
            'parent' => $root,
            'icon' => 'ui green plus icon'
        ];
        return $tree;
    }
}
