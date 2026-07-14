<?php

declare(strict_types=1);

namespace schema;

use vakata\collection\Collection;
use vakata\database\schema\Entity;

/**
 * @property int $mr
 * @property int $contract
 * @property ?string $date_from
 * @property ?string $date_to
 * @property ?int $status
 * @property ?string $report_comment
 * @property ?string $correction_date
 * @property ?int $correction_attempt
 * @property ?int $dme_prev_year
 * @property ?int $sme_prev_year
 * @property ?int $report_nra_prev_year
 * @property ?int $document_annual_reporting_statistics
 * @property ?string $other
 * @property ?string $last_sync
 * @property ?int $pdf_sign
 * @property ContractsEntity $contracts
 * @property ?UploadsEntity $document_annual_reporting_statistics_uploads
 * @property ?UploadsEntity $dme_prev_year_uploads
 * @property ?UploadsEntity $pdf_sign_uploads
 * @property ?UploadsEntity $sme_prev_year_uploads
 * @property \vakata\collection\Collection<int,MaintenanceReportsEmployeesEntity> $maintenance_reports_employees
 */
class MaintenanceReportsEntity extends Entity
{
    /**
     * @psalm-suppress all
     * @return array<int>
     */
    public function employees(): array
    {
        return Collection::from($this->relatedQuery('maintenance_reports_employees'))
            ->toArray('workplace_empl', 'comment');
    }
}
