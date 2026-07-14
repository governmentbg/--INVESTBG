<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;
use vakata\files\File;
use vakata\files\FileStorageInterface;

// phpcs:disable Generic.Files.LineLength.TooLong

/**
 * @property int $id
 * @property string $name
 * @property string $location
 * @property int $bytesize
 * @property string $uploaded
 * @property string $hash
 * @property ?string $data
 * @property ?string $settings
 * @property \vakata\collection\Collection<int,CollectionsEntity> $collections via upload_collections
 * @property \vakata\collection\Collection<int,UsersEntity> $users via upload_user
 * @property \vakata\collection\Collection<int,UsersEntity> $users_avatar
 * @property \vakata\collection\Collection<int,UploadsVersionsEntity> $uploads_versions
 * @property \vakata\collection\Collection<int,MaintenanceReportsEntity> $maintenance_reports_document_annual_reporting_statistics
 * @property \vakata\collection\Collection<int,MaintenanceReportsEntity> $maintenance_reports_dme_prev_year
 * @property \vakata\collection\Collection<int,MaintenanceReportsEntity> $maintenance_reports_pdf_sign
 * @property \vakata\collection\Collection<int,MaintenanceReportsEntity> $maintenance_reports_sme_prev_year
 * @property \vakata\collection\Collection<int,ReportDocumentsEntity> $report_documents_payment_request
 * @property \vakata\collection\Collection<int,ReportDocumentsEntity> $report_documents_technical_report
 * @property \vakata\collection\Collection<int,ReportDocumentsEntity> $report_documents_financial_report
 * @property \vakata\collection\Collection<int,ReportDocumentsEntity> $report_documents_employment_contracts_report
 * @property \vakata\collection\Collection<int,ReportDocumentsEntity> $report_documents_other_public_funding_report
 * @property \vakata\collection\Collection<int,ReportDocumentsEntity> $report_documents_expenses_eligibility_declaration
 * @property \vakata\collection\Collection<int,ReportDocumentsEntity> $report_documents_auditor_report
 * @property \vakata\collection\Collection<int,ReportDocumentsEntity> $report_documents_state_aid_declaration
 * @property \vakata\collection\Collection<int,ReportDocumentsEntity> $report_documents_statistics_documents
 * @property \vakata\collection\Collection<int,ReportsImportsEntity> $reports_imports
 * @property \vakata\collection\Collection<int,ReportsEntity> $reports_mir_checklist
 * @property \vakata\collection\Collection<int,ReportsEntity> $reports_mir_doc
 * @property \vakata\collection\Collection<int,ReportsEntity> $reports_pdf_sign
 * @property \vakata\collection\Collection<int,SampleDocumentsEntity> $sample_documents
 */
class UploadsEntity extends Entity
{
    protected FileStorageInterface $files;
    /**
     * @param FileStorageInterface $files
     * @param array<string,mixed> $data
     * @param array<string,callable> $lazy
     * @param array<string,callable> $relations
     */
    public function __construct(FileStorageInterface $files, array $data = [], array $lazy = [], array $relations = [])
    {
        $this->files = $files;
        parent::__construct($data, $lazy, $relations);
    }
    public function file(): File
    {
        return $this->files->get((string)$this->id);
    }
}
