<?php

declare(strict_types=1);

namespace webadmin\modules\administration\journal;

use webadmin\modules\common\crud\CRUDService;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\intl\Intl;
use vakata\user\User;
use webadmin\modules\common\crud\CRUDModuleInterface;
use webadmin\modules\ModulesContainer;

/**
 * @extends CRUDService<\schema\LogSystemEntity>
 */
class JournalService extends CRUDService
{
    protected ModulesContainer $mc;
    protected Intl $intl;

    public function __construct(
        JournalModule $module,
        ModulesContainer $mc,
        Intl $intl,
        DBInterface $db,
        User $user
    ) {
        $this->mc = $mc;
        $this->intl = $intl;
        parent::__construct($module, $db, $user);
    }
    public function listQuery(): TableQueryMapped
    {
        /** @var TableQueryMapped<\schema\LogSystemEntity> */
        return parent::entities()
            ->sort('created', true)
            ->columns([ 'created', 'lvl', 'message', 'module', 'module_id', 'usr' ]);
    }
    public function create(array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function update(mixed $id, array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    /**
     * @return array<int,string>
     */
    public function users(): array
    {
        return $this->db->rows(
            "SELECT usr, name FROM users ORDER BY name",
            []
        )->toArray('usr', 'name');
    }
    public function modules(): array
    {
        $res = [];
        foreach ($this->mc as $m) {
            if ($m instanceof CRUDModuleInterface && $this->user->hasPermission($m->getName())) {
                $res[$m->getName()] = [
                    'slug' => $m->getSlug(),
                    'pk' => $this->db->definition($m->getTable())->getPrimaryKey()[0] ?? '',
                    'name' => '<i class="ui ' . $m->getColor() . ' ' . $m->getIcon() . ' icon"></i> ' .
                        $this->intl->get($m->getName() . '.title')
                ];
            }
        }
        return $res;
    }
}
