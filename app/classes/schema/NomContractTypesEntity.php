<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $contract_type
 * @property string $name
 * @property int $pos
 * @property \vakata\collection\Collection<int,ContractsEntity> $contracts
 */
class NomContractTypesEntity extends Entity
{
}
