<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $contract
 * @property ?int $contract_type
 * @property int $company
 * @property ?int $company_type
 * @property ?int $city
 * @property ?string $date_application
 * @property ?int $sector
 * @property ?int $sector_activity
 * @property ?string $cert_date
 * @property ?string $cert_expire
 * @property ?string $cert_number
 * @property ?int $cert_type
 * @property ?string $contract_number
 * @property ?string $contract_date
 * @property ?string $contract_term
 * @property ?int $period_reporting
 * @property ?string $period_maintenance_start
 * @property ?string $period_maintenance_end
 * @property ?string $period_invest_start
 * @property ?string $period_invest_end
 * @property ?string $currency
 * @property ?int $period_value
 * @property ?int $invest_amount
 * @property ?int $number_persons
 * @property ?string $declaration
 * @property ?string $files
 * @property \vakata\collection\Collection<int,NomKidEntity> $nom_kid via contract_kid
 * @property \vakata\collection\Collection<int,UsersEntity> $users via contract_users
 * @property ?CitiesEntity $cities
 * @property CompaniesEntity $companies
 * @property ?NomCertificateTypesEntity $nom_certificate_types
 * @property ?NomCompanyTypesEntity $nom_company_types
 * @property ?NomContractTypesEntity $nom_contract_types
 * @property ?NomReportingPeriodsEntity $nom_reporting_periods
 * @property ?NomSectorActivitiesEntity $nom_sector_activities
 * @property ?NomSectorsEntity $nom_sectors
 * @property \vakata\collection\Collection<int,ContractBanksEntity> $contract_banks
 * @property \vakata\collection\Collection<int,ContractOrdersEntity> $contract_orders
 * @property \vakata\collection\Collection<int,MaintenanceReportsEntity> $maintenance_reports
 * @property \vakata\collection\Collection<int,PaymentsEntity> $payments
 * @property \vakata\collection\Collection<int,ReportsEntity> $reports
 */
class ContractsEntity extends Entity
{
}
