<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $notification
 * @property int $recipient
 * @property ?string $opened
 * @property ?string $mailed
 * @property UsersEntity $users
 * @property NotificationsEntity $notifications
 */
class NotificationRecipientsEntity extends Entity
{
}
