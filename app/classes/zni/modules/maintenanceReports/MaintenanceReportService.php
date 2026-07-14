<?php

declare(strict_types=1);

namespace zni\modules\maintenanceReports;

use DateTime;
use schema\MaintenanceReportsEntity;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\schema\TableQueryMapped;
use vakata\intl\Intl;
use vakata\user\User;
use vakata\database\schema\Entity;
use vakata\http\Uri;
use vakata\validation\Validator;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDServiceInterface;
use webadmin\modules\common\crud\CRUDServiceVersioned;
use zni\clients\RegixClient;
use zni\enums\MaintenanceReportStatus;
use zni\enums\ReportStatus;
use zni\permission\PermissionService;

/**
 * @extends CRUDServiceVersioned<MaintenanceReportsEntity>
 * @implements CRUDServiceInterface<MaintenanceReportsEntity>
 */
class MaintenanceReportService extends CRUDServiceVersioned implements CRUDServiceInterface
{
    public function __construct(
        MaintenanceReportModule $module,
        DBInterface $db,
        protected PermissionService $permissionService,
        protected User $user,
        protected Config $config,
        protected Intl $intl,
        protected Uri $uri
    ) {
        parent::__construct($module, $db, $user);
    }

    public function entities(): TableQueryMapped
    {

        $isMIR = $this->permissionService->isMIR();
        $isINV = $this->permissionService->isINV();
        $entities = parent::entities();

        if ($isMIR) {
            return $entities
                ->filter('contracts.users.usr', $this->user->getID());
        } elseif ($isINV) {
            $egn = $this->user->getData()['egn'];

            if ($egn) {
                return $entities
                    ->filter('contracts.companies.company_egns.egn', $egn);
            } else {
                return $entities->filter('contracts.companies.company_egns.egn', null);
            }
        }

        return $entities;
    }

    public function getContracts(): array
    {
        if ($this->permissionService->isMIR()) {
            $contracts = $this->db->table('contracts')
                ->filter('users.usr', $this->user->getID());
        } elseif ($this->permissionService->isINV()) {
            $egn = $this->user->getData()['egn'];

            $contracts = $this->db->table('contracts')
                ->filter('companies.company_egns.egn', $egn);
        } else {
            $contracts = $this->db->table('contracts');
        }

        $arr = $contracts->sort('contracts.contract')
            ->collection(['contract', 'contract_date', 'companies.company_name'])
            ->map(function (array $row) {
                $row['name'] = $row['contract'] . ' / ' . $row['contract_date'] . ' / ' . $row['company_name'];
                return $row;
            });

        return $arr->toArray('contract', 'name');
    }

    public function hasApproveReport(int $contract): bool
    {
        $checkReport = $this->db->table('reports')
            ->filter('contracts.contract', $contract)
            ->filter('reports.status', ReportStatus::Approved->value)
            ->count();

        return $checkReport > 0;
    }
    public function getPeriod(int $contractId): array
    {
        $contract = $this->db->table('contracts')->find($contractId);

        if (!empty($contract['period_maintenance_start']) && !empty($contract['period_maintenance_end'])) {
            $dateStart = new DateTime($contract['period_maintenance_start']);
            $dateEnd   = new DateTime($contract['period_maintenance_end']);
        } else {
            $person = $this->db->table('workplace_empls')
                ->filter('workplaces.reports.status', ReportStatus::Approved->value)
                ->filter('workplaces.reports.contracts.contract', $contractId)
                ->sort('workplace_empls.start_date', true)
                ->limit(1)
                ->select(['workplace_empls.start_date', 'workplace_empls.end_date']);

            $dateStart = new DateTime($person[0]['start_date'] ?? 'now');
            $dateEnd = clone $dateStart;
            if ($contract['period_reporting'] > 1) {
                $dateEnd->modify('+ ' . $contract['period_reporting'] - 1 . ' years');
            }
        }

        $apporvedReport = $this->entities()
            //->filter('status', MaintenanceReportStatus::Approved->value)
            ->sort('date_to', true)
            ->limit(1)
            ->collection(['date_to'])[0] ?? [];
        if ($apporvedReport) {
            $dateStart = new DateTime($apporvedReport->date_to ?? "now");
        }
        return [
            'date_from' => $dateStart->format('Y-m-d'),
            'date_to' => $dateEnd->format('Y-m-d'),
        ];
    }

