<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $report
 * @property ?int $contract_id
 * @property ?string $date_from
 * @property ?string $date_to
 * @property ?int $status
 * @property ?int $locked
 * @property ?int $workplaces
 * @property ?int $report_number
 * @property ?string $general_comment
 * @property ?int $correction_numb
 * @property ?string $correction_end_date
 * @property int $percent_second
 * @property int $percent_third
 * @property ?int $mir_doc
 * @property ?int $mir_checklist
 * @property ?int $pdf_sign
 * @property ?ContractsEntity $contracts
 * @property ?UploadsEntity $mir_checklist_uploads
 * @property ?UploadsEntity $mir_doc_uploads
 * @property ?UploadsEntity $pdf_sign_uploads
 * @property \vakata\collection\Collection<int,ReportEmployeeCommentsEntity> $report_employee_comments
 * @property \vakata\collection\Collection<int,ReportDocumentsEntity> $report_documents
 * @property \vakata\collection\Collection<int,ReportsImportsEntity> $reports_imports
 * @property \vakata\collection\Collection<int,WorkplacesEntity> $workplaces_report_id
 */
class ReportsEntity extends Entity
{
}
