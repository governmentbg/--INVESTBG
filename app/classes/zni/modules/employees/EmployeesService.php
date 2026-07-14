<?php

declare(strict_types=1);

namespace zni\modules\employees;

use DateTime;
use schema\EmployeesEntity;
use schema\ReportsEntity;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\intl\Intl;
use vakata\user\User;
use webadmin\modules\common\crud\CRUDNotFoundException;
use webadmin\modules\common\crud\CRUDServiceInterface;
use webadmin\modules\common\crud\CRUDServiceVersioned;
use webadmin\modules\common\ekatte\EkatteService;
use webadmin\modules\ModulesContainer;
use zni\clients\RegixClient;
use zni\enums\ReportStatus;
use zni\modules\nomenc\nomenc\NomencService;
use zni\permission\PermissionService;

/**
 * @extends CRUDServiceVersioned<EmployeesEntity>
 * @implements CRUDServiceInterface<EmployeesEntity>
 */
class EmployeesService extends CRUDServiceVersioned implements CRUDServiceInterface
/**
 * @param CRUDModuleInterface<EmployeesEntity, CRUDServiceInterface<EmployeesEntity>> $module
 */
{
    public function __construct(
        EmployeesModule $module,
        DBInterface $db,
        User $user,
        protected ModulesContainer $mc,
        protected Intl $intl,
        protected EkatteService $ekatteService,
        protected NomencService $nomencService,
        protected PermissionService $permissionService,
        protected Config $config
    ) {
        parent::__construct($module, $db, $user);
    }

    public function entities(): TableQueryMapped
    {
        $entities =  parent::entities()
            ->with('workplace_empls');

        //ako e otgovornik - vijda vsichki
        foreach ($this->user->getGroups() as $group) {
            if (
                in_array((int)$group->getId(), [
                    $this->config->getInt('RESPONSIBLE_MIR'),
                    $this->config->getInt('CHECKING_MIR_CONTRACT')
                ])
            ) {
                return $entities;
            }
        }

        $isMIR = $this->isMIR();
        $isINV = $this->isINV();

        if ($isMIR) {
            $entities = $entities
                ->filter('workplace_empls.workplaces.reports.contracts.users.usr', $this->user->getID());
        } elseif ($isINV) {
            $egn = $this->user->getData()['egn'];

            if ($egn) {
                $entities = $entities
                    ->filter('workplace_empls.workplaces.reports.contracts.companies.company_egns.egn', $egn);
            } else {
                return $entities->filter('contract', null);
            }
        }

        return $entities;
    }
    public function create(array $data = []): Entity
    {
        $workplaceId = (int)($data['workplace_id'] ?? 0);
        $positionId  = (int)($data['position'] ?? 0);

        $identType = (int)($data['identifirer_type'] ?? 0);
        $ident     = trim((string)($data['identifirer'] ?? ''));

        $this->db->begin();
        try {
            $employeeId = 0;

            if ($identType > 0 && $ident !== '') {
                $employeeId = (int)$this->db->one(
                    "SELECT employee
                 FROM employees
                 WHERE identifirer_type = ? AND identifirer = ?
                 LIMIT 1",
                    [$identType, $ident]
                );
            }


            $entity = null;

            if ($employeeId > 0) {
                $entity = $this->read($employeeId);
            } else {
                $entity = parent::create($data);
                $employeeId = (int)($entity->employee ?? 0);

                if ($employeeId <= 0) {
                    throw new \RuntimeException('Missing employee id after parent::create().');
                }
            }

            $this->db->query(
                "UPDATE workplaces
             SET position_id = :pid
             WHERE workplace = :wid",
                [
                    'pid' => $positionId,
                    'wid' => $workplaceId
                ]
            );

            $date = static function (?string $d): string {
                $d = trim((string)$d);
                return $d !== '' ? (new DateTime($d))->format('Y-m-d') : '';
            };

            $workplaceEmplData = [
                'workplace_id' => $workplaceId,
                'employee_id' => $employeeId,
                'start_date'  => !empty($data['start_date'])
                    ? date('Y-m-d', strtotime($data['start_date']) ?: 0)
                    : null,
                'end_date'  => !empty($data['end_date'])
                    ? date('Y-m-d', strtotime($data['end_date']) ?: 0)
                    : null,
                'refund_sum'  => (float)($data['refund_sum'] ?? 0),
                'salary_amount'  => (float)($data['salary_amount'] ?? 0),
                'project_start_date' => !empty($data['project_start_date'])
                    ? date('Y-m-d', strtotime($data['project_start_date']) ?: 0)
                    : null,
                'last_amend_date' => !empty($data['last_amend_date'])
                    ? date('Y-m-d', strtotime($data['last_amend_date']) ?: 0)
                    : null,
                'reason' => $data['reason'] ?? null,
                'eco_code'    => $data['eco_code'] ?? null,
                'profession'   => $data['position'] ?? null,
                'ekatte' => $data['ekatte'] ?? null,
                'last_term' => $data['last_term'] ?? null,
                'sync_status' => (int)($data['sync_status'] ?? 0),
                'last_sync' => date('Y-m-d H:i:s'),
                'type_expense' => (int)($data['type_expense'] ?? 0),
                'not_empl_report' => isset($data['mr']) ? 1 : 0
            ];

            $this->db->table('workplace_empls')->insert($workplaceEmplData);

            //mr report
            if (isset($data['mr'])) {
                $workplaceEmpl = $this->db->table('workplace_empls')
                    ->filter('workplace_id', $workplaceId)
                    ->filter('employee_id', $employeeId)
                    ->select()[0] ?? null;
                if ($workplaceEmpl) {
                    $this->db->query(
                        "INSERT INTO maintenance_reports_employees
                        (mr, workplace_empl)
                        VALUES (?, ?)
                        ON CONFLICT (mr, workplace_empl)
                        DO NOTHING",
                        [
                            $data['mr'],
                            $workplaceEmpl['workplace_empl']
                        ]
                    );
                }
            }

            $this->db->commit();
            return $entity;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function update(mixed $id, array $data = []): Entity
    {

        $temp = parent::read($id);

        $selectedReport = (int)($data['allReports'] ?? 0);
        if ($selectedReport <= 0) {
            throw new \RuntimeException('Missing selected report (allReports).');
        }

        $assignment = null;
        $report = null;

        foreach ($temp->workplace_empls as $we) {
            $r = $we->workplaces->reports ?? null;
            if ($r && (int)$r->report === $selectedReport) {
                $assignment = $we;
                $report = $r;
                break;
            }
        }

        if (!$assignment || !$report) {
            throw new \RuntimeException('Assignment/report not found for selected report.');
        }

        if ((int)$report->locked === 1 && (int)$report->status !== ReportStatus::Draft->value) {
            return $temp;
        }

        $editable = in_array(
            (int)$report->status,
            [ReportStatus::Draft->value, ReportStatus::ReturnedForCorrection->value],
            true
        );

        if (!$editable) {
            throw new CRUDNotFoundException('Report is not editable.');
        }

        $workplaceEmplId = (int)$assignment->workplace_empl;

        $data['refund_sum'] = $data['totalInsurance'] ?? ($data['refund_sum'] ?? 0);
        unset($data['totalInsurance'], $data['totalSalary'], $data['allReports']);

        $salaryInfo = [];

        $salaryArr = $data['salary'] ?? [];
        $insArr    = $data['insurance'] ?? [];
        $perArr    = $data['percent'] ?? [];

        foreach ($salaryArr as $key => $salary) {
            $key = (string)$key;

            if (!preg_match('/^\d{4}_\d{1,2}$/', $key)) {
                continue;
            }

            [$year, $month] = array_map('intval', explode('_', $key));

            $salaryInfo[$year][$month] = [
                'salary'    => $salary,
                'insurance' => $insArr[$key] ?? 0,
                'percent'   => $perArr[$key] ?? 0,
            ];
        }

        unset($data['salary'], $data['insurance'], $data['percent']);

        $date = static function (?string $d): string {
            $d = trim((string)$d);
            if ($d === '') {
                return '';
            }
            if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $d)) {
                [$dd, $mm, $yy] = explode('.', $d);
                return "{$yy}-{$mm}-{$dd}";
            }
            return (new DateTime($d))->format('Y-m-d');
        };

        $this->db->begin();
        try {
            $this->db->table('workplace_empls')
                ->filter('workplace_empl', $workplaceEmplId)
                ->update([
                    'start_date'         => $date($data['start_date'] ?? '') ?: null,
                    'end_date'           => $date($data['end_date'] ?? '') ?: null,
                    'refund_sum'         => (float)($data['refund_sum']),
                    'salary_amount'      => (float)($data['salary_amount'] ?? 0),
                    'project_start_date' => $date($data['project_start_date'] ?? '') ?: null,
                    'last_amend_date'    => $date($data['last_amend_date'] ?? '') ?: null,
                    'reason'             => $data['reason'] ?? null,
                    'eco_code'           => $data['eco_code'] ?? null,
                    'profession'         => $data['position'] ?? ($data['profession'] ?? null),
                    'ekatte'             => $data['ekatte'] ?? null,
                    'last_term'          => $data['last_term'] ?? null,
                    'sync_status'        => (int)($data['sync_status'] ?? 0),
                    'last_sync'          => $date($data['last_sync'] ?? date('Y-m-d H:i:s')),
                    'type_expense'       => (int)($data['type_expense'] ?? 0),
                ]);

            $this->db->table('employee_salary')
                ->filter('workplace_empl', $workplaceEmplId)
                ->delete();

            foreach ($salaryInfo as $year => $months) {
                foreach ($months as $month => $vals) {
                    $salary    = $this->toDecimal((string)$vals['salary']);
                    $insurance = $this->toDecimal((string)$vals['insurance']);
                    $percent   = $this->toDecimal((string)$vals['percent']);

                    $this->db->table('employee_salary')->insert([
                        'workplace_empl' => $workplaceEmplId,
                        'salary' => $salary,
                        'insurance' => $insurance,
                        'percent' => $percent,
                        'month' => (int)$month,
                        'year' => (int)$year,
                    ]);
                }
            }

            $this->db->commit();
            return parent::read($id);
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    public function name(Entity $entity): string
    {
        return $entity->name ?? "";
    }
    public function getPositions(): array
    {
        return $this->nomencService->getNkpd();
    }
    public function getWorkplaces(int $reportId): array
    {
        return $this->nomencService->getWorkplaces($reportId);
    }
    public function getReport(int $id): ?ReportsEntity
    {
        return $this->db->tableMapped('reports')
            ->filter('report', $id)->select()[0] ?? null;
    }

    public function getAllWorkplaces(int $reportId): array
    {
        return $this->nomencService->getAllWorkplaces($reportId);
    }
    public function getBulstatByReport(int $report, string $egn): ?string
    {
        $sql = "SELECT id
        FROM companies c2
        join company_egns ce on c2.company = ce.company
        join contracts c on c2.company = c.company
        join reports r  on r.contract_id  = c.contract
        where r.report = ?
        and ce.egn  = ?";

        $params = [$report, $egn];
        return $this->db->one($sql, $params);
    }
    public function getTypeExpense(): array
    {
        return $this->nomencService->getTypeExpense();
    }
    public function toDecimal(?string $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        $v = (string)$value;

        $v = str_replace(' ', '', $v);

        $v = str_replace(',', '.', $v);

        $v = preg_replace('/[^0-9.\-]/', '', $v);

        if ($v === '' || $v === '-' || $v === '.') {
            return '0.00';
        }

        return number_format((float)$v, 2, '.', '');
    }
    // @phpstan-ignore-next-line
    public function getEETZ(
        string $id,
        string $type,
        int $reportId,
        ?int $employee = null,
        ?int $workplace = null
    ): array {
        $user = $this->user->getData()['auth'][0]['id'];

        $companyBulstat = (string)$this->getBulstatByReport($reportId, $user);

        $is_checked = 0;
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
                if (!isset($contract['ContractorBulstat'])) {
                    continue;
                }

                $bulstat = (string)$contract['ContractorBulstat'];
                $bulstat = trim($bulstat);

                if (strlen($bulstat) >= 10) {
                    $bulstat = substr($bulstat, 0, 9);
                } elseif (strlen($bulstat) === 7) {
                    $bulstat = str_pad($bulstat, 9, '0', STR_PAD_LEFT);
                }

                if ($bulstat === $companyBulstat) {
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
                                // 'contractor_bulstat'  => $contract['ContractorBulstat'],
                                'name' => $contract['IndividualNames'] ?? null,
                                'start_date' => $contract['StartDate'] ?? null,
                                'end_date'   => $contract['EndDate'] ?? null,
                                'last_amend_date'     => $contract['LastAmendDate'] ?? null,
                                'reason'              => $contract['Reason'] ?? null,
                                'eco_code'            => $contract['EcoCode'] ?? null,
                                'position'            => $nkpd ?? null,
                                'ekatte'           => $contract['EKATTECode'] ?? null,
                                'last_term'           => $contract['LastTermId'] ?? null,
                                'sync_status'           => $is_checked,
                                'last_sync'           => date('Y-m-d H:i:s'),
                            ]);
                    }
                    $result = [
                        'data' => [
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
                            'last_sync'           => date('Y-m-d H:i:s'),
                            // 'checkNraHidden' => 1,
                        ]
                    ];

                    break;
                }
            }
            if ($is_checked === 2) {
                $result = [
                    'data' => [
                        'name' =>  null,
                        'start_date' =>  null,
                        'end_date' =>  null,
                        'last_amend_date' => null,
                        'reason' =>  null,
                        'eco_code' =>  null,
                        'ekatte'           => null,
                        'position' => null,
                        'last_term' => null,
                        'sync_status'           => $is_checked,
                        'last_sync'           => date('Y-m-d H:i:s'),
                        // 'checkNraHidden' => 1,

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
    public function isINV(): bool
    {
        return $this->permissionService->isINV();
    }
    public function isMIR(): bool
    {
        return $this->permissionService->isMIR();
    }
    public function getKid(): array
    {
        return $this->nomencService->getKid();
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

    public function getEmployeeSalary(int $workplaceEmpl): array
    {
        $rows = $this->db->get(
            "SELECT month, year, salary, insurance, percent
         FROM employee_salary
         WHERE workplace_empl = ?
         ORDER BY year, month",
            [$workplaceEmpl]
        )->toArray();

        $result = [];

        foreach ($rows as $r) {
            $key = $r['year'] . '_' . $r['month'];

            $result[$key] = [
                'salary' => $r['salary'],
                'insurance' => $r['insurance'],
                'percent' => $r['percent'],
            ];
        }

        return $result;
    }
    public function regixStatuses(): array
    {
        $intl = $this->intl;
        return [
            '0' => $intl('regix.not.checked'),
            '1' => $intl('regix.success'),
            '2' => $intl('regix.not.compay'),
        ];
    }
}