    private function calcUnsupportedDays(array $persons): array
    {
        $positions = [];
        $personsIds = [];
        foreach ($persons as $row) {
            $posId = (int)$row['empl_pos'] . '' . $row['job_code'];

            if (!isset($positions[$posId])) {
                $positions[$posId] = [
                    'empl_position' => (int)$row['empl_pos'],
                    'job_number' => $row['job_code'],
                    'nkpd_code' => $row['nkpd_code'],
                    'nkpd_name' => $row['nkpd_name'],
                    'kid_code' => $row['kid_code'] ?? '',
                    'kid_name' => $row['kid_name'] ?? '',
                    'unsupported' => false,
                    'total_unsupported_days' => 0,
                    'persons' => []
                ];
            }
            $personsIds[] = $row['workplace_empl'];
            $positions[$posId]['persons'][] = $row;
        }

        foreach ($positions as &$position) {
            $prevEnd = null;
            $totalUnsupportedDays = 0;

            foreach ($position['persons'] as $person) {
                // if ($person['not_empl_report']) {
                //     continue;
                // }

                $start = new DateTime($person['start_date']);

                if ($prevEnd && $start > $prevEnd) {
                    $totalUnsupportedDays += $prevEnd->diff($start)->days;
                }

                $prevEnd = !empty($person['end_date'])
                    ? new DateTime($person['end_date'])
                    : null;
            }

            //today
            $lastEndDate = end($position['persons'])['end_date'] ?? null;
            if ($lastEndDate) {
                $endDate = new DateTime($lastEndDate);
                $totalUnsupportedDays += $endDate->diff(new DateTime('now'))->days;
            }

            $position['total_unsupported_days'] = $totalUnsupportedDays;
            $position['unsupported'] = $totalUnsupportedDays > $this->config->getInt('UNSUPPORTED_DAYS');
        }
        return [
            'positions'   => $positions,
            'persons_ids' => $personsIds
        ];
    }

    public function totalAmountUnsuportedPositions(array $positions): float
    {
        $amount = 0;
        foreach ($positions as $position) {
            if ($position['total_unsupported_days']) {
                foreach ($position['persons'] as $person) {
                    $amount += $person['refund_sum'];
                }
            }
        }
        return $amount;
    }

