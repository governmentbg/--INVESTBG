<?php

declare(strict_types=1);

namespace webadmin\modules\administration\organization;

use vakata\database\DBInterface;
use vakata\user\User;
use vakata\phptree\Tree;
use vakata\phptree\Node;
use vakata\collection\Collection;

class OrganizationService
{
    protected DBInterface $db;
    protected User $user;
    protected string $table;
    protected ?Tree $tree = null;

    public function __construct(DBInterface $db, User $user)
    {
        $this->db = $db;
        $this->user = $user;
        $this->table = "organization";
    }
    protected function items(): array
    {
        $data = $this->db->rows(
            "SELECT DISTINCT o2.lft, o2.org, o2.title
             FROM
                 organization o1,
                 organization o2,
                 user_organizations uo
             WHERE o1.org = uo.org AND o2.lft >= o1.lft AND o2.rgt <= o1.rgt AND uo.usr = ?
             ORDER BY o2.lft",
            [$this->user->getID()]
        )
        ->toArray('org', 'title');
        return $data;
    }
    protected function contains(int $root, array $nodes): bool
    {
        if (!count($nodes)) {
            return false;
        }
        $root = $this->db->row("SELECT lft, rgt FROM organization WHERE org = ?", [$root]);
        if (!$root) {
            return false;
        }
        return $this->db->val(
            "SELECT 1 FROM organization WHERE org IN (??) AND (lft < ? OR rgt > ?)",
            [ $nodes, $root['lft'], $root['rgt'] ]
        ) === null;
    }
    protected function tree(): Tree
    {
        if (isset($this->tree)) {
            return $this->tree;
        }
        return $this->tree = Tree::fromDatabase(
            $this->db,
            $this->table,
            [
                'id'       => 'org',
                'parent'   => 'pid',
                'position' => 'pos',
                'level'    => 'lvl',
                'left'     => 'lft',
                'right'    => 'rgt',
                'title'    => 'title',
                'properties'    => 'properties',
            ]
        );
    }
    protected function saveTree(): array
    {
        return $this->tree()->toDatabase(
            $this->db,
            $this->table,
            [
                'id'       => 'org',
                'parent'   => 'pid',
                'position' => 'pos',
                'level'    => 'lvl',
                'left'     => 'lft',
                'right'    => 'rgt',
                'title'    => 'title'
            ]
        );
    }
    public function getRoots(): array
    {
        $roots = $this->db->rows(
            "SELECT DISTINCT o1.org, o1.lft, o1.pid, o1.title
             FROM
                 organization o1,
                 user_organizations uo
             WHERE o1.org = uo.org AND uo.usr = ?
             ORDER BY o1.lft",
            [$this->user->getID()]
        )
        ->toArray('org');
        $new = [];
        foreach ($roots as $id => $root) {
            if (!isset($roots[(int)$root['pid']])) {
                $new[$id] = $root['title'];
            }
        }
        return $new;
    }
    public function getChildren(int $root, int $node): array
    {
        return $this->getNode($root, $node)->getChildren();
    }
    public function getNode(int $root, int $node): Node
    {
        if (!in_array($root, array_keys($this->getRoots()))) {
            throw new \Exception('Invalid root');
        }
        if (!$this->contains($root, [$node])) {
            throw new \Exception('Invalid node');
        }
        $node = $this->tree()->getNode($node);
        if (!isset($node)) {
            throw new \Exception('Invalid node');
        }
        return $node;
    }
    public function getNodes(int $root, array $nodes): array
    {
        return Collection::from($nodes)
            ->mapKey(function (string|int $v) {
                return $v;
            })
            ->map(function (string|int $v) use ($root) {
                return $this->getChildren($root, (int)$v);
            })
            ->toArray();
    }
    public function createNode(int $root, int $parent, ?int $position = null): int
    {
        if (!in_array($root, array_keys($this->getRoots()))) {
            throw new \Exception('Invalid root');
        }
        if (!$this->contains($root, [$parent])) {
            throw new \Exception('Invalid node');
        }
        $tree = $this->tree();
        $node = new Node();
        $parent = $tree->getNode($parent);
        if (!isset($parent)) {
            throw new \Exception('Invalid node');
        }
        $parent->addChild($node, $position);
        $this->saveTree();
        return $node->org;
    }
    public function moveNode(int $root, int $node, int $parent, ?int $position = null): void
    {
        if (!in_array($root, array_keys($this->getRoots()))) {
            throw new \Exception('Invalid root');
        }
        if (!$this->contains($root, [$node, $parent])) {
            throw new \Exception('Invalid node');
        }
        $tree = $this->tree();
        $node = $tree->getNode($node);
        if (!isset($node)) {
            throw new \Exception('Invalid node');
        }
        $parent = $tree->getNode($parent);
        if (!isset($parent)) {
            throw new \Exception('Invalid node');
        }
        $node->moveTo($parent, $position);
        $this->saveTree();
    }
    public function copyNode(int $root, int $node, int $parent, ?int $position = null): int
    {
        if (!in_array($root, array_keys($this->getRoots()))) {
            throw new \Exception('Invalid root');
        }
        if (!$this->contains($root, [$node, $parent])) {
            throw new \Exception('Invalid node');
        }
        $tree = $this->tree();
        $node = $tree->getNode($node);
        if (!isset($node)) {
            throw new \Exception('Invalid node');
        }
        $parent = $tree->getNode($parent);
        if (!isset($parent)) {
            throw new \Exception('Invalid node');
        }
        $copy = $node->copyTo($parent, $position);
        $this->saveTree();
        return $copy->org;
    }
    public function checkForUsers(int $root, int $node): array
    {
        if (!in_array($root, array_keys($this->getRoots()))) {
            throw new \Exception('Invalid root');
        }
        if (!$this->contains($root, [$node])) {
            throw new \Exception('Invalid node');
        }
        // check for orphan users
        $lri = $this->db->row("SELECT lft, rgt FROM organization WHERE org = ?", [$node]);
        $ids = $lri !== null ? $this->db->col(
            "SELECT org FROM organization WHERE lft >= ? AND rgt <= ?",
            [ $lri['lft'], $lri['rgt'] ]
        ) : [];
        $usr = $this->db->rows(
            "SELECT u.name, o.title
             FROM
                user_organizations uo,
                users u,
                organization o
             WHERE
                u.usr = uo.usr AND
                o.org = uo.org AND
                uo.org IN (??) AND
                NOT EXISTS (
                    SELECT 1 FROM user_organizations WHERE usr = uo.usr AND org <> uo.org
                )",
            [$ids]
        )->toArray();
        return $usr;
    }
    public function removeNode(int $root, int $node): void
    {
        if (!in_array($root, array_keys($this->getRoots()))) {
            throw new \Exception('Invalid root');
        }
        if (!$this->contains($root, [$node])) {
            throw new \Exception('Invalid node');
        }
        $node = $this->tree()->getNode($node);
        if (!isset($node)) {
            throw new \Exception('Invalid node');
        }
        $node->remove();
        $this->saveTree();
    }
    public function renameNode(int $root, int $node, string $name): void
    {
        if (!in_array($root, array_keys($this->getRoots()))) {
            throw new \Exception('Invalid root');
        }
        if (!$this->contains($root, [$node])) {
            throw new \Exception('Invalid node');
        }

        $this->db->table($this->table)->filter('org', $node)->update([ 'title' => $name ]);
    }
    public function setData(int $root, int $node, mixed $data): void
    {
        if (!in_array($root, array_keys($this->getRoots()))) {
            throw new \Exception('Invalid root');
        }
        if (!$this->contains($root, [$node])) {
            throw new \Exception('Invalid node');
        }

        $this->db->table($this->table)
            ->filter('org', $node)
            ->update([ 'properties' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ]);
    }
    public function search(string $query): array
    {
        $ids = $this->db->col(
            "SELECT org FROM organization
             WHERE title LIKE ?",
            [ '%' . str_replace('%', '\%', $query) . '%']
        );
        return Collection::from($ids)
            ->map(function (int $v) {
                return $this->tree()->getNode($v);
            })
            ->toArray();
    }
    public function searchParents(string $query): array
    {
        return Collection::from($this->search($query))
            ->map(function (Node $v): array {
                return $v->getAncestors();
            })
            ->flatten()
            ->map(function (Node $v): int {
                return $v->org;
            })
            ->unique()
            ->values()
            ->toArray();
    }
}
