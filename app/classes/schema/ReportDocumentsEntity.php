<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $id
 * @property int $report_id
 * @property ?int $payment_request
 * @property ?int $technical_report
 * @property ?int $financial_report
 * @property ?int $employment_contracts_report
 * @property ?int $other_public_funding_report
 * @property ?int $expenses_eligibility_declaration
 * @property ?int $auditor_report
 * @property ?int $state_aid_declaration
 * @property ?int $statistics_documents
 * @property ?string $other_documents
 * @property ?string $created_at
 * @property ?string $updated_at
 * @property ReportsEntity $reports
 * @property ?UploadsEntity $payment_request_uploads
 * @property ?UploadsEntity $technical_report_uploads
 * @property ?UploadsEntity $financial_report_uploads
 * @property ?UploadsEntity $employment_contracts_report_uploads
 * @property ?UploadsEntity $other_public_funding_report_uploads
 * @property ?UploadsEntity $expenses_eligibility_declaration_uploads
 * @property ?UploadsEntity $auditor_report_uploads
 * @property ?UploadsEntity $state_aid_declaration_uploads
 * @property ?UploadsEntity $statistics_documents_uploads
 */
class ReportDocumentsEntity extends Entity
{
}
