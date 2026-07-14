<?php

declare(strict_types=1);

namespace zni\modules\reports;

use DateTime;
use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use schema\ReportsEntity;
use schema\WorkplacesEntity;
use schema\WorkplaceEmplsEntity;
use stdClass;
use vakata\collection\Collection;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\intl\Intl;
use vakata\mail\driver\SenderInterface;
use vakata\mail\Mail;
use vakata\mail\MailException;
use vakata\user\User;
use vakata\validation\Validator;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDServiceInterface;
use webadmin\modules\common\crud\CRUDServiceVersioned;
use webadmin\modules\common\ekatte\EkatteService;
use zni\enums\ReportStatus;
use zni\modules\nomenc\nomenc\NomencService;
use zni\permission\PermissionService;
use zni\utility\Helper;

/**
 * @extends CRUDServiceVersioned<ReportsEntity>
 * @implements CRUDServiceInterface<ReportsEntity>
 */
class ReportsService extends CRUDServiceVersioned implements CRUDServiceInterface
/**
 * @param CRUDModuleInterface<ReportsEntity, CRUDServiceInterface<ReportsEntity>> $module
 */
{
    public function __construct(
        ReportsModule $module,
        DBInterface $db,
        User $user,
        protected Intl $intl,
        protected Config $config,
        protected SenderInterface $sender,
        protected EkatteService $ekatteService,
        protected NomencService $nomencService,
        protected PermissionService $permissionService,
    ) {
        parent::__construct($module, $db, $user);
    }
    public function isMIR(): bool
    {
        return $this->permissionService->isMIR();
    }
    public function isINV(): bool
    {
        return $this->permissionService->isINV();
    }
    public function entities(): TableQueryMapped
    {
        $entities = parent::entities();
        foreach ($this->user->getGroups() as $group) {
            if ((int)$group->getId() === $this->config->getInt('RESPONSIBLE_MIR')) {
                return $entities;
            }
        }

        $isMIR = $this->isMIR();
        $isINV = $this->isINV();

        // if (!$isMIR && !$isINV) {
        //     return $entities->filter('contracts.users.usr', null);
        // }

        if ($isMIR) {
            return $entities->filter('contracts.users.usr', $this->user->getID());
        } elseif ($isINV) {
            $egn = $this->user->getData()['egn'];

            if ($egn) {
                $entities
                    ->filter('contracts.companies.company_egns.egn', $egn);
            } else {
                return $entities->filter('contracts.companies.company_egns.egn', null);
            }
        }

        return  $entities;
    }

    public function create(array $data = []): Entity
    {
        if ((int)$data['workplaces'] <= 0) {
            throw new CRUDException('Трябва да има поне едно работно място !');
        }

        $data['report_number'] = 1;
        $data['status'] = 0;
        $data['locked'] = 0;
        $data['contract_id'] = $data['hiddenContract'];
        $data['mir_doc'] = null;
        $data['mir_checklist'] = null;
        $data['pdf_sign'] = null;

        $entity = parent::create($data);

        $this->db->query(
            "INSERT INTO workplaces (report_id, workplace_no)
        SELECT :report_id, gs
        FROM generate_series(1, :cnt::int) AS gs",
            [
                'report_id' => $entity->report,
                'cnt'       => $data['workplaces']
            ]
        );

        return $entity;
    }


    public function update(mixed $id, array $data = []): Entity
    {
        $temp = parent::read($id);
        $reportId = $temp->report;
        $oldWorkplacesNumb = (int)$temp->workplaces;

        $this->db->begin();

        try {
            $oldStatus = $temp->status;
            $newStatus = $data['status'] ?? null;

            if ($oldStatus === ReportStatus::Submitted->value) {
                $allowedStatuses = [
                    ReportStatus::Approved->value,
                    ReportStatus::Rejected->value,
                    ReportStatus::ReturnedForCorrection->value
                ];

                if (!in_array($newStatus, $allowedStatuses, true)) {
                    throw new CRUDException('Не може да смените статуса');
                }
            } else {
                if ((int)$temp->locked === 1) {
                    throw new CRUDException('Не може да променяте заключен отчет!');
                }
            }

            $data['date_from'] = !isset($data['date_from']) ? $temp->date_from : $data['date_from'];
            $data['date_to'] = !isset($data['date_to']) ? $temp->date_to : $data['date_to'];
            $data['general_comment'] = !isset($data['general_comment'])
                ? $temp->general_comment
                : $data['general_comment'];
            $data['correction_numb'] = $temp->correction_numb;
            if (
                $oldStatus === ReportStatus::ReturnedForCorrection->value
                && $temp->status === ReportStatus::ReturnedForCorrection->value
            ) {
                $data['correction_end_date'] = $temp->correction_end_date;
            } else {
                $data['correction_end_date'] = null;
            }

            if (isset($data['status']) && ($data['status'] === ReportStatus::ReturnedForCorrection->value)) {
                $data['correction_numb'] = (int)$temp->correction_numb + 1;

                $hasMasterMir = false;

                foreach ($this->user->getGroups() as $group) {
                    if ((int)$group->getId() === $this->config->getInt('MASTER_MIR')) {
                        $hasMasterMir = true;
                        break;
                    }
                }

                if (!$hasMasterMir) {
                    $maxAttempts = $this->config->getInt('CORRECTION_ATTEMPT', 2);
                    if ($data['correction_numb'] > $maxAttempts) {
                        throw new CRUDException('Максималният брой опити за корекция е достигнат');
                    }
                }

                $correctionEnd = $temp->correction_end_date
                    ? new \DateTime($temp->correction_end_date)
                    : (new \DateTime())->modify('+1 day');


                $daysToAdd = $this->config->getInt('EMPL_REPORT_CORRECTION_DAYS', 14);
                $correctionEnd->modify("+{$daysToAdd} days");


                $maxDays = $this->config->getInt('CORRECTION_DAYS', 30);
                $maxDate = (new \DateTime($temp->date_from ?? 'now'))->modify("+{$maxDays} days");

                if ($correctionEnd > $maxDate) {
                    $correctionEnd = $maxDate;
                }

                $data['correction_end_date'] = $correctionEnd->format('Y-m-d');

                $data['locked'] = 0;
            }

            $existing = $this->db->row(
                'SELECT report_id FROM report_documents WHERE report_id = ?',
                [$reportId]
            );

            $merge = function (string $key) use ($data, $existing): mixed {
                if (isset($data[$key])) {
                    $val = $data[$key];

                    if (is_array($val)) {
                        $val = array_filter($val);
                        $val = implode(',', $val);
                    }

                    if (!empty($val)) {
                        return $val;
                    }
                }

                return $existing[$key] ?? null;
            };

            $docData = [
                'report_id' => $reportId,
                'payment_request'                  => $merge('payment_request'),
                'technical_report'                 => $merge('technical_report'),
                'financial_report'                 => $merge('financial_report'),
                'employment_contracts_report'      => $merge('employment_contracts_report'),
                'other_public_funding_report'      => $merge('other_public_funding_report'),
                'expenses_eligibility_declaration' => $merge('expenses_eligibility_declaration'),
                'auditor_report'                   => $merge('auditor_report'),
                'state_aid_declaration'            => $merge('state_aid_declaration'),
                'statistics_documents'             => $merge('statistics_documents'),
                'other_documents'                  => $merge('other_documents'),
            ];

            if ($existing) {
                unset($docData['report_id']);

                $this->db
                    ->table('report_documents')
                    ->where('report_id = ?', [$reportId])
                    ->update($docData);
            } else {
                $this->db
                    ->table('report_documents')
                    ->insert($docData);
            }
            $entity = parent::update($id, $data);

            if ($oldWorkplacesNumb > (int)($data['workplaces'] ?? 0)) {
                throw new CRUDException('Не може да намаляте работните места !');
            }

            if ($oldWorkplacesNumb < (int)($data['workplaces'] ?? 0)) {
                $newWorkplaces = (int)$data['workplaces'] -  $oldWorkplacesNumb;

                $maxNo = (int)$this->db->one(
                    "SELECT COALESCE(MAX(workplace_no), 0)
             FROM workplaces
             WHERE report_id = ?",
                    [$reportId]
                );

                $from = $maxNo + 1;
                $to   = $maxNo + $newWorkplaces;

                $this->db->query(
                    "INSERT INTO workplaces (report_id, workplace_no)
            SELECT :report_id, gs
            FROM generate_series(:old::int, :cnt::int) AS gs",
                    [
                        'report_id' => $reportId,
                        'old'       => $from,
                        'cnt'       => $to
                    ]
                );
            }

            $this->journal('Редактиране на отчет', 'info', $reportId);

            $this->db->commit();
            return $entity;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    public function createWithOld(array $data = []): Entity
    {
        $contractId = (int)($data['hiddenContract'] ?? 0);
        if ($contractId <= 0) {
            throw new \RuntimeException('Липсва договор.');
        }

        $newDateFrom = $data['date_from'] ?? $data['hidden_date_from'] ?? null;
        $newDateTo   = $data['date_to']   ?? $data['hidden_date_to']   ?? null;

        if (!$newDateFrom || !$newDateTo) {
            throw new \RuntimeException('Липсват дати за отчета.');
        }


        $df = \DateTime::createFromFormat('d.m.Y', (string)$newDateFrom)
            ?: \DateTime::createFromFormat('Y-m-d', (string)$newDateFrom);
        $dt = \DateTime::createFromFormat('d.m.Y', (string)$newDateTo)
            ?: \DateTime::createFromFormat('Y-m-d', (string)$newDateTo);

        if (!$df || !$dt) {
            throw new \RuntimeException('Невалиден формат на датите.');
        }

        $newDateFromSql = $df->format('Y-m-d');
        $newDateToSql   = $dt->format('Y-m-d');

        $this->db->begin();

        try {
            $lastReport = $this->db->row(
                "SELECT r.*
             FROM reports r
             WHERE r.contract_id = ?
             ORDER BY r.date_from DESC NULLS LAST, r.report DESC
             LIMIT 1",
                [$contractId]
            );

            if (!$lastReport) {
                throw new \RuntimeException('Няма стар отчет за този договор.');
            }

            $oldReportId = (int)$lastReport['report'];


            /** @var ReportsEntity $newReport */
            $newReport = parent::create([
                'contract_id' => $contractId,
                'date_from'   => $newDateFromSql,
                'date_to'     => $newDateToSql,
                'status'      => 0,
                'locked'      => 0,
                'workplaces'     => $data['workplaces'],
                'percent_second' => $data['percent_second'],
                'percent_third'  => $data['percent_third'],
                'report_number'  => ((int)$lastReport['report_number']) + 1,
            ]);

            $newReportId = (int)($newReport->report ?? 0);
            if ($newReportId <= 0) {
                throw new \RuntimeException('Неуспешно създаване на нов отчет.');
            }

            $cnt = $data['workplaces'] ?? 1;

            $this->db->query(
                "INSERT INTO workplaces (report_id, workplace_no)
             SELECT ?, gs
             FROM generate_series(1, ?) AS gs",
                [$newReportId, $cnt]
            );

            $this->db->query(
                "UPDATE workplaces w_new
             SET position_id = w_old.position_id
             FROM workplaces w_old
             WHERE w_old.report_id = ?
               AND w_new.report_id = ?
               AND w_old.workplace_no = w_new.workplace_no",
                [$oldReportId, $newReportId]
            );

            $this->db->query(
                "INSERT INTO workplace_empls
        (workplace_id, employee_id, start_date, end_date, refund_sum, salary_amount, project_start_date)
     SELECT
        w_new.workplace,
        we_old.employee_id,
        ?,
        NULL,
        we_old.refund_sum,
        we_old.salary_amount,
        we_old.project_start_date
     FROM workplaces w_old
     JOIN workplace_empls we_old
       ON we_old.workplace_id = w_old.workplace
      AND we_old.end_date IS NULL
     JOIN workplaces w_new
       ON w_new.report_id = ?
      AND w_new.workplace_no = w_old.workplace_no
     WHERE w_old.report_id = ?",
                [$newDateFromSql, $newReportId, $oldReportId]
            );

            $docData = [
                'report_id' => $newReportId,
                'payment_request'                  => $data['payment_request'] ?: null,
                'technical_report'                 => $data['technical_report'] ?: null,
                'financial_report'                 => $data['financial_report'] ?: null,
                'employment_contracts_report'      => $data['employment_contracts_report'] ?: null,
                'other_public_funding_report'      => $data['other_public_funding_report'] ?: null,
                'expenses_eligibility_declaration' => $data['expenses_eligibility_declaration'] ?: null,
                'auditor_report'                   => $data['auditor_report'] ?: null,
                'state_aid_declaration'            => $data['state_aid_declaration'] ?: null,
                'statistics_documents'             => $data['statistics_documents'] ?: null,
                'other_documents'                  => $data['other_documents'] ?: null,
            ];

            $this->db
                ->table('report_documents')
                ->insert($docData);

            $this->db->commit();
            return $newReport;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    public function partialUpdate(int $id, array $data): Entity
    {
        $temp = parent::read($id);

        $this->journal('Редактиране на отчет', 'info', $temp->report);

        //mir master unlock report
        if (isset($data['locked']) && $data['locked'] == 0) {
            $data['status'] = ReportStatus::ReturnedForCorrection->value;
        }

        if (isset($data['status']) && ($data['status'] === ReportStatus::ReturnedForCorrection->value)) {
            $data['correction_numb'] = (int)$temp->correction_numb + 1;

            $hasMasterMir = false;

            if ($this->user->inGroup($this->config->getString('MASTER_MIR'))) {
                $hasMasterMir = true;
            }

            if (!$hasMasterMir) {
                $maxAttempts = $this->config->getInt('CORRECTION_ATTEMPT', 2);
                if ($data['correction_numb'] > $maxAttempts) {
                    throw new CRUDException('Максималният брой опити за корекция е достигнат');
                }
            }

            $correctionEnd = $temp->correction_end_date
                ? new \DateTime($temp->correction_end_date)
                : (new \DateTime())->modify('+1 day');


            $daysToAdd = $this->config->getInt('EMPL_REPORT_CORRECTION_DAYS', 14);
            $correctionEnd->modify("+{$daysToAdd} days");


            $maxDays = $this->config->getInt('CORRECTION_DAYS', 30);
            $maxDate = (new \DateTime($temp->date_from ?? 'now'))->modify("+{$maxDays} days");

            if ($correctionEnd > $maxDate) {
                $correctionEnd = $maxDate;
            }

            $data['correction_end_date'] = $correctionEnd->format('Y-m-d');
            $data['locked'] = 0;
        }

        if (isset($data['status'])) {
            $this->sendNotification($temp, $data['status']);
        }

        return parent::update($id, $data);
    }
    public function getContract(int $contractId): array|null
    {

        $contract = $this->db->get(
            "SELECT * FROM contracts WHERE contract = ?",
            $contractId
        )->toArray();
        if (!$contract) {
            return null;
        }

        $reports = $this->db->get(
            "SELECT * from
             reports r
              where r.contract_id =  ?",
            $contractId
        )->toArray();

        return [
            'contract' => $contract,
            'reports'  => $reports
        ];
    }
    public function getContractFullInfo(int $contractId): ?array
    {
        // ===== CONTRACT =====
        $contract = $this->db->get(
            "SELECT
            contracts.*,
            c.id,
            c.company_name,
            c.company_region,
            c.company_municipality,
            c.company_city,
            c.company_address,
            c.company_email
         FROM contracts
         JOIN companies c ON c.company = contracts.company
         WHERE contract = ?",
            [$contractId]
        )->toArray();

        if (!$contract) {
            return null;
        }

        // ===== REPORTS =====
        $reports = $this->db->table('reports')
            ->with('contracts')
            ->filter('contract_id', $contractId)
            ->sort('reports.report_number', true)
            ->select([
                'reports.report',
                'reports.report_number',
                'reports.status',
                'reports.date_from',
                'reports.date_to',
                'reports.locked',
                'reports.workplaces as workplaces_count',
                'contracts.currency'
            ]);

        foreach ($reports as &$report) {
            // ===== WORKPLACES =====
            $workplaces = $this->db->get(
                "SELECT
                w.*, nn.name as nkpd_name, nn.code as nkpd_code
            FROM workplaces w
            JOIN nom_nkpd nn ON w.position_id = nn.nkpd
            WHERE w.report_id = ?
            ORDER BY w.workplace ASC",
                [$report['report']]
            )->toArray();

            foreach ($workplaces as &$workplace) {
                // ===== EMPLOYEES FOR WORKPLACE =====
                $employees = $this->db->get(
                    "SELECT
                    we.*,
                    e.*
                FROM workplace_empls we
                JOIN employees e ON e.employee = we.employee_id
                WHERE we.workplace_id = ?
                    and we.not_empl_report = ?
                ORDER BY we.workplace_empl ASC",
                    [$workplace['workplace'], 0]
                )->toArray();

                $workplace['total_salary']    = 0.0;
                $workplace['total_insurance'] = 0.0;
                $workplace['total_months'] = 0;

                foreach ($employees as &$employee) {
                    // ===== SALARY FOR EMPLOYEE IN WORKPLACE =====
                    $salary = $this->db->one(
                        "SELECT
                        COALESCE(SUM(salary), 0)    AS salary,
                        COALESCE(SUM(insurance), 0) AS insurance,
                        COALESCE( SUM(CASE WHEN insurance != 0 and salary !=0 THEN 1 ELSE NULL END), 0) as months
                     FROM employee_salary es
                     WHERE es.workplace_empl = ?",
                        [$employee['workplace_empl']]
                    );

                    $employee['salary']    = (float)($salary['salary'] ?? 0);
                    $employee['insurance'] = (float)($salary['insurance'] ?? 0);
                    $employee['months'] = ($salary['months'] ?? 0);

                    $workplace['total_salary']    += $employee['salary'];
                    $workplace['total_insurance'] += $employee['insurance'];
                    $workplace['total_months'] += $employee['months'];
                }

                $workplace['employees'] = $employees;
            }

            $report['workplaces'] = $workplaces;
        }

        return [
            'contract' => $contract[0],
            'reports'  => $reports
        ];
    }
    public function generateExcel(int $id): mixed
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="contracts.xlsx"');
        header('Cache-Control: max-age=0');

        $report = $this->db->one(
            "SELECT date_from, date_to, percent_third
            FROM reports
            WHERE report = ?",
            [$id]
        );

        $from = $report['date_from'];
        $to   = $report['date_to'];

        $months = $this->getMonthsRange($from, $to);

        $rows = $this->db->get(
            "SELECT e.employee
            , e.name AS person_name
            , e.identifirer
            , w.workplace_no
            , w.workplace
            , we.project_start_date
            , we.start_date AS person_start_date
            , we.end_date AS person_end_date
            , r.date_from AS report_start_date
            , r.date_to AS report_end_date
            , c.currency
            , CONCAT(nn.code, '-', nn.name) as nkpd
            , string_agg(DISTINCT nk.code || ' ' || nk.name, ', ') as kid
            , we.workplace_empl as person_id
            FROM employees e
            JOIN workplace_empls we ON e.employee = we.employee_id
            JOIN workplaces w ON we.workplace_id = w.workplace
            JOIN reports r ON r.report = w.report_id
            JOIN contracts c ON c.contract = r.contract_id
            JOIN nom_nkpd nn ON nn.nkpd = w.position_id
            JOIN contract_kid ck ON ck.contract = c.contract
            JOIN nom_kid nk ON nk.kid = ck.kid
            WHERE r.report = ?
                AND we.not_empl_report = ?
            GROUP BY e.employee
                , w.workplace
                , we.workplace_empl
                , r.report
                , c.contract
                , nn,nkpd
            ORDER BY w.workplace ASC
                , we.start_date ASC",
            [$id, 0]
        );

        $salaries = $this->db->table('employee_salary')
            ->filter('workplace_empls.workplaces.report_id', $id)
            ->filter('workplace_empls.not_empl_report', 0)
            ->select();

        $salaryMap = [];

        foreach ($salaries as $s) {
            $monthKey = sprintf('%02d.%04d', $s['month'], $s['year']);

            $salaryMap[$s['workplace_empl']][$monthKey] = [
                'salary'    => $s['salary'],
                'insurance' => $s['insurance'],
                'percent'   => $s['percent'],
            ];
        }

        $this->generateExcelFile($rows->toArray(), $months, $salaryMap, (float) $report['percent_third']);
        exit;
    }
    public function generateExcelFile(array $rows, array $months, array $salaryMap, float $percentThird): mixed
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');

        // ==== REPORT PERIOD ====
        $reportFrom = $rows[0]['report_start_date'];
        $reportTo   = $rows[0]['report_end_date'];

        // $periodText = 'ОТЧЕТ ОТ ' .
        //     date('d.m.Y', strtotime($reportFrom)) .
        //     ' ДО ' . date('d.m.Y', strtotime($reportTo));

        // $sheet->setCellValue('A1', $periodText);
        // $sheet->mergeCells('A1:Z1');
        // $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        // $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ==== HEADER ROWS ====
        $headerRow1 = 1;
        $headerRow2 = 2;
        $dataRow    = 3;

        $egnList = [];

        // ==== STATIC HEADERS ====
        $staticHeaders = [
            '№',
            'Системно ID',
            'НКПД',
            'КИД',
            'Работно място',
            'Идентификатор',
            'Имена',
            'Статус',
            'Начална дата',
            'Крайна дата',
            'Валута'
        ];

        $col = 1;
        foreach ($staticHeaders as $header) {
            $cell = Coordinate::stringFromColumnIndex($col) . $headerRow1;
            $sheet->setCellValue($cell, $header);
            $sheet->mergeCells(
                Coordinate::stringFromColumnIndex($col) . $headerRow1 . ':' .
                    Coordinate::stringFromColumnIndex($col) . $headerRow2
            );
            $sheet->getStyle($cell)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $col++;
        }

        // ==== MONTH HEADERS ====
        foreach ($months as $month) {
            $startCol = $col;

            $sheet->setCellValue(
                Coordinate::stringFromColumnIndex($startCol) . $headerRow1,
                $month
            );
            $sheet->mergeCells(
                Coordinate::stringFromColumnIndex($startCol) . $headerRow1 . ':' .
                    Coordinate::stringFromColumnIndex($startCol + 2) . $headerRow1
            );

            $sheet->setCellValue(Coordinate::stringFromColumnIndex($startCol) . $headerRow2, 'Заплата');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($startCol + 1) . $headerRow2, 'Осигуровка');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($startCol + 2) . $headerRow2, 'Процент');

            for ($i = 0; $i < 3; $i++) {
                $cell = Coordinate::stringFromColumnIndex($startCol + $i) . $headerRow2;

                $style = $sheet->getStyle($cell);

                $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }

            $col += 3;
        }

        // ==== SUM COLUMNS ====
        $sumSalaryCol    = $col;
        $sumInsuranceCol = $col + 1;
        $sumMonthsCol = $sumInsuranceCol + 1;

        $sheet->setCellValue(Coordinate::stringFromColumnIndex($sumSalaryCol) . $headerRow1, 'Общо Заплата');
        $sheet->mergeCells(
            Coordinate::stringFromColumnIndex($sumSalaryCol) . $headerRow1 . ':' .
                Coordinate::stringFromColumnIndex($sumSalaryCol) . $headerRow2
        );

        $sheet->setCellValue(Coordinate::stringFromColumnIndex($sumInsuranceCol) . $headerRow1, 'Общо Осигуровки');
        $sheet->mergeCells(
            Coordinate::stringFromColumnIndex($sumInsuranceCol) . $headerRow1 . ':' .
                Coordinate::stringFromColumnIndex($sumInsuranceCol) . $headerRow2
        );

        $sheet->setCellValue(Coordinate::stringFromColumnIndex($sumMonthsCol) . $headerRow1, 'Общо месеци');
        $sheet->mergeCells(
            Coordinate::stringFromColumnIndex($sumMonthsCol) . $headerRow1 . ':' .
                Coordinate::stringFromColumnIndex($sumMonthsCol) . $headerRow2
        );

        // ==== DATA ====
        $rowNum = $dataRow;
        $counter = 1;
        $totalSalaryCols = [];
        $totalInsuranceCols = [];
        $totalMontsCols = [];

        foreach ($rows as $row) {
            $col = 1;

            $egnList[] = $row['identifirer'] ?? 'X';
            $hasPerson = !empty($row['person_id']);

            if (!$hasPerson) {
                $statusText  = 'Няма служител';
                $statusColor = 'E0E0E0';
            } else {
                $isActive = empty($row['person_end_date']) ||
                    $row['person_end_date'] >= $reportTo;

                if ($isActive) {
                    $statusText  = 'АКТИВЕН';
                    $statusColor = 'C6EFCE';
                } else {
                    $statusText  = 'НЕАКТИВЕН';
                    $statusColor = 'FFC7CE';
                }
            }

            $staticValues = [
                $counter++,
                $row['person_id'],
                $row['nkpd'],
                $row['kid'],
                "Работно място " . $row['workplace_no'],
                $row['identifirer'] ?? "X",
                $row['person_name'],
                $statusText,
                !empty($row['person_start_date'])
                    ? date('d.m.Y', strtotime($row['person_start_date']) ?: 0)
                    : '',
                !empty($row['person_end_date'])
                    ? date('d.m.Y', strtotime($row['person_end_date']) ?: 0)
                    : '',
                $this->intl->get('currency.' . $row['currency'])
            ];

            foreach ($staticValues as $idx => $val) {
                $cell = Coordinate::stringFromColumnIndex($col) . $rowNum;
                $sheet->setCellValue($cell, $val);
                $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($cell)->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

                if ($idx === 7) {
                    $sheet->getStyle($cell)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($statusColor);
                }
                $col++;
            }

            $monthColsStart = $col;
            $monthSum = 0;

            foreach ($months as $m) {
                $monthDate  = DateTime::createFromFormat('m.Y', $m);
                $monthKeyDate = DateTime::createFromFormat('m.Y', $m);
                // @phpstan-ignore-next-line
                $monthYm = $monthKeyDate->format('Y-m');

                if (!empty($row['project_start_date'])) {
                    $start = new \DateTime($row['project_start_date']);
                    if ($start->format('j') > 1) {
                        $start->modify('first day of next month');
                    }
                    $startYm = $start->format('Y-m');
                } else {
                    $startYm = null;
                }

                $endYm = !empty($row['person_end_date'])
                    ? date('Y-m', strtotime($row['person_end_date']) ?: 0)
                    : null;

                $beforeStart = $startYm !== null && $monthYm < $startYm;
                $afterEnd = $endYm !== null && $monthYm > $endYm;

                $locked = $beforeStart || $afterEnd;

                $personId = (int)($row['person_id'] ?? 0);
                $hasPerson = $personId > 0;
                $monthKey = $m;

                if ($hasPerson && !$locked) {
                    $monthSum++;
                }

                foreach (['salary', 'insurance', 'percent'] as $field) {
                    $cell = Coordinate::stringFromColumnIndex($col) . $rowNum;
                    $style = $sheet->getStyle($cell);

                    if (!$hasPerson || $locked) {
                        $sheet->setCellValue($cell, '-||-||-');
                        $style->getProtection()
                            ->setLocked(Protection::PROTECTION_PROTECTED);
                    } elseif ($field == 'percent') {
                        $sheet->setCellValue(
                            $cell,
                            $percentThird
                        );
                        $style->getProtection()
                            ->setLocked(Protection::PROTECTION_PROTECTED);
                    } else {
                        $value = $salaryMap[$personId][$monthKey][$field] ?? 0;

                        $sheet->setCellValue($cell, $value);
                        $style->getProtection()
                            ->setLocked(Protection::PROTECTION_UNPROTECTED);
                    }

                    $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    $col++;
                }
            }

            $monthColsEnd = $col - 1;

            $salaryCells = [];
            $insuranceCells = [];
            for ($i = $monthColsStart; $i <= $monthColsEnd; $i += 3) {
                $salaryCells[] = Coordinate::stringFromColumnIndex($i) . $rowNum;
                $insuranceCells[] = Coordinate::stringFromColumnIndex($i + 1) . $rowNum;
            }

            $sheet->setCellValue(
                Coordinate::stringFromColumnIndex($sumSalaryCol) . $rowNum,
                '=SUM(' . implode(',', $salaryCells) . ')'
            );
            $totalSalaryCols[] = Coordinate::stringFromColumnIndex($sumSalaryCol) . $rowNum;

            $sheet->setCellValue(
                Coordinate::stringFromColumnIndex($sumInsuranceCol) . $rowNum,
                '=SUM(' . implode(',', $insuranceCells) . ')'
            );
            $totalInsuranceCols[] = Coordinate::stringFromColumnIndex($sumInsuranceCol) . $rowNum;

            $sheet->setCellValue(
                Coordinate::stringFromColumnIndex($sumMonthsCol) . $rowNum,
                $monthSum
            );
            $totalMontsCols[] = Coordinate::stringFromColumnIndex($sumMonthsCol) . $rowNum;

            $sheet->getStyle(Coordinate::stringFromColumnIndex($sumSalaryCol) . $rowNum)
                ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
            $sheet->getStyle(Coordinate::stringFromColumnIndex($sumInsuranceCol) . $rowNum)
                ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
            $sheet->getStyle(Coordinate::stringFromColumnIndex($sumMonthsCol) . $rowNum)
                ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

            $rowNum++;
        }


        //TOTALS all rows
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($sumSalaryCol) . $rowNum, 'Общо Заплата');
        $cellSallary = Coordinate::stringFromColumnIndex($sumSalaryCol) . $rowNum + 1;
        $sheet->setCellValue(
            $cellSallary,
            '=SUM(' . implode(',', $totalSalaryCols) . ')'
        );
        $sheet->getStyle(Coordinate::stringFromColumnIndex($sumSalaryCol) . $rowNum)
            ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($sumSalaryCol) . $rowNum + 1)
            ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);


        $sheet->setCellValue(Coordinate::stringFromColumnIndex($sumInsuranceCol) . $rowNum, 'Общо Осигуровки');
        $sheet->setCellValue(
            Coordinate::stringFromColumnIndex($sumInsuranceCol) . ($rowNum + 1),
            '=SUM(' . implode(',', $totalInsuranceCols) . ')'
        );

        $sheet->setCellValue(Coordinate::stringFromColumnIndex($sumMonthsCol) . $rowNum, 'Общо месеци');
        $cellMonths = Coordinate::stringFromColumnIndex($sumMonthsCol) . ($rowNum + 1);
        $sheet->setCellValue(
            $cellMonths,
            '=SUM(' . implode(',', $totalMontsCols) . ')'
        );

        $sheet->getStyle(Coordinate::stringFromColumnIndex($sumInsuranceCol) . $rowNum)
            ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($sumInsuranceCol) . $rowNum + 1)
            ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);


        $sheet->setCellValue(Coordinate::stringFromColumnIndex($sumSalaryCol) . $rowNum + 2, 'Средна работна заплата');
        $sheet->setCellValue(
            Coordinate::stringFromColumnIndex($sumSalaryCol) . $rowNum + 3,
            "=IF($cellMonths <> 0, ROUND($cellSallary / $cellMonths, 2), 0)"
        );
        $sheet->getStyle(Coordinate::stringFromColumnIndex($sumSalaryCol) . $rowNum + 2)
            ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($sumSalaryCol) . $rowNum + 3)
            ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // ==== PROTECTION ====
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('IO_w3b_d3v');

        // ==== HASH ====
        sort($egnList);
        $hash = hash('sha256', implode('|', $egnList));

        $spreadsheet->getProperties()
            ->setCreator('MIR-BIA')
            ->setTitle('Report')
            ->setCategory($hash)
            ->setDescription($hash)
            ->setCustomProperty('identifirer_hash', $hash);

        // ==== AUTOSIZE ====
        for ($c = 1; $c <= Coordinate::columnIndexFromString($sheet->getHighestColumn()); $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        // ==== FILE NAME ====
        $fileName = 'Отчет от ' .
            date('d.m.Y', strtotime($reportFrom) ?: 0) . ' до ' . date('d.m.Y', strtotime($reportTo) ?: 0) . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    public function importExcel(array $data, int $reportId, string $excelHash): int
    {
        if ($data === []) {
            throw new \RuntimeException('Празен Excel файл.');
        }

        // ===== HEADER ROWS =====
        $topRow    = array_shift($data) ?: [];
        $bottomRow = array_shift($data) ?: [];

        $headerTop    = array_map('trim', $topRow);
        $headerBottom = array_map('trim', $bottomRow);

        // ===== FIXED IDENTIFIER COLUMN (F) =====
        $egnColumnIndex = 5;
        if (!isset($headerTop[$egnColumnIndex])) {
            throw new CRUDException('Колона за идентификатор (F) не съществува в Excel.');
        }

        // ===== HASH CHECK =====
        $excelEgnList = [];
        foreach ($data as $row) {
            if (isset($row[$egnColumnIndex])) {
                $egn = trim((string)$row[$egnColumnIndex]);
                // @phpstan-ignore-next-line
                if ($egn !== '' || $egn !== 'X') {
                    $excelEgnList[] = $egn;
                }
            }
        }

        if ($excelEgnList === []) {
            throw new \RuntimeException('В Excel файла няма ЕГН.');
        }

        sort($excelEgnList);
        $calculatedHash = hash('sha256', implode('|', $excelEgnList));

        if ($calculatedHash !== $excelHash) {
            throw new CRUDException(
                'Excel файлът е променен или не съответства на първоначалните данни.'
            );
        }

        // ===== MONTH MAP =====
        $monthMap = [];
        $headerTopCnt = count($headerTop);
        foreach ($headerTop as $index => $topTitle) {
            if (!preg_match('/^(\d{2})[.,](\d{4})$/', $topTitle, $m)) {
                continue;
            }

            $monthKey = sprintf('%02d.%04d', $m[1], $m[2]);
            $monthMap[$monthKey] = [];

            for ($i = $index; $i < $headerTopCnt; $i++) {
                if (($headerTop[$i] ?? "") !== '' && $i !== $index) {
                    break;
                }

                $sub = mb_strtolower($headerBottom[$i] ?? "");

                if (str_contains($sub, 'заплата')) {
                    $monthMap[$monthKey]['salary'] = $i;
                } elseif (str_contains($sub, 'осигур')) {
                    $monthMap[$monthKey]['insurance'] = $i;
                } elseif (str_contains($sub, 'процент')) {
                    $monthMap[$monthKey]['percent'] = $i;
                }
            }
        }

        if ($monthMap === []) {
            throw new \RuntimeException('В Excel файла няма месечни колони.');
        }

        // ===== REPORT PERIOD (ЕДИН ПЪТ) =====
        $report = $this->db->row(
            'SELECT date_from, date_to FROM reports WHERE report = ?',
            [$reportId]
        );

        if (!$report) {
            throw new \RuntimeException('Отчета не съществува');
        }

        $tz = new \DateTimeZone('Europe/Sofia');

        $reportFrom = (new \DateTimeImmutable((string)$report['date_from'], $tz))
            ->modify('first day of this month');

        $reportTo = (new \DateTimeImmutable((string)$report['date_to'], $tz))
            ->modify('first day of this month');

        // ===== TRANSACTION =====
        try {
            $this->db->begin();

            $this->db->query(
                "DELETE FROM employee_salary es
                USING workplace_empls we
                JOIN workplaces w ON we.workplace_id = w.workplace
                WHERE es.workplace_empl = we.workplace_empl
                AND w.report_id = ?",
                [$reportId]
            );

            foreach ($data as $row) {
                $personIdFromExcel = $row[1]; // ako ima 2 egn-ta da znaem koe da vzemem
                if (!is_numeric($personIdFromExcel)) {
                    continue;
                }
                // dd($row[1]);
                $egn = isset($row[$egnColumnIndex]) ? trim((string)$row[$egnColumnIndex]) : '';
                if ($egn === '') {
                    continue;
                }

                $employee = $this->db->table('employees')
                    ->filter('employees.identifirer', $egn)
                    ->filter('workplace_empls.workplace_empl', $personIdFromExcel)
                    ->filter('workplace_empls.workplaces.report_id', $reportId)
                    ->select([
                        'workplace_empls.workplace_empl as person',
                        'workplace_empls.start_date',
                        'workplace_empls.end_date'
                    ])[0] ?? null;

                if (!$employee) {
                    continue;
                }

                $personId = (int)$employee['person'];

                // ===== JOB PERIOD =====
                $jobFrom = (new \DateTimeImmutable($employee['start_date'], $tz))
                    ->modify('first day of this month');

                $jobTo = !empty($employee['end_date'])
                    ?
                    (new \DateTimeImmutable($employee['end_date'], $tz))
                    ->modify('first day of this month')
                    : $reportTo;

                // ===== INTERSECTION =====
                $validFrom = $jobFrom > $reportFrom ? $jobFrom : $reportFrom;
                $validTo   = $jobTo   < $reportTo   ? $jobTo   : $reportTo;

                $refundSum = 0;
                $salarySum = 0;

                foreach ($monthMap as $monthKey => $cols) {
                    [$month, $year] = array_map('intval', explode('.', $monthKey));

                    // ===== ENSURE SAME TIMEZONE =====
                    $current = (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month), $tz));

                    if ($current < $validFrom && $current > $validTo) {
                        continue;
                    }

                    $salary    = isset($cols['salary'])    ? (float)($row[$cols['salary']] ?? 0)    : 0;
                    $insurance = isset($cols['insurance']) ? (float)($row[$cols['insurance']] ?? 0) : 0;
                    $percent   = isset($cols['percent'])   ? (float)($row[$cols['percent']] ?? 0)   : 0;

                    $this->db->query('INSERT INTO employee_salary
                        (workplace_empl, salary, insurance, percent, month, year)
                        SELECT
                            we.workplace_empl, :salary, :insurance, :percent, :month, :year
                        FROM
                            workplace_empls we
                        JOIN
                            workplaces w ON we.workplace_id = w.workplace
                        WHERE
                            we.workplace_empl = :person_id
                            AND w.report_id = :report_id;
                        ', [
                        "salary" => $salary,
                        "insurance" => $insurance,
                        "percent" => $percent * 100,
                        "month" => $month,
                        "year" => $year,
                        "person_id" => $personId,
                        "report_id" => $reportId,
                    ]);

                    $refundSum += $insurance;
                    $salarySum += $salary;
                }

                $this->db->query('UPDATE workplace_empls we
                    SET refund_sum = ?, salary_amount  = ?
                    FROM workplaces w
                    WHERE we.workplace_id = w.workplace
                    AND we.employee_id = ?
                    AND w.report_id = ?', [$refundSum, $salarySum, $personId, $reportId]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return $reportId;
    }
    public function getNKPD(int $nkpd): string
    {
        return $this->db->one(
            "SELECT CONCAT(code,' - ' , name) as name
        FROM nom_nkpd
        WHERE nom_nkpd.nkpd = ?",
            $nkpd
        );
    }
    public function getKID(int $kid): string
    {
        return $this->db->one(
            "SELECT CONCAT(code,' - ' , name) as name
        FROM nom_kid
        WHERE nom_kid.kid = ?",
            $kid
        );
    }
    public function changeStatus(int $report, int $status, ?string $generalComment = null): void
    {
        $this->db->query(
            "UPDATE reports
         SET
             status = ?,
             general_comment = COALESCE(?, general_comment)
         WHERE report = ?",
            [
                $status,
                $generalComment,
                $report
            ]
        );
    }
    public function lockReport(int $report): void
    {
        $this->db->query(
            "UPDATE reports
         SET locked = 1
         WHERE report = ?",
            [$report]
        );
    }
    protected function getMonthsRange(string $from, string $to): array
    {
        $start = new \DateTime($from);
        $start->modify('first day of this month');

        $end = new \DateTime($to);
        $end->modify('first day of this month');

        $months = [];

        while ($start <= $end) {
            $months[] = $start->format('m.Y');
            $start->modify('+1 month');
        }

        return $months;
    }
    public function getSubmitMir(int $reportId): array
    {
        $intl = $this->intl;
        $rowsRaw = $this->db->get(
            "SELECT
            e.employee
            , we.workplace_empl
            , e.name
            , w.workplace_no
            , we.start_date
            , we.end_date
            , we.last_amend_date
            , we.refund_sum
            , we.profession
            , nn.code as nkpd_code
            , nn.name as nkpd_name
            , c.contract_date as contract_start_date
            , c.contract_term as contract_end_date
            , we.last_term
            , rec.comment as person_comment
            , r.general_comment
            from workplace_empls we
            join employees e on we.employee_id  = e.employee
            join workplaces w on w.workplace = we.workplace_id
            join nom_nkpd nn on nn.nkpd = w.position_id
            join reports r on r.report = w.report_id
            join contracts c on c.contract = r.contract_id
            LEFT JOIN report_employee_comments rec
                ON rec.report_id = r.report
                    AND rec.workplace_empl = we.workplace_empl
            where r.report = ?
            order by w.workplace_no, we.start_date",
            [$reportId]
        )->toArray();

        $kids = $this->db->table('reports')
            ->with('contracts.nom_kid')
            ->filter('reports.report', $reportId)
            ->collection(['nom_kid.code', 'nom_kid.name'])
            ->toArray('code', 'name');

        $salaryRaw = $this->db->table('employee_salary')
            ->filter('workplace_empls.workplaces.report_id', $reportId)
            ->order('workplace_empl, year, month')
            ->select([
                'workplace_empl',
                'month',
                'year',
                'salary',
                'insurance',
                'percent'
            ]);

        $months = [];
        $salaryByPerson = [];

        foreach ($salaryRaw as $s) {
            $pid = (int)$s['workplace_empl'];
            $key = sprintf('%04d-%02d', $s['year'], $s['month']);

            $salaryByPerson[$pid][$key] = [
                'salary'    => (float)$s['salary'],
                'insurance' => (float)$s['insurance'],
                'percent'   => (float)$s['percent'],
            ];

            $monthKey = '_locale.months.long.' . (int)$s['month'];

            $months[$key] = mb_strtolower(
                $s['year'] . ' ' . $intl($monthKey),
                'UTF-8'
            );
        }

        ksort($months);

        $jobPeriods = [];
        foreach ($rowsRaw as $r) {
            $job = (int)$r['workplace_empl'];

            if ($r['start_date']) {
                $jobPeriods[$job][] = [
                    'start' => new \DateTime($r['start_date']),
                    'end'   => $r['end_date'] ? new \DateTime($r['end_date']) : null,
                ];
            }
        }

        $rows = [];

        $totals = [
            'total_salary'    => 0,
            'total_insurance' => 0,
            'total_months'    => 0,
            'average_salary'  => 0,
        ];

        foreach ($rowsRaw as $r) {
            $pid = (int)$r['workplace_empl'];
            $personMonths = $salaryByPerson[$pid] ?? [];

            $salaryRow    = [];
            $insuranceRow = [];
            $percentRow   = [];
            $totalMonths  = 0;

            foreach ($months as $key => $_) {
                $salaryRow[$key]    = $personMonths[$key]['salary']    ?? 0;
                $insuranceRow[$key] = $personMonths[$key]['insurance'] ?? 0;
                $percentRow[$key]   = $personMonths[$key]['percent']   ?? 0;
                if ($salaryRow[$key] != 0 && $insuranceRow[$key] != 0) {
                    $totalMonths++;
                }
            }

            $totalSalary    = array_sum($salaryRow);
            $totalInsurance = array_sum($insuranceRow);
            $refundSum      = (float)($r['refund_sum'] ?? 0);
            $difference     = round($refundSum - $totalSalary, 2);

            $vacancyInfo = $this->getJobVacancyInfo(
                $jobPeriods[(int)$r['workplace_empl']] ?? []
            );

            $rows[] = [
                // идентификация
                'workplace_empl' => $pid,
                'fullname'       => $r['name'],

                // // работно място
                'workplace_no' => $r['workplace_no'],

                // длъжност
                'nkpd_code' => $r['nkpd_code'],
                'nkpd_name' => $r['nkpd_name'],

                // дати
                'contract_start' => $r['contract_start_date'],
                'contract_end'   => $r['contract_end_date'],
                'job_start'      => $r['start_date'],
                'job_end'        => $r['end_date'],
                'reason'         => $r['last_term'],

                // пари
                'refund_sum' => $refundSum,
                'salary'     => $salaryRow,
                'insurance'  => $insuranceRow,
                'percent'    => $percentRow,

                'total_salary'    => $totalSalary,
                'total_insurance' => $totalInsurance,
                'total_months'    => $totalMonths,
                'difference'      => $difference,
                'is_mismatch'     => abs($difference) > 0.01,

                // статус
                'status' => empty($r['end_date']) ? 'Активен' : 'Напуснал',


                'job_vacancy_label' => $vacancyInfo['label'],
                'job_vacancy_days'  => $vacancyInfo['days'],
                'comment' => $r['person_comment'],
            ];

            $totals['total_salary'] = $totals['total_salary'] + $totalSalary;
            $totals['total_insurance'] = $totals['total_insurance'] + $totalInsurance;
            $totals['total_months'] = $totals['total_months'] + $totalMonths;
        }

        $totals['average_salary'] = $totals['total_months']
            ? round($totals['total_salary'] / $totals['total_months'], 2)
            : 0;
        return [
            'report_id'         => $reportId,
            'months'            => $months,
            'rows'              => $rows,
            'kids'              => $kids,
            'totals'            => $totals,
            'general_comment'   => $rowsRaw[0]['general_comment'] ?? "",
        ];
    }
    public function saveSubmitMir(int $reportId, int $status, array $comments, ?string $generalComment = null): void
    {

        $this->db->begin();

        try {
            $this->savePersonComment($reportId, $comments);
            $this->changeStatus($reportId, $status, $generalComment);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    public function savePersonComment(int $reportId, array $comments): void
    {
        $sql = "INSERT INTO report_employee_comments
            (report_id, workplace_empl, comment)
        VALUES
            (?, ?, ?)
        ON CONFLICT (report_id, workplace_empl)
        DO UPDATE SET
            comment = EXCLUDED.comment,
            updated_at = now()";

        foreach ($comments as $emplPerson => $comment) {
            $comment = trim((string)$comment);

            if ($comment === '') {
                continue;
            }
            $this->db->query($sql, [
                $reportId,
                (int)$emplPerson,
                $comment
            ]);
        }
    }
    protected function getJobVacancyInfo(array $periods): array
    {
        if (!$periods) {
            return [
                'label' => 'Няма назначен служител',
                'days'  => null,
            ];
        }

        usort($periods, fn($a, $b) => $a['start'] <=> $b['start']);

        $now        = new \DateTime();
        $hasActive  = false;
        $maxGapDays = 0;

        foreach ($periods as $p) {
            if ($p['end'] === null) {
                $hasActive = true;
            }
        }
        $periodsCnt = count($periods);
        for ($i = 0; $i < $periodsCnt - 1; $i++) {
            if ($periods[$i]['end']) {
                $gap = $periods[$i]['end']->diff($periods[$i + 1]['start'])->days;
                $maxGapDays = max($maxGapDays, $gap);
            }
        }

        $last = end($periods);
        if ($last['end']) {
            $gap = $last['end']->diff($now)->days;
            $maxGapDays = max($maxGapDays, $gap);
        }

        if ($hasActive && $maxGapDays === 0) {
            return [
                'label' => 'Активен',
                'days'  => 0,
            ];
        }

        return [
            'label' => 'Без човек: ' . $maxGapDays . ' дни',
            'days'  => $maxGapDays,
        ];
    }
    public function getEmailByReport(int $report): array
    {
        return $this->db->col(
            'SELECT
                distinct users.mail
                FROM reports
                JOIN contracts ON contracts.contract = reports.contract_id
                JOIN companies ON companies.company = contracts.company
                JOIN company_egns ON company_egns.company = companies.company
                JOIN user_providers ON user_providers.id = company_egns.egn AND user_providers.provider = ?
                JOIN users ON users.usr = user_providers.usr
                JOIN user_groups ON user_groups.usr = users.usr
                WHERE reports.report = ?
                AND user_groups.grp IN (??)',
            [
                'StampIT',
                $report,
                [$this->config->getInt('ADMIN_INV'), $this->config->getInt('NORMAL_INV')]
            ]
        );
    }
    public function getEmployeeByReport(int $report): ?int
    {
        return $this->db->one(
            'SELECT count(e.employee) from employees e
            join workplace_empls we on we.employee_id = e.employee
            join workplaces w  on w.workplace  = we.workplace_id
            join reports r  on r.report  = w.report_id
            where r.report  = ?',
            [$report]
        );
    }
    public function canReportAccess(int $contract): bool
    {
        $isMIR = $this->isMIR();
        $isINV = $this->isINV();

        if (!$isMIR && !$isINV) {
            return false;
        }

        if ($isMIR) {
            return false;
        } elseif ($isINV) {
            $egn = $this->user->getData()['egn'];

            if ($egn) {
                return $this->db->table('contracts')
                    ->filter('contracts.contract', $contract)
                    ->filter('companies.company_egns.egn', $egn)
                    ->count() > 0;
            } else {
                return false;
            }
        }

        return false;
    }
    public function getCorrectionDays(\DateTime|string|null $correctionEndDate): array
    {
        if (is_null($correctionEndDate)) {
            return [
                'days_left'   => 0,
                'days_passed' => 0
            ];
        }
        $today = new \DateTime('today');

        if (!$correctionEndDate instanceof \DateTime) {
            $correctionEndDate = new \DateTime($correctionEndDate);
        }

        $intervalLeft = $today->diff($correctionEndDate)->days;
        $daysLeft = max(0, $correctionEndDate >= $today ? $intervalLeft : 0);


        $daysPassed = max(0, $correctionEndDate < $today ? $intervalLeft : 0);

        return [
            'days_left'   => $daysLeft,
            'days_passed' => $daysPassed
        ];
    }
    public function hasSpecialPermissions(): bool
    {
        return $this->isMIR() && $this->user->hasPermission('contracts/unlock', true);
    }
    public function journal(string $message, string $lvl, int $id, array $context = []): void
    {
        $this->db->table('log_system')->insert([
            'module'  => $this->module->getName(),
            'module_id'  => $id,
            'message' => $message,
            'lvl' => $lvl,
            'context' => json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'created' => date('Y-m-d H:i:s'),
            'usr' => $this->user->getID()
        ]);
    }
    public function logImports(int $report, int $file, int $type = 0): void
    {
        $this->db->table('reports_imports')->insert([
            'report' => $report,
            'file_id' => $file,
            'created' => date('Y-m-d H:i:s'),
            'usr' => $this->user->getID(),
            'type' => $type
        ]);
    }
    public function sendNotification(ReportsEntity $entity, int $status): void
    {
        $subject = '';
        $body = '';
        $emailData = [];
        $emails = [];

        //submited
        if (
            $entity->status !== ReportStatus::Submitted->value
            && $status === ReportStatus::Submitted->value
        ) {
            $emails = (array) $entity->contracts?->users->clone()->pluck('mail')->toArray();
            if ($this->config->getString('MIR_SPECIAL_NOTIFICATION_EMIAL')) {
                $emails[] = $this->config->getString('MIR_SPECIAL_NOTIFICATION_EMIAL');
            }

            $subject = "Подаден отчет или допълнителни документи към отчет";
            $body = "Номер на договор {contract}<br>
                Името на инвеститора: {company_name}<br>
                ЕИК: {eik}";
            $emailData = [
                '{contract}' => $entity->contracts?->contract_number,
                '{company_name}' => $entity->contracts?->companies->company_name,
                '{eik}' => $entity->contracts?->companies->id,
            ];
        } elseif (
            $entity->status !== ReportStatus::Approved->value
            && $status === ReportStatus::Approved->value
        ) {
            $emails = $this->getEmailByReport($entity->report);
            $subject = "Одобрен Отчет";
            $body = "Одобрен Отчет за заетостта № {number} от договор {contract}";
            $emailData = [
                '{number}' => $entity->report_number,
                '{contract}' => $entity->contracts?->contract_number
            ];
        } elseif (
            $entity->status !== ReportStatus::Rejected->value
            && $status === ReportStatus::Rejected->value
        ) {
            $emails = $this->getEmailByReport($entity->report);
            $subject = "Отказан Отчет";
            $body = "Отказан Отчет за заетостта № {number} от договор {contract}";
            $emailData = [
                '{number}' => $entity->report_number,
                '{contract}' => $entity->contracts?->contract_number
            ];
        } elseif (
            $entity->status !== ReportStatus::ReturnedForCorrection->value
            && $status === ReportStatus::ReturnedForCorrection->value
        ) {
            $emails = $this->getEmailByReport($entity->report);
            $subject = "Коригиране на Отчет за заетостта №" . $entity->report_number . '  от договор '
                . $entity->contracts?->contract_number;
            $body = "Моля, коригирайте отчета в рамките на {days} дни, според предоставените указания.<br>"
                . "Указания: {instruction}";
            $emailData = [
                '{days}' => 14,
                '{instruction}' => $entity->general_comment ?? ""
            ];
        }

        //send notification
        if (count($emailData) && !empty($body) && !empty($subject) && count($emails)) {
            $message = Helper::emailTemplateReplace(
                array_keys($emailData),
                array_values($emailData),
                $body
            );

            $mail = new Mail($this->config->getString('FROM_EMAIL'), $subject, $message);
            $mail->setTo((array)$emails);
            try {
                $this->sender->send($mail);
            } catch (MailException $e) {
                Helper::storeMail($this->db, $mail);
            }
        }
    }
    public function getInsurableIncome(DateTime $from, DateTime $to): array
    {
        return $this->db->table('insurable_income')
            ->where('from_date >= ?', [$from->format("Y-m-d")])
            ->where('to_date <= ?', [$to->format("Y-m-d")])
            ->collection()
            ->toArray();
    }
    /**
     * @return Collection<int|string, mixed>
     */
    public function importsList(int $id): Collection
    {
        $report = $this->entities()->filter('report', $id)->select()[0] ?? null;
        if ($report) {
            return $report->reports_imports;
        } else {
            return new Collection();
        }
    }

    protected function fromArray(Entity $entity, array $data = []): void
    {
        foreach (
            [
                'mir_doc',
                'mir_checklist',
                'pdf_sign'
            ] as $f
        ) {
            if (isset($data[$f]) && !(int)$data[$f]) {
                $data[$f] = null;
            }
        }
        if (isset($data['percent_second'])) {
            $data['percent_second'] = Helper::toDecimal($data['percent_second']);
        }

        if (isset($data['percent_third'])) {
            $data['percent_third'] = Helper::toDecimal($data['percent_third']);
        }

        parent::fromArray($entity, $data);
    }

    public function getUser(): string
    {
        return $this->user->getData()['name'];
    }
    public function getWorkplaceStatus(array $workplace): array
    {
        $empls = $workplace['employees'];

        if (!$empls) {
            return ['status' => 0, 'days' => 0];
        }

        foreach ($empls as $e) {
            if (empty($e['end_date'])) {
                return ['status' => 1, 'days' => 0];
            }
        }

        $periods = [];

        foreach ($empls as $e) {
            if ($e['start_date'] && $e['end_date']) {
                $periods[] = [
                    'start' => new DateTimeImmutable($e['start_date']),
                    'end'   => new DateTimeImmutable($e['end_date']),
                ];
            }
        }

        usort($periods, fn($a, $b) => $a['start'] <=> $b['start']);

        $today = new DateTimeImmutable('today');
        $freeDays = 0;
        $cntPeriod = count($periods);
        for ($i = 1; $i < $cntPeriod; $i++) {
            $gapStart = $periods[$i - 1]['end']->modify('+1 day');
            $gapEnd   = $periods[$i]['start']->modify('-1 day');

            if ($gapStart <= $gapEnd) {
                $freeDays += $gapStart->diff($gapEnd)->days + 1;
            }
        }

        /**
         * @psalm-suppress PossiblyInvalidArrayAccess
         * @phpstan-ignore-next-line
         */
        $lastEnd = end($periods)['end'];
        $from = $lastEnd->modify('+1 day');

        if ($from <= $today) {
            $freeDays += $from->diff($today)->days + 1;
        }

        return ['status' => 2, 'days' => $freeDays];
    }

    /**
     * @return Collection<int|string, mixed>
     */
    public function getWorkplaces(int $id): Collection
    {
        $report = $this->read($id);

        $entities = new Collection();

        $workplaces = $report
            ->workplaces_report_id
            ->sortBy(fn(WorkplacesEntity $a, WorkplacesEntity $b): int => $a->workplace_no <=> $b->workplace_no)
            ->toArray();
        /** @var ReportsModule $module */
        $module = $this->module;

        foreach ($workplaces as $workplace) {
            $obj = new stdClass();
            $obj->workplace = $workplace->workplace;
            $obj->workplace_no = $workplace->workplace_no;

            $statusData = $module->getWorkplaceStatus($workplace);

            $status = $statusData['status'];
            $days = $statusData['days'];
            if ($status === 0) {
                $statusName = $this->intl->get('Not occupied');
                $statusColor = 'gray';
                $statusNumb = 0;
            } elseif ($status === 1) {
                $statusName = $this->intl->get('Occupied');
                $statusColor = 'green';
                $statusNumb = 1;
            } else {
                $statusName = $this->intl->get('Free') .
                    " ($days " . $this->intl->get('days without employee') . ")";
                $statusColor = 'yellow';
                $statusNumb = 2;
                if ($days > 180) {
                    $statusColor = 'red';
                }
            }

            $obj->status = $statusName;
            $obj->status_color = $statusColor;

            if ($workplace->workplace_empls->count() == 0) {
                $obj->nkpd = null;
                $obj->status = $this->intl->get('Not occupied');
                $obj->status_color = 'gray';
                $obj->start_date = null;
                $obj->end_date = null;
                $obj->name = null;
                $obj->employee = null;
                $obj->report = $report->report;
                $obj->statusNumb = $statusNumb;
                $obj->locked = $report->locked;
            } else {
                $w = $workplace->workplace_empls->toArray();
                // @var WorkplaceEmplsEntity $workplaceEmpl
                $workplaceEmpl = end($w);
                if ($workplaceEmpl) {
                    $obj->nkpd = $workplace->nom_nkpd->name ?? null;
                    $obj->start_date = $workplaceEmpl->start_date;
                    $obj->end_date = $workplaceEmpl->end_date;
                    $obj->name = $workplaceEmpl->employees->name;
                    $obj->employee = $workplaceEmpl->employees->employee;
                    $obj->report = $report->report;
                    $obj->statusNumb = $statusNumb;
                    $obj->locked = $report->locked;
                }
            }
            /** @psalm-suppress InvalidArgument */
            $entities->add($obj);
        }

        return $entities;
    }

    public function createSubValidator(Validator $validator): Validator
    {
        return $validator->required('date_to')
            ->required('workplaces')
            ->required('percent_second')
            ->required('percent_third');
    }
    public function sendReportValidator(Validator $validator): Validator
    {
        return $validator
            ->required('pdf_sign', $this->intl->get('signed pdf is required'));
    }
}