    public function getEETZ(
        string $id,
        string $type,
        string $companyBulstat,
        int $employee,
        int $workplace
    ): array {

        $is_checked = 0;
        $foundCompany = false;
        $result = [];

        $client = new RegixClient();
        $today = date('Y-m-d');

        $dataRegix = $client->getEmploymentContracts($id, $type, $today);

        if (
            isset($dataRegix['data']['Status']['Code']) &&
            (int)$dataRegix['data']['Status']['Code'] === 0
        ) {
            $is_checked = 2;

            $contracts = $dataRegix['data']['EContracts']['EContract'] ?? [];

            if (isset($contracts['ContractorBulstat'])) {
                $contracts = [$contracts];
            }

            foreach ($contracts as $contract) {
                if (
                    isset($contract['ContractorBulstat']) &&
                    (string)$contract['ContractorBulstat'] === $companyBulstat
                ) {
                    $foundCompany = true;
                    $is_checked = 1;
                    $nkpd = $this->db->one(
                        "SELECT nkpd
                        FROM nom_nkpd
                        WHERE regexp_replace(name, '[,]+', '', 'g') =
                        regexp_replace(?, '[,]+', '', 'g')",
                        $contract['ProfessionName']
                    );

                    if ($employee) {
                        $this->db
                            ->table('workplace_empls')
                            ->where('employee_id = ?', [$employee])
                            ->where('workplace_id = ?', [$workplace])
                            ->update([
                                'name' => $contract['IndividualNames'] ?? null,
                                'start_date' => $contract['StartDate'] ?? null,
                                'end_date'   => $contract['EndDate'] ?? null,
                                'last_amend_date'     => $contract['LastAmendDate'] ?? null,
                                'reason'              => $contract['Reason'] ?? null,
                                'eco_code'            => $contract['EcoCode'] ?? null,
                                'position'            => $nkpd ?? null,
                                'ekatte'              => $contract['EKATTECode'] ?? null,
                                'last_term'           => $contract['LastTermId'] ?? null,
                                'sync_status'         => $is_checked,
                                'last_sync'           => date('Y-m-d H:m'),
                            ]);
                    }
                    $result = [
                        'data' => [
                            'employee' => $employee,
                            'workplace' => $workplace,
                            'name' => $contract['IndividualNames'] ?? null,
                            'start_date' => $contract['StartDate'] ?? null,
                            'end_date' => $contract['EndDate'] ?? null,
                            'last_amend_date' => $contract['LastAmendDate'] ?? null,
                            'reason' => $contract['Reason'] ?? null,
                            'eco_code' => $contract['EcoCode'] ?? null,
                            'position' => $nkpd ?? null,
                            'ekatte'           => $contract['EKATTECode'] ?? null,
                            'last_term' => $contract['LastTermId'] ?? null,
                            'sync_status'           => $is_checked,
                            'last_sync'           => date('Y-m-d H:m'),
                        ]
                    ];

                    break;
                }
            }
            if ($is_checked === 2) {
                $result = [
                    'data' => [
                        'employee' => null,
                        'workplace' => null,
                        'name' =>  null,
                        'start_date' =>  null,
                        'end_date' =>  null,
                        'last_amend_date' => null,
                        'reason' =>  null,
                        'eco_code' =>  null,
                        'ekatte' => null,
                        'position' => null,
                        'last_term' => null,
                        'sync_status' => $is_checked,
                        'last_sync' => date('Y-m-d H:m'),
                    ]
                ];
            }
        }


        if ($employee && $workplace) {
            $this->db
                ->table('workplace_empls')
                ->where('employee_id = ?', [$employee])
                ->where('workplace_id = ?', [$workplace])
                ->update([
                    'sync_status' => $is_checked
                ]);
        }

        return $result;
    }

    public function getPersonsByContract(int $contract): array
    {
        $persons = $this->db->table('workplace_empls')
            ->with('employees')
            ->with('workplaces.reports')
            ->with('workplaces.reports.contracts')
            ->with('workplaces.nom_nkpd')
            ->filter('workplaces.reports.status', ReportStatus::Approved->value)
            ->filter('workplaces.reports.contracts.contract', $contract)
            ->order('workplace_empls.workplace_empl, workplaces.position_id ASC, workplace_empls.start_date ASC')
            ->select([
                'DISTINCT ON (workplace_empls.workplace_empl) workplace_empls.workplace_empl',
                'workplace_empls.employee_id',
                'contracts.contract',
                'workplaces.position_id as empl_pos',
                'employees.name',
                'employees.identifirer_type',
                'employees.identifirer',
                'workplace_empls.start_date',
                'workplace_empls.end_date',
                'workplace_empls.project_start_date',
                '\'\' as last_term',
                'workplace_empls.refund_sum',
                'contracts.contract_date as contract_start_date',
                'contracts.contract_term as contract_end_date',
                'workplace_empls.not_empl_report',
                'workplaces.workplace_no as job_code',
                'nom_nkpd.code as nkpd_code',
                'nom_nkpd.name as nkpd_name',
                'workplace_empls.workplace_empl',
                '\'\' as comment'
            ]);

        return $this->calcUnsupportedDays($persons);
    }

