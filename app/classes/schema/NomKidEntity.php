<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $kid
 * @property ?string $code
 * @property ?string $name
 * @property \vakata\collection\Collection<int,ContractsEntity> $contracts via contract_kid
 */
class NomKidEntity extends Entity
{
}
