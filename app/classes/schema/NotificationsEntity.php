<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;

/**
 * @property int $notification
 * @property ?int $thread
 * @property ?int $sender
 * @property string $title
 * @property string $body
 * @property string $link
 * @property string $sent
 * @property int $reply
 * @property ?UsersEntity $users
 * @property ?NotificationsEntity $thread_notifications
 * @property \vakata\collection\Collection<int,NotificationFilesEntity> $notification_files
 * @property \vakata\collection\Collection<int,NotificationsEntity> $notifications_thread
 * @property \vakata\collection\Collection<int,NotificationRecipientsEntity> $notification_recipients
 */
class NotificationsEntity extends Entity
{
    /**
     * @psalm-suppress all
     * @return array<UploadsEntity>
     */
    public function files(): array
    {
        $files = [];
        foreach ($this->relatedQuery('notification_files')->sort('pos') as $v) {
            $files[] = $v->uploads()?->file();
        }
        return array_filter($files);
    }
}
