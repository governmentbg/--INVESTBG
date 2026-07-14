<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $company
 * @property string $id
 * @property int $id_type
 * @property ?string $company_name
 * @property ?string $company_address
 * @property ?string $company_email
 * @property ?int $company_region
 * @property ?int $company_municipality
 * @property ?int $company_city
 * @property ?string $created
 * @property ?CitiesEntity $cities
 * @property ?MunicipalitiesEntity $municipalities
 * @property ?RegionsEntity $regions
 * @property \vakata\collection\Collection<int,CompanyEgnsEntity> $company_egns
 * @property \vakata\collection\Collection<int,ContractsEntity> $contracts
 */
class CompaniesEntity extends Entity
{
}
