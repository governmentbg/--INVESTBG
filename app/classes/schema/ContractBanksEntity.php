<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $contact_bank
 * @property int $contract
 * @property ?string $bank_date_from
 * @property ?string $bank_date_to
 * @property ?int $bank_amount
 * @property ?string $bank_name
 * @property ContractsEntity $contracts
 */
class ContractBanksEntity extends Entity
{
}
