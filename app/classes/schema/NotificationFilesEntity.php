<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $notification
 * @property int $upload
 * @property int $pos
 * @property UploadsEntity $uploads
 * @property NotificationsEntity $notifications
 */
class NotificationFilesEntity extends Entity
{
}