    public function getPersonsByMaintenanceReport(int $id, bool $onlyPerson = false): array
    {
        $persons = $this->db->query('SELECT
            DISTINCT ON (mre.workplace_empl) mre.workplace_empl
            , we.employee_id
            , c.contract
            , e.name
            , e.identifirer_type
            , e.identifirer
            , we.start_date
            , we.end_date
            , we.project_start_date
            , \'\' as last_term
            , we.refund_sum
            , we.salary_amount
            , we.not_empl_report
            , c.contract_date as contract_start_date
            , c.contract_term as contract_end_date
            , w.position_id as empl_pos
            , w.workplace_no as job_code
            , nn.code as nkpd_code
            , nn.name as nkpd_name
            , we.workplace_empl
            , mre.comment
            FROM maintenance_reports mr
            JOIN maintenance_reports_employees mre on mr.mr = mre.mr
            JOIN contracts c on c.contract = mr.contract
            JOIN workplace_empls we on mre.workplace_empl = we.workplace_empl
            JOIN employees e on e.employee = we.employee_id
            JOIN workplaces w on w.workplace = we.workplace_id
            JOIN nom_nkpd nn on nn.nkpd = w.position_id
            WHERE mr.mr = ?
            ORDER BY
                mre.workplace_empl
                , w.position_id ASC
                , we.start_date ASC', $id)->toArray();

        if ($onlyPerson) {
            return $persons;
        }

        return $this->calcUnsupportedDays($persons);
    }

    public function getNomKidsByContract(int $id): string
    {
        $data = $this->db->table('contracts')
            ->with('nom_kid')
            ->filter('contract', $id)
            ->select()[0] ?? [];
        if (count($data)) {
            return implode('', array_map(function (array $item) {
                return $item['code'] . '  ' . $item['name'] . '<br>';
            }, $data['nom_kid']));
        }

        return '';
    }

    public function name(Entity $entity): string
    {
        return '';
    }

    public function create(array $data = []): Entity
    {
        if ($this->isInv()) {
            if (isset($data['submit_report'])) {
                $data['status'] = MaintenanceReportStatus::Submitted->value;
            }
        }
        $entity = parent::create($data);

        if (isset($data['persons'])) {
            $persons = array_filter((array)$data['persons']);
            foreach ($persons as $person) {
                $this->db->table('maintenance_reports_employees')
                    ->insert([ 'mr' => $entity->mr, 'workplace_empl' => $person ]);
            }
        }
        $this->journal('Създаване на справка за поддържане на заетост', 'info', $entity->mr);
        $this->version($entity, 0, true);
        return $entity;
    }

    public function update(mixed $id, array $data = []): Entity
    {
        $entity = $this->read($id);
        $data['contract'] = $entity->contract;
        if ($this->isInvAdmin()) {
            if (
                isset($data['submit_report']) && in_array($entity->status, [
                    MaintenanceReportStatus::Draft->value,
                    MaintenanceReportStatus::ReturnedForCorrection->value
                ])
            ) {
                $data['status'] = MaintenanceReportStatus::Submitted->value;
            }
        }

        if ($this->isMIR()) {
            if (isset($data['under_review']) && $entity->status == MaintenanceReportStatus::Submitted->value) {
                $data['status'] = MaintenanceReportStatus::UnderReview->value;
            }

            if (isset($data['reject'])) {
                $data['status'] = MaintenanceReportStatus::Rejected->value;
            }

            if ($entity->status == MaintenanceReportStatus::UnderReview->value) {
                if (isset($data['approve'])) {
                    $data['status'] = MaintenanceReportStatus::Approved->value;
                }
                if (isset($data['for_correction'])) {
                    $data['status'] = MaintenanceReportStatus::ReturnedForCorrection->value;
                }
            }
        }

        if (
            $entity->status != MaintenanceReportStatus::ReturnedForCorrection->value &&
            (isset($data['status']) && $data['status'] == MaintenanceReportStatus::ReturnedForCorrection->value)
        ) {
            $data['correction_date'] = date(
                "Y-m-d",
                strtotime("tomorrow + " . $this->config->getInt('CORRECTION_DAYS') . " days") ?: null
            );

            //$data['correction_attempt'] = 0;//reset attempt
        }
        if (
            $entity->status == MaintenanceReportStatus::ReturnedForCorrection->value &&
            (isset($data['status']) && $data['status'] == MaintenanceReportStatus::Submitted->value)
        ) {
            $data['correction_date'] = null;
            $data['correction_attempt'] = 0;//reset attempt
        }

        if ($entity->status == MaintenanceReportStatus::ReturnedForCorrection->value) {
            $data['correction_attempt'] = $entity->correction_attempt + 1;
        }

        $entity = parent::update($id, $data);

        if (isset($data['persons_comments'])) {
            foreach ($entity->employees() as $emplId => $p) {
                if (isset($data['persons_comments'][$emplId])) {
                    $this->db->table('maintenance_reports_employees')
                        ->filter('workplace_empl', $emplId)
                        ->filter('mr', $entity->mr)
                        ->update([ 'comment' => $data['persons_comments'][$emplId] ]);
                }
            }
        }

        $this->journal('Редактиране на справка за поддържане на заетост', 'info', $entity->mr);
        $this->version($entity, 1, true);
        return $entity;
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $arr = parent::toArray($entity, $relations);
        $arr['persons'] = $entity->employees();
        return $arr;
    }
    protected function fromArray(Entity $entity, array $data = []): void
    {
        foreach (
            [
                'dme_prev_year',
                'sme_prev_year',
                'report_nra_prev_year',
                'document_annual_reporting_statistics',
                'other',
                'pdf_sign'
                ] as $f
        ) {
            if (isset($data[$f]) && !(int)$data[$f]) {
                $data[$f] = null;
            }
        }

        parent::fromArray($entity, $data);
    }

    public function canUpdate(MaintenanceReportsEntity $entity): bool
    {
        if (
            ($entity->status == MaintenanceReportStatus::Approved->value
            || $entity->status == MaintenanceReportStatus::Rejected->value)
            && in_array($this->config->getInt('MASTER_MIR'), array_keys($this->user->getGroups()))
        ) {
            return true;
        }

        if ($this->isInv()) {
            return $entity->status == MaintenanceReportStatus::Draft->value
                || ($entity->status == MaintenanceReportStatus::ReturnedForCorrection->value
                    && $entity->correction_attempt < $this->config->getInt('CORRECTION_ATTEMPT')
                    && ($entity->correction_date ?? "") >= date('Y-m-d')
                );
        } elseif ($this->isMIR()) {
            return $entity->status == MaintenanceReportStatus::Submitted->value
                || $entity->status == MaintenanceReportStatus::UnderReview->value
                || ($entity->status == MaintenanceReportStatus::ReturnedForCorrection->value
                    && (($entity->correction_date ?? "") < date('Y-m-d')
                    || $entity->correction_attempt >= $this->config->getInt('CORRECTION_ATTEMPT'))
                );
        }

        return true;
    }

    public function isInv(): bool
    {
        return $this->permissionService->isINV();
    }

    public function isMIR(): bool
    {
        return $this->permissionService->isMIR();
    }

    public function isInvAdmin(): bool
    {
        return $this->user->inGroup((string)$this->config->getInt('ADMIN_INV'));
    }

    public function getCorrectionAttempt(): int
    {
        return $this->config->getInt('CORRECTION_ATTEMPT', 2);
    }

    public function delete(mixed $id): void
    {
        throw new CRUDException();
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
}
