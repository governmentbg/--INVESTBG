<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $mail
 * @property string $recipient
 * @property string $subject
 * @property ?string $content
 * @property ?int $priority
 * @property string $added
 * @property ?string $started
 * @property ?string $finished
 * @property ?string $result
 */
class MailsEntity extends Entity
{
}
