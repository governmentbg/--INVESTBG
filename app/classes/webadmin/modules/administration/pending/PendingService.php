<?php

declare(strict_types=1);

namespace webadmin\modules\administration\pending;

use schema\UserPendingEntity;
use webadmin\modules\common\crud\CRUDService;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\user\User;
use vakata\user\UserManagementInterface;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDModuleInterface;
use webadmin\modules\ModulesContainer;

/**
 * @extends CRUDService<\schema\UserPendingEntity>
 */
class PendingService extends CRUDService
{
    protected UserManagementInterface $usrm;
    protected ModulesContainer $mc;
    protected string $defaultGroup;

    public function __construct(
        PendingModule $module,
        ModulesContainer $mc,
        UserManagementInterface $usrm,
        Config $config,
        DBInterface $db,
        User $user
    ) {
        parent::__construct($module, $db, $user);
        $this->usrm = $usrm;
        $this->mc = $mc;
        $this->defaultGroup = $config->get('GROUP_USERS');
    }
    protected function entities(): TableQueryMapped
    {
        return $this->db->entities(\schema\UserPendingEntity::class)
            ->sort('created', true)
            ->where(
                'NOT EXISTS
                    (SELECT 1 FROM user_providers WHERE provider = user_pending.provider AND id = user_pending.id)'
            );
    }
    public function listQuery(): TableQueryMapped
    {
        return parent::listQuery()->sort('created', true);
    }
    public function create(array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    public function update(mixed $id, array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function isUserAdmin(): bool
    {
        try {
            $this->mc->byName('users');
            return $this->user->hasPermission('users');
        } catch (\Throwable $e) {
            return false;
        }
    }
    public function newUser(UserPendingEntity $pending): string
    {
        if (!$this->isUserAdmin()) {
            throw new CRUDException('Not enough permissions');
        }
        try {
            $module = $this->mc->byName('users');
            if (!($module instanceof CRUDModuleInterface)) {
                throw new \RuntimeException();
            }
            $slug = $module->getSlug();
        } catch (\Throwable $e) {
            throw new CRUDException('No user module');
        }
        if (
            $this->db->val(
                "SELECT 1 FROM user_providers WHERE provider = ? AND id = ?",
                [ $pending->provider, $pending->id ]
            )
        ) {
            throw new CRUDException('User already exists');
        }
        $user = new \vakata\user\User(
            '',
            [
                'name' => $pending->name,
                'mail' => $pending->mail
            ]
        );
        $user->addGroup($this->usrm->getGroup($this->defaultGroup));
        $user->addProvider(new \vakata\user\Provider($pending->provider, $pending->id));
        $this->usrm->saveUser($user);

        return $slug . '/update/' . (int)$user->getID();
    }
    public function existingUser(int $userID, UserPendingEntity $pending): string
    {
        if (!$this->isUserAdmin()) {
            throw new CRUDException('Not enough permissions');
        }
        try {
            $module = $this->mc->byName('users');
            if (!($module instanceof CRUDModuleInterface)) {
                throw new \RuntimeException();
            }
            $slug = $module->getSlug();
        } catch (\Throwable $e) {
            throw new CRUDException('No user module');
        }
        if (
            $this->db->val(
                "SELECT 1 FROM user_providers WHERE provider = ? AND id = ?",
                [ $pending->provider, $pending->id ]
            )
        ) {
            throw new CRUDException('User already exists');
        }
        try {
            $module->getService()->read($userID);
        } catch (\Throwable $e) {
            throw new CRUDException('No user');
        }
        $user = $this->usrm->getUser((string)$userID);
        $user->addProvider(new \vakata\user\Provider($pending->provider, $pending->id));
        $this->usrm->saveUser($user);
        return $slug . '/update/' . (int)$user->getID();
    }
}
