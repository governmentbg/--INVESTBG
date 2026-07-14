<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $contact_order
 * @property int $contract
 * @property ?string $order_date
 * @property ?string $order_date_return
 * @property ?int $order_amount
 * @property ContractsEntity $contracts
 */
class ContractOrdersEntity extends Entity
{
}
