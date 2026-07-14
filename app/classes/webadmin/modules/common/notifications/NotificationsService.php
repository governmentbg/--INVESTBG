<?php

declare(strict_types=1);

namespace webadmin\modules\common\notifications;

use webadmin\modules\common\crud\CRUDService;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\user\User;

/**
 * @extends CRUDService<\schema\NotificationsEntity>
 */
class NotificationsService extends CRUDService
{
    protected function entities(): TableQueryMapped
    {
        return $this->db->entities(\schema\NotificationsEntity::class)
            ->sort('sent', true)
            ->with('notification_recipients')
            ->limitOnMainTable(true)
            ->with('users')
            ->filter('notification_recipients.recipient', (int)$this->user->getID());
    }

    public function push(mixed $user, string $title, string $body = '', string $link = '', bool $reply = false): Entity
    {
        $entity = parent::create([
            'thread' => null,
            'sender' => null,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'files' => '',
            'sent' => date('Y-m-d H:i:s'),
            'reply' => $reply ? 1 : 0
        ]);
        if (!is_array($user)) {
            $user = [ $user ];
        }
        foreach ($user as $u) {
            if ($u instanceof User) {
                $u = $u->getID();
            }
            if ((int)$u) {
                $this->db->table('notification_recipients')->insert([
                    'notification' => $entity->notification,
                    'recipient' => $u
                ]);
            }
        }
        return $entity;
    }

    public function getAvailableRecipients(): array
    {
        if ($this->user->hasPermission('users/master')) {
            return $this->db->rows("SELECT usr, name FROM users ORDER BY name")->toArray('usr', 'name');
        }
        return $this->user->organization && count($this->user->organization) ?
            $this->db->rows(
                "SELECT u.usr, u.name
                FROM users u, user_organizations uo
                WHERE u.usr = uo.usr AND uo.org IN (??)
                ORDER BY u.name",
                [array_keys($this->user->organization)]
            )
            ->toArray('usr', 'name') :
            [];
    }

    public function create(array $data = []): Entity
    {
        $data['link'] = '';
        $data['sent'] = date('Y-m-d H:i:s');
        $data['sender'] = (int)$this->user->getID();
        $recpt = $this->getAvailableRecipients();
        // allow posting to thread only if in conversation
        if (
            !(int)$data['thread'] ||
            !$this->db->val(
                "SELECT 1 FROM notifications WHERE notification = ? AND reply = 1",
                [$data['thread']]
            ) ||
            !$this->db->val(
                "SELECT 1 FROM notification_recipients
                    WHERE notification = ? AND recipient = ?",
                [ (int)$data['thread'], (int)$this->user->getID() ]
            )
        ) {
            $data['thread'] = null;
        } else {
            $thread = $this->db->row("SELECT * FROM notifications WHERE notification = ?", [$data['thread']]);
            $data['reply'] = $thread ? $thread['reply'] : 0;
            $data['title'] = 'RE: ' . ($thread ? $thread['title'] : '');
            $data['recipients'] = $this->db->col(
                "SELECT recipient FROM notification_recipients WHERE notification = ?",
                [$data['thread']]
            );
        }
        $entity = parent::create($data);
        $files = array_filter(explode(',', $data['files'] ?? ''));
        $pos = 0;
        foreach ($files as $file) {
            $this->db->table('notification_files')
                ->insert([ 'notification' => $entity->notification, 'upload' => $file, 'pos' => ++$pos ]);
        }
        foreach (($data['recipients'] ?? []) as $recipient) {
            if (
                (int)$recipient !== (int)$this->user->getID() &&
                ($data['thread'] !== null || isset($recpt[(int)$recipient]))
            ) {
                $this->db->table('notification_recipients')->insert([
                    'notification' => (int)$entity->notification,
                    'recipient' => (int)$recipient
                ]);
            }
        }
        $this->db->table('notification_recipients')->insert([
            'notification' => (int)$entity->notification,
            'recipient' => (int)$this->user->getID(),
            'opened' => date('Y-m-d H:i:s')
        ]);
        return $entity;
    }
    protected function notification(int $id): ?object
    {
        $temp = $this->db->table('notifications')
                    ->with('notification_recipients')
                    ->with('users')
                    ->filter('notification_recipients.recipient', (int)$this->user->getID())
                    ->filter('notification', $id)[0] ?? null;
        if (!$temp) {
            return null;
        }
        $temp = (object)$temp;
        $temp->users = $temp->users ? (object)$temp->users : null;
        $temp->files = implode(
            ',',
            $this->db->col("SELECT upload FROM notification_files WHERE notification = ? ORDER BY pos", [$id])
        );
        return $temp;
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $arr = parent::toArray($entity, $relations);
        $arr['parents'] = [];
        if ($entity->thread) {
            $arr['parents'][] = $this->entities()->find((int)$entity->thread);
            foreach (
                $this->db->col(
                    'SELECT notification FROM notifications WHERE thread = ? ORDER BY sent',
                    [ $entity->thread ]
                ) as $id
            ) {
                $arr['parents'][] = $this->entities()->find((int)$id);
            }
        } else {
            $arr['parents'][] = $this->entities()->find((int)$entity->notification);
            foreach (
                $this->db->col(
                    'SELECT notification FROM notifications WHERE thread = ? ORDER BY sent',
                    [ $entity->notification ]
                ) as $id
            ) {
                $arr['parents'][] = $this->entities()->find((int)$id);
            }
        }
        return $arr;
    }
    public function read(mixed $id): Entity
    {
        /** @var \schema\NotificationsEntity $entity */
        $entity = parent::read($id);
        if (!$entity->notification_recipients[0]?->opened) {
            $this->db->table('notification_recipients')
                ->filter('notification', (int)$entity->notification)
                ->filter('recipient', (int)$this->user->getID())
                ->update([ 'opened' => date('Y-m-d H:i:s') ]);
        }
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    public function getUser(): int
    {
        return (int)$this->user->getID();
    }
    public function getNotifications(int $limit = 5): array
    {
        $unread = $this->db->table('notifications')
            ->filter('notification_recipients.recipient', (int)$this->user->getID())
            ->filter('notification_recipients.opened', null)
            ->sort('sent', true)
            ->paginate(1, $limit)
            ->select(['notification', 'title', 'link', 'sent', 'unread' => 1]);
        $read = $this->db->table('notifications')
            ->filter('notification_recipients.recipient', (int)$this->user->getID())
            ->filter('notification_recipients.opened', null, true)
            ->sort('sent', true)
            ->paginate(1, $limit)
            ->select(['notification', 'title', 'link', 'sent', 'unread' => 0]);
        return array_slice(array_merge($unread, $read), 0, $limit);
    }
}
