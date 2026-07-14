<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $payment
 * @property int $contract
 * @property string $payment_date
 * @property int $amount
 * @property ContractsEntity $contracts
 */
class PaymentsEntity extends Entity
{
}
