<?php

declare(strict_types=1);

namespace webadmin\modules\site\pages;

use webadmin\Jobs;
use vakata\database\DBInterface;
use vakata\user\User;
use vakata\user\UserManagementInterface;
use vakata\phptree\Tree;
use vakata\phptree\Node;
use vakata\collection\Collection;
use webadmin\components\html\Form;
use vakata\intl\Intl;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\ModulesContainer;

class PagesService
{
    protected ModulesContainer $mc;
    protected DBInterface $db;
    /** @var array<string,scalar|null>|null $site */
    protected ?array $site;
    protected ?Tree $tree = null;
    protected User $user;
    protected Jobs $jobs;
    protected UserManagementInterface $usrm;
    protected string $stable;
    protected string $dtable;
    /** @var array<int,string> $languages */
    protected array $languages = [];
    protected bool $multiLanguage = false;

    public function __construct(
        ModulesContainer $mc,
        DBInterface $db,
        User $user,
        UserManagementInterface $usrm,
        Jobs $jobs
    ) {
        if (!count($user->languages ?? [])) {
            throw new CRUDException('No languages configured for user');
        }
        $this->mc = $mc;
        $this->db = $db;
        $this->user = $user;
        $this->usrm = $usrm;
        $this->jobs = $jobs;
        $this->stable = "tree_struct";
        $this->dtable = "tree_data";
        $this->multiLanguage = $this->db->val("SELECT COUNT(lang) FROM site_lang WHERE site = ?", [$user->site]) > 1;
        $this->languages = $this->db->rows(
            "SELECT lang, code FROM languages WHERE lang IN (??)",
            [ array_merge(['' => 0], array_keys($this->user->languages)) ]
        )->toArray('lang', 'code');
        $this->site = $user->site ?
            $this->db->row("SELECT * FROM sites WHERE site = ?", [$user->site]) :
            $this->db->row("SELECT * FROM sites WHERE dflt = 1");
        $this->tree = null;
    }

    public function domain(): string
    {
        return trim(explode("\n", trim((string)($this->site['domains'] ?? '')))[0]);
    }
    public function availableLanguages(): array
    {
        return $this->languages;
        // return $this->db->definition($this->dtable)->getColumn('lang')->getValues();
    }
    public function canChangeStructure(): bool
    {
        return $this->user->hasPermission('pages/structure');
    }
    public function canChangeWidgets(): bool
    {
        return $this->user->hasPermission('pages/widgets');
    }
    public function canChangePermissions(): bool
    {
        return $this->user->hasPermission('pages/permissions', true);
    }
    public function canPublish(): bool
    {
        return $this->user->hasPermission('pages/publish');
    }

