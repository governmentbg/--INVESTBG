<?php

declare(strict_types=1);

namespace webadmin\modules\administration\maildb;

use vakata\config\Config;
use webadmin\modules\common\crud\CRUDService;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\user\User;

/**
 * @extends CRUDService<\schema\MailsEntity>
 */
class MailDBService extends CRUDService
{
    protected bool $queue = false;

    public function __construct(MailDBModule $module, Config $config, DBInterface $db, User $user)
    {
        parent::__construct($module, $db, $user);
        $this->queue = $config->getBool('MAILQUEUE');
    }
    public function hasQueue(): bool
    {
        return $this->queue;
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
}