    protected function tree(): Tree
    {
        if (isset($this->tree)) {
            return $this->tree;
        }
        return $this->tree = Tree::fromDatabase(
            $this->db,
            $this->stable,
            [
                'id'       => 'id',
                'parent'   => 'pid',
                'position' => 'pos',
                'level'    => 'lvl',
                'left'     => 'lft',
                'right'    => 'rgt'
            ]
        );
    }
    protected function saveTree(bool $transaction = true): array
    {
        return $this->tree()->toDatabase(
            $this->db,
            $this->stable,
            [
                'id'       => 'id',
                'parent'   => 'pid',
                'position' => 'pos',
                'level'    => 'lvl',
                'left'     => 'lft',
                'right'    => 'rgt'
            ],
            $transaction
        );
    }
    public function getChildren(int $node, int $lang): array
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        $node = $this->tree()->getNode($node);
        if (!isset($node)) {
            throw new \Exception('Invalid node');
        }
        $nodes = $node->getChildren();
        $ids = Collection::from($nodes)
            ->map(function (Node $v): int {
                return (int)$v->id;
            })
            ->toArray();
        $data = [];
        $stale = [];
        if (count($ids)) {
            $data = $this->db->rows(
                "SELECT id, title, hidden
                 FROM {$this->dtable}
                 WHERE lang = ? AND id IN (??) AND published = 1",
                [ $lang, $ids ],
            )
            ->toArray('id');
            $stale = $this->db->col(
                "SELECT id FROM {$this->dtable} t
                 WHERE
                    t.published = 1 AND lang = ? AND id IN (??) AND
                    EXISTS (
                        SELECT 1 FROM {$this->dtable}
                        WHERE id = t.id AND lang = t.lang AND version > t.version AND published <> 2
                    )",
                [ $lang, $ids ]
            );
        }
        return Collection::from($nodes)
            ->each(function (Node $v) use ($data, $stale) {
                $temp = $data[$v->id] ?? [];
                $temp['stale'] = in_array($v->id, $stale);
                $v->data = $temp;
            })
            ->toArray();
    }
    public function getRoots(int $lang): array
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        if ($this->site && (int)$this->site['tree']) {
            $nodes = [ $this->tree()->getNode((int)$this->site['tree']) ];
        } else {
            $nodes = $this->tree()->getRoot()->getChildren();
        }
        $ids = Collection::from($nodes)
            ->map(function (Node $v): int {
                return (int)$v->id;
            })
            ->toArray();
        $data = $this->db->rows(
            "SELECT id, title, hidden
             FROM {$this->dtable}
             WHERE lang = ? AND id IN (??) AND published = 1",
            [ $lang, $ids ]
        )
        ->toArray('id');
        $stale = $this->db->col(
            "SELECT id FROM {$this->dtable} t
             WHERE
                t.published = 1 AND lang = ? AND id IN (??) AND
                EXISTS (SELECT 1 FROM {$this->dtable} WHERE id = t.id AND lang = t.lang AND version > t.version)",
            [ $lang, $ids ]
        );
        return Collection::from($nodes)
            ->each(function (Node $v) use ($data, $stale) {
                $temp = $data[$v->id] ?? [];
                $temp['stale'] = in_array($v->id, $stale);
                $v->data = $temp;
            })
            ->toArray();
    }
    public function getPermission(int $node, int $lang): int
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        $node = $this->tree()->getNode($node);
        if (!$node) {
            throw new \Exception('Node not found');
        }
        if ($this->user->hasPermission('pages/permissions')) {
            return 2;
        }
        $permissions = (string)$this->db->val(
            "SELECT d.permissions
             FROM {$this->dtable} d, {$this->stable} s
             WHERE
                 s.id = d.id AND s.lft <= ? AND s.rgt >= ? AND
                 d.lang = ? AND d.published = 1 AND d.permissions IS NOT NULL AND d.permissions <> ''
             ORDER BY s.lft DESC",
            [ $node->lft, $node->rgt, $lang ]
        );
        $level = 2;
        if ($permissions && ($permissions = json_decode($permissions, true)) && is_array($permissions)) {
            $level = 0;
            foreach ($permissions['editors'] as $permission) {
                $permission = explode('_', $permission);
                if (
                    $permission[0] === 'user' &&
                    (int)$permission[1] &&
                    (int)$this->user->getID() === (int)$permission[1]
                ) {
                    $level = 1;
                }
                if ($permission[0] === 'group' && (int)$permission[1] && $this->user->inGroup($permission[1])) {
                    $level = 1;
                }
                if (
                    $permission[0] === 'org' &&
                    (int)$permission[1] &&
                    isset($this->user->organization[(int)$permission[1]])
                ) {
                    $level = 1;
                }
            }
            foreach ($permissions['publishers'] as $permission) {
                $permission = explode('_', $permission);
                if (
                    $permission[0] === 'user' &&
                    (int)$permission[1] &&
                    (int)$this->user->getID() === (int)$permission[1]
                ) {
                    $level = 2;
                }
                if ($permission[0] === 'group' && (int)$permission[1] && $this->user->inGroup($permission[1])) {
                    $level = 2;
                }
                if (
                    $permission[0] === 'org' &&
                    (int)$permission[1] &&
                    isset($this->user->organization[(int)$permission[1]])
                ) {
                    $level = 2;
                }
            }
        }
        return $level;
    }
    public function getNode(int $node, int $lang): Node
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        $node = $this->tree()->getNode($node);
        if (!$node) {
            throw new \Exception('Node not found');
        }
        $level = $this->getPermission($node->id, $lang);
        if ($level === 0) {
            throw new PagesException();
        }
        $temp = $this->db->row(
            "SELECT * FROM {$this->dtable} WHERE lang = ? AND id = ? AND published = 1",
            [ $lang, $node->id ]
        );
        $temp['canPublish'] = $level === 2;
        $temp['stale'] = $this->db->val(
            "SELECT id FROM {$this->dtable} t
             WHERE id = ? AND lang = ? AND version > ?",
            [ $node->id, $lang, $temp['version'] ]
        ) !== null;
        $node->data = $temp;
        return $node;
    }
    public function getNodes(array $nodes, int $lang): array
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        return Collection::from($nodes)
            ->mapKey(function (string|int $v) {
                return $v;
            })
            ->map(function (string|int $v) use ($lang) {
                return $this->getChildren((int)$v, $lang);
            })
            ->toArray();
    }
    public function search(string $query, int $lang): array
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        switch ($this->db->driverName()) {
            case 'postgre':
                $ids = $this->db->col(
                    "SELECT s.id FROM {$this->dtable} d, {$this->stable} s
                    WHERE d.lang = ? AND d.id = s.id AND d.title ILIKE ? AND d.published = 1",
                    [ $lang, '%' . str_replace(['%', '_'], ['\\%','\\_'], $query) . '%']
                );
                break;
            case 'oracle':
                $ids = $this->db->col(
                    "SELECT s.id FROM {$this->dtable} d, {$this->stable} s
                    WHERE d.lang = ? AND d.id = s.id AND UPPER(d.title) LIKE ? AND d.published = 1",
                    [ $lang, '%' . str_replace(['%', '_'], ['\\%','\\_'], mb_strtoupper($query)) . '%']
                );
                break;
            default:
                $ids = $this->db->col(
                    "SELECT s.id FROM {$this->dtable} d, {$this->stable} s
                    WHERE d.lang = ? AND d.id = s.id AND d.title LIKE ? AND d.published = 1",
                    [ $lang, '%' . str_replace(['%', '_'], ['\\%','\\_'], $query) . '%']
                );
                break;
        }
        return Collection::from($ids)
            ->map(function (string|int $v) {
                return $this->tree()->getNode((int)$v);
            })
            ->toArray();
    }
    public function searchParents(string $query, int $lang): array
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        return Collection::from($this->search($query, $lang))
            ->map(function (Node $v) {
                return $v->getAncestors();
            })
            ->flatten()
            ->map(function (Node $node): int {
                return (int)$node->id;
            })
            ->unique()
            ->toArray();
    }
    public function createNode(int $parent, ?int $position, int $language, ?string $title = null): int
    {
        if (!isset($this->languages[$language])) {
            throw new \Exception('Forbidden', 403);
        }
        if (!$this->canChangeStructure()) {
            throw new \Exception('Not allowed', 403);
        }
        $node = new Node();
        $parent = $this->tree()->getNode($parent);
        if (!isset($parent)) {
            throw new \Exception('Invalid node');
        }
        $parent->addChild($node, $position);
        $this->db->begin();
        try {
            $this->saveTree(false);
            foreach ($this->availableLanguages() as $lang => $code) {
                $template = null;
                $parentData = $this->db->row(
                    $this->db->driverName() !== 'oracle' ?
                        "SELECT template, url
                        FROM {$this->dtable}
                        WHERE id = ? AND lang = ?
                        ORDER BY published DESC, id DESC LIMIT 1" :
                        "SELECT template, url
                        FROM {$this->dtable}
                        WHERE id = ? AND lang = ?
                        ORDER BY published DESC, id DESC FETCH FIRST 1 ROW ONLY",
                    [ $parent->id, $lang ]
                );
                if ($parentData) {
                    $template = (int)$this->db->val(
                        "SELECT child_default FROM templates WHERE template = ?",
                        [$parentData['template']]
                    );
                }
                if (!$template) {
                    $template = $this->db->val(
                        $this->db->driverName() !== 'oracle' ?
                            "SELECT template FROM templates ORDER BY is_default DESC, template ASC LIMIT 1" :
                            "SELECT template FROM templates ORDER BY is_default DESC, template ASC
                             FETCH FIRST 1 ROW ONLY"
                    );
                }
                $url = $parentData && $parentData['url'] ?
                    rtrim((string)$parentData['url'], '/*') . '/' . $node->id :
                    ($this->multiLanguage ? $code . '/' . $node->id : $node->id);
                $url = (string)$url;
                $this->saveData((int)$node->id, (int)$lang, [
                    'id'          => $node->id,
                    'url'         => $url,
                    'title'       => ($title ?? '') . ($lang !== $language ? ' (' . $code . ')' : ''),
                    'hidden'      => 1,
                    'lang'        => $lang,
                    'template'    => $template,
                    'menu'        => null,
                    'content'     => json_encode(['content' => ''], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'permissions' => json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'settings'    => json_encode(
                        [
                            'url'         => $url,
                            'redirect'    => "",
                            'sitemap'     => 1,
                            'parentmenu'  => 1,
                            'breadcrumb'  => 1,
                            'meta'        => "{}",
                            'nocache'     => 0,
                            'head'        => ""
                        ],
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    )
                ], true, false, false);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
        $this->jobs->cachePublic();
        return $node->id;
    }
    public function moveNode(int $node, int $parent, ?int $position = null): void
    {
        if (!$this->canChangeStructure()) {
            throw new \Exception('Not allowed', 403);
        }
        $node = $this->tree()->getNode($node);
        if (!isset($node)) {
            throw new \Exception('Invalid node');
        }
        $parent = $this->tree()->getNode($parent);
        if (!isset($parent)) {
            throw new \Exception('Invalid node');
        }
        $node->moveTo($parent, $position);
        $this->saveTree();
        $this->jobs->cachePublic();
    }
    public function copyNode(int $node, int $parent, ?int $position = null): int
    {
        if (!$this->canChangeStructure()) {
            throw new \Exception('Not allowed', 403);
        }
        $node = $this->tree()->getNode($node);
        if (!isset($node)) {
            throw new \Exception('Invalid node');
        }
        $parent = $this->tree()->getNode($parent);
        if (!isset($parent)) {
            throw new \Exception('Invalid node');
        }
        $copy = $node->copyTo($parent, $position);
        $this->db->begin();
        try {
            $this->saveTree(false);
            $desc = $copy->getDescendants();
            $orig = $node->getDescendants();
            foreach ($this->availableLanguages() as $lang => $code) {
                $this->db->query(
                    "INSERT INTO {$this->dtable} 
                        (lang, version, created, usr, title, hidden, redirect, settings, content, template, menu,
                            published, id, url)
                    SELECT
                        lang, 1, ?, usr, title, hidden, redirect, settings, content, template, menu,
                        published, ? AS id, ? AS url
                    FROM {$this->dtable} WHERE id = ? AND lang = ? AND published = 1",
                    [
                        date('Y-m-d H:i:s'),
                        $copy->id,
                        $this->multiLanguage ? $code . '/' . $copy->id : (string)$copy->id,
                        $node->id,
                        $lang
                    ]
                );
                $this->db->query(
                    "INSERT INTO {$this->dtable}_pub 
                        (lang, version, created, usr, title, hidden, redirect, settings, content, template, menu,
                            published, id, url)
                    SELECT
                        lang, 1, ?, usr, title, hidden, redirect, settings, content, template, menu,
                        published, ? AS id, ? AS url
                    FROM {$this->dtable}_pub WHERE id = ? AND lang = ? AND published = 1",
                    [
                        date('Y-m-d H:i:s'),
                        $copy->id,
                        $this->multiLanguage ? $code . '/' . $copy->id : (string)$copy->id,
                        $node->id,
                        $lang
                    ]
                );
                foreach ($desc as $k => $v) {
                    $this->db->query(
                        "INSERT INTO {$this->dtable}
                            (lang, version, created, usr, title, hidden, redirect, settings, content, template, menu,
                                published, id, url)
                        SELECT
                            lang, 1, ?, usr, title, hidden, redirect, settings, content, template, menu,
                            published, ? AS id, ? AS url
                        FROM {$this->dtable} WHERE id = ? AND lang = ? AND published = 1",
                        [
                            date('Y-m-d H:i:s'),
                            $v->id,
                            $this->multiLanguage ? $code . '/' . $v->id : (string)$v->id,
                            $orig[$k]->id,
                            $lang
                        ]
                    );
                    $this->db->query(
                        "INSERT INTO {$this->dtable}_pub
                            (lang, version, created, usr, title, hidden, redirect, settings, content, template, menu,
                                published, id, url)
                        SELECT
                            lang, 1, ?, usr, title, hidden, redirect, settings, content, template, menu,
                            published, ? AS id, ? AS url
                        FROM {$this->dtable}_pub WHERE id = ? AND lang = ? AND published = 1",
                        [
                            date('Y-m-d H:i:s'),
                            $v->id,
                            $this->multiLanguage ? $code . '/' . $v->id : (string)$v->id,
                            $orig[$k]->id,
                            $lang
                        ]
                    );
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
        $this->jobs->cachePublic();
        return $copy->id;
    }
    public function removeNode(int $node): void
    {
        if (!$this->canChangeStructure()) {
            throw new \Exception('Not allowed', 403);
        }
        $temp = [$node];
        $node = $this->tree()->getNode($node);
        if (!isset($node)) {
            throw new \Exception('Invalid node');
        }
        $roots = [];
        foreach (array_keys($this->availableLanguages()) as $lang) {
            $roots = array_merge($roots, $this->getRoots((int)$lang));
        }
        if (in_array($node, $roots)) {
            throw new \Exception('Root node');
        }
        foreach ($node->getDescendants() as $n) {
            $temp[] = $n->id;
        }
        $this->db->commit();
        try {
            $this->db->query(
                "DELETE FROM {$this->dtable} WHERE id IN (??)",
                [$temp]
            );
            $this->db->query(
                "DELETE FROM {$this->dtable}_pub WHERE id IN (??)",
                [$temp]
            );
            $node->remove();
            $this->saveTree(false);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
        $this->jobs->cachePublic();
    }
    public function renameNode(int $id, int $lang, string $title): void
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        $this->db->query(
            "UPDATE {$this->dtable} SET title = ? WHERE id = ? AND lang = ?",
            [ $title, $id, $lang ]
        );
        $this->db->query(
            "UPDATE {$this->dtable} SET title = ? WHERE id = ? AND lang <> ? AND title = ?",
            [
                $title . ' (' . $this->languages[$lang] . ')',
                $id,
                $lang,
                'New node (' . $this->languages[$lang] . ')'
            ]
        );
        $this->db->query(
            "UPDATE {$this->dtable}_pub SET title = ? WHERE id = ? AND lang = ?",
            [ $title, $id, $lang ]
        );
        $this->db->query(
            "UPDATE {$this->dtable}_pub SET title = ? WHERE id = ? AND lang <> ? AND title = ?",
            [
                $title . ' (' . $this->languages[$lang] . ')',
                $id,
                $lang,
                'New node (' . $this->languages[$lang] . ')'
            ]
        );
    }
    public function showNode(int $id, int $lang): void
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        $this->db->query(
            "UPDATE {$this->dtable} SET hidden = ? WHERE id = ? AND lang = ?",
            [ 0, $id, $lang ]
        );
        $this->db->query(
            "UPDATE {$this->dtable}_pub SET hidden = ? WHERE id = ? AND lang = ?",
            [ 0, $id, $lang ]
        );
        $this->jobs->cachePublic();
    }
    public function hideNode(int $id, int $lang): void
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        $this->db->query(
            "UPDATE {$this->dtable} SET hidden = ? WHERE id = ? AND lang = ?",
            [ 1, $id, $lang ]
        );
        $this->db->query(
            "UPDATE {$this->dtable}_pub SET hidden = ? WHERE id = ? AND lang = ?",
            [ 1, $id, $lang ]
        );
        $this->jobs->cachePublic();
    }
    public function nodeVersions(int $id, int $lang, bool $previews = false): array
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        return $this->db->rows(
            "SELECT t.version, t.created, t.published, t.url, u.name FROM {$this->dtable} t, users u 
             WHERE t.usr = u.usr AND t.id = ? AND t.lang = ? AND published IN (??)
             ORDER BY t.created DESC",
            [ $id, $lang, $previews ? [0,1,2] : [0,1] ]
        )->toArray();
    }
    public function nodeVersion(int $id, int $lang, int $version): array
    {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        return $this->db->row(
            "SELECT * FROM {$this->dtable} WHERE id = ? AND lang = ? AND version = ?",
            [ $id, $lang, $version ]
        ) ?? [ 'id' => $id, 'lang' => $lang, 'content' => '' ];
    }
    public function saveData(
        int $id,
        int $lang,
        array $data,
        bool $publish,
        bool $preview = false,
        bool $rebuild = true
    ): void {
        if (!isset($this->languages[$lang])) {
            throw new \Exception('Forbidden', 403);
        }
        if ($preview) {
            $publish = 0;
        }
        $level = $this->getPermission($id, $lang);
        if ($level === 0) {
            throw new \Exception('Forbidden', 403);
        }
        if ($publish && !$this->canPublish()) {
            throw new \Exception('Forbidden', 403);
        }
        if ($publish && $level !== 2) {
            throw new \Exception('Forbidden', 403);
        }

        $all = [
            'title',
            'hidden',
            'url',
            'redirect',
            'settings',
            'content',
            'permissions',
            'template',
            'menu'
        ];

        if (isset($data['permissions']) && $this->canChangePermissions()) {
            $permissions = json_decode($data['permissions'], true);
            if (!$permissions) {
                $data['permissions'] = null;
            } else {
                $editors = [];
                $publishers = [];
                foreach ($permissions['editors'] ?? [] as $editor) {
                    if (strpos($editor, '_')) {
                        $editors[] = $editor;
                    }
                }
                foreach ($permissions['publishers'] ?? [] as $publisher) {
                    if (strpos($publisher, '_')) {
                        $publishers[] = $publisher;
                    }
                }
                $data['permissions'] = count($editors) || count($publishers) ?
                    json_encode(
                        ['editors' => $editors, 'publishers' => $publishers],
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    ) :
                    null;
            }
        }

        if (isset($data['settings'])) {
            $settings = json_decode($data['settings'], true);
            if (!$settings) {
                throw new \Exception('Invalid settings');
            }
            $settings['redirect'] = implode(
                '/',
                array_map(function ($v) {
                    return mb_strtolower(rawurldecode($v));
                }, explode('/', $settings['redirect']))
            );
            foreach ($all as $field) {
                if (isset($settings[$field])) {
                    $data[$field] = $settings[$field];
                }
            }
            $settings['url'] = rtrim($settings['url'], '/*');
            $data['settings'] = json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $data['url'] = implode(
            '/',
            array_map(function ($v) {
                return mb_strtolower(rawurldecode($v));
            }, explode('/', $data['url']))
        );

        $name = $this->db->one('SELECT base FROM templates WHERE template = ?', [ $data['template'] ?? null ]);

        if (!$name) {
            throw new \Exception('Invalid template', 400);
        }
        $template = $this->mc->getTemplate($name);

        $data['url'] = rtrim($data['url'], '/*');
        if ($template->wildcard()) {
            $data['url'] .= '/*';
        }

        // if (!isset($data['url']) || !$data['url']) {
        //     $data['url'] = $this->multiLanguage ? $this->languages[$lang] . '/' . $id : $id;
        // }
        if (!isset($data['menu']) || !(int)$data['menu']) {
            $data['menu'] = null;
        }
        if ($this->site && (int)$this->site['tree']) {
            $ids = $this->db->col(
                "SELECT id FROM {$this->dtable} WHERE id <> ? AND url = ? AND published = 1",
                [$id, $data['url']]
            );
            $n = $this->tree()->getNode((int)$this->site['tree']);
            if (!isset($n)) {
                throw new \Exception('Invalid node');
            }
            foreach ($n->getDescendants() as $dsc) {
                if (in_array($dsc->id, $ids)) {
                    throw new \Exception('URL already in use', 400);
                }
            }
        } else {
            if (
                $this->db->val(
                    "SELECT 1 FROM {$this->dtable} WHERE id <> ? AND url = ? AND published = 1",
                    [$id, $data['url']]
                )
            ) {
                throw new \Exception('URL already in use', 400);
            }
        }

        $temp = $this->db->row(
            "SELECT * FROM {$this->dtable} WHERE id = ? AND lang = ? AND published = 1",
            [ $id, $lang ]
        );
        $content = null;
        if ($temp) {
            $data['title'] = $temp['title'];
            $data['hidden'] = $temp['hidden'];
            $content = $temp['content'];
        }
        if (!$this->canChangePermissions()) {
            $data['permissions'] = $temp ? ($temp['permissions'] ?? null) : null;
        }
        if (!$content || !is_string($content)) {
            $content = '[]';
        }
        $content = json_decode($content, true) ?? [];
        $temp = json_decode($data['content'], true);
        if ($temp === null) {
            throw new \Exception('Invalid settings');
        }
        $content[$data['template']] = $temp;
        $widgets = json_decode($data['widgets'] ?? '', true) ?? [];
        $content['widgets'] = $widgets;
        $data['content'] = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $data = array_merge($content, $data);

        $ins = [ 'id', 'lang', 'version', 'from_version', 'created', 'usr', 'published' ];
        $par = [
            $id,
            $lang,
            (int)$this->db->val(
                "SELECT MAX(version) FROM {$this->dtable} WHERE id = ? AND lang = ?",
                [ $id, $lang ]
            ) + 1,
            isset($data['version']) && (int)$data['version'] ? (int)$data['version'] : null,
            date('Y-m-d H:i:s'),
            $this->user->getID(),
            $preview ? 2 : ($publish ? 1 : 0)
        ];
        foreach ($data as $k => $v) {
            if (!in_array($k, $all)) {
                continue;
            }
            $ins[] = $k;
            $par[] = is_array($v) || is_object($v) ?
                json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) :
                $v;
        }
        if ($publish) {
            $this->db->query(
                "UPDATE {$this->dtable}
                    SET published = 0
                    WHERE id = ? AND lang = ? AND published = 1",
                [ $id, $lang ]
            );
        }
        $this->db->query(
            "INSERT INTO {$this->dtable} (" . implode(', ', $ins) . ") VALUES (??)",
            [$par]
        );
        if ($publish) {
            if (
                $this->db->one(
                    "SELECT 1 FROM {$this->dtable}_pub WHERE id = ? AND lang = ?",
                    [ $data['id'], $data['lang'] ]
                )
            ) {
                $cols = [];
                $pars = [];
                foreach ($ins as $k => $c) {
                    if ($c !== 'id' && $c !== 'lang') {
                        $cols[] = $c . ' = ?';
                        $pars[] = $par[$k] ?? null;
                    }
                }
                $pars[] = $data['id'];
                $pars[] = $data['lang'];
                $this->db->query(
                    "UPDATE {$this->dtable}_pub SET " . implode(', ', $cols) . " WHERE id = ? AND lang = ?",
                    $pars
                );
            } else {
                $this->db->query(
                    "INSERT INTO {$this->dtable}_pub (" . implode(', ', $ins) . ") VALUES (??)",
                    [$par]
                );
            }
        }
        if ($publish && $rebuild) {
            $this->jobs->cachePublic();
        }
    }

    public function buildPreview(): void
    {
        $this->jobs->cachePublic(true);
    }

    public function permissionOptions(Intl $intl): array
    {
        $data = [];
        $data[] = [
            'id' => 'users',
            'parent' => '#',
            'icon' => 'ui user icon',
            'state' => [ 'disabled' => true ],
            'text' => $intl('pages.permissions.users')
        ];
        foreach ($this->db->rows("SELECT usr, name FROM users ORDER BY name")->toArray() as $user) {
            $data[] = [
                'id' => 'user_' . $user['usr'],
                'icon' => 'ui user icon',
                'parent' => 'users',
                'text' => $user['name']
            ];
        }
        $data[] = [
            'id' => 'groups',
            'parent' => '#',
            'icon' => 'ui users icon',
            'state' => [ 'disabled' => true ],
            'text' => $intl('pages.permissions.groups')
        ];
        foreach ($this->db->rows("SELECT grp, name FROM grps ORDER BY name")->toArray() as $group) {
            $data[] = [
                'id' => 'group_' . $group['grp'],
                'icon' => 'ui users icon',
                'parent' => 'groups',
                'text' => $group['name']
            ];
        }
        $data[] = [
            'id' => 'organization',
            'parent' => '#',
            'icon' => 'ui sitemap icon',
            'state' => [ 'disabled' => true ],
            'text' => $intl('pages.permissions.organization')
        ];
        foreach ($this->db->rows("SELECT org, pid, title FROM organization ORDER BY lft")->toArray() as $org) {
            $data[] = [
                'id' => 'org_' . $org['org'],
                'parent' => $org['pid'] ? 'org_' . $org['pid'] : 'organization',
                'text' => $org['title']
            ];
        }
        return $data;
    }

    public function baseTemplates(): array
    {
        $templates = $this->mc->getTemplates();
        return array_combine($templates, $templates);
    }
    public function templates(): array
    {
        return $this->db->rows("SELECT template, name, base, zones FROM templates ORDER BY is_default DESC, name ASC")
            ->toArray();
    }
    public function template(string $name, array $data = [], array $context = []): Form
    {
        return $this->mc->getTemplate($name)->getForm($data, $context);
    }
    public function widgets(): array
    {
        $widgets = $this->mc->getWidgets();
        return array_combine($widgets, $widgets);
    }
    public function widget(string $name, array $data = [], array $context = []): Form
    {
        return $this->mc->getWidget($name)->getForm($data, $context);
    }
    public function menus(): array
    {
        return [ 0 => 'По подразбиране' ] + $this->db->rows(
            "SELECT menu, name FROM menus WHERE site = ? ORDER BY is_default DESC, name ASC",
            [ $this->user->site ]
        )
            ->toArray('menu', 'name');
    }
}
