<?php

declare(strict_types=1);

namespace zni\modules\contracts;

use schema\ContractsEntity;
use vakata\collection\Collection;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\intl\Intl;
use vakata\user\User;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDModuleInterface;
use webadmin\modules\common\crud\CRUDServiceInterface;
use webadmin\modules\common\crud\CRUDServiceVersioned;
use webadmin\modules\common\ekatte\EkatteService;
use webadmin\modules\ModulesContainer;
use zni\clients\RegixClient;
use zni\modules\nomenc\nomenc\NomencService;
use zni\permission\PermissionService;

/**
 * @extends CRUDServiceVersioned<ContractsEntity>
 * @implements CRUDServiceInterface<ContractsEntity>
 */
class ContractsService extends CRUDServiceVersioned implements CRUDServiceInterface
/**
 * @param CRUDModuleInterface<ContractsEntity, CRUDServiceInterface<ContractsEntity>> $module
 */
{
    public function __construct(
        ContractsModule $module,
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
        $entities =  parent::entities();
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
                ->filter('users.usr', $this->user->getID());
        } elseif ($isINV) {
            $egn = $this->user->getData()['egn'];

            if ($egn) {
                $entities = $entities
                    ->filter('companies.company_egns.egn', $egn);
            } else {
                return  $entities->filter('contract', null);
            }
        }

        return $entities;
    }
    public function create(array $data = []): Entity
    {
        if (self::checkContract($data)) {
            throw new CRUDException('crud.create.exist');
        }

        $data['contract_type'] = !empty($data['contract_type']) ? $data['contract_type']  : null;
        $data['company_type'] = !empty($data['company_type']) ? $data['company_type']  : null;
        $data['sector'] = !empty($data['sector']) ? $data['sector']  : null;
        $data['sector_activity'] = !empty($data['sector_activity']) ? $data['sector_activity']  : null;
        $data['cert_type'] = !empty($data['cert_type']) ? $data['cert_type']  : null;
        $data['period_reporting'] = !empty($data['period_reporting']) ? $data['period_reporting']  : null;

        $this->db->begin();
        try {
            $entity = parent::create($data);
            $contractUsers = $data['users'] ?? [];

            $this->db->query(
                "DELETE FROM contract_users WHERE contract = ?",
                [$entity->contract]
            );

            if (count($contractUsers) >= 1) {
                foreach ($contractUsers as $usr) {
                    $this->db->query(
                        "INSERT INTO contract_users (contract, usr) VALUES (?, ?)",
                        [$entity->contract, $usr]
                    );
                }
            }

            $banks = $data['bank'] ?? [];

            foreach ($banks as $bank) {
                $this->db->query(
                    "INSERT INTO contract_banks
             (contract, bank_date_from, bank_date_to, bank_amount, bank_name)
             VALUES (?, ?, ?, ?, ?)",
                    [
                        $entity->contract,
                        !empty($bank['bank_date_from'])
                            ? (new \DateTime($bank['bank_date_from']))->format('Y-m-d')
                            : null,
                        !empty($bank['bank_date_to'])
                            ? (new \DateTime($bank['bank_date_to']))->format('Y-m-d')
                            : null,
                        !empty($bank['bank_amount'])
                            ? (float) $bank['bank_amount']
                            : null,
                        $bank['bank_name'] ?? null
                    ]
                );
            }

            // //  contract_orders
            $orders = $data['order'] ?? [];
            foreach ($orders as $order) {
                $this->db->query(
                    "INSERT INTO contract_orders (contract, order_date, order_date_return, order_amount)
                         VALUES (?, ?, ?, ?)",
                    [
                        $entity->contract,
                        !empty($order['order_date'])
                            ? (new \DateTime($order['order_date']))->format('Y-m-d')
                            : null,
                        !empty($order['order_date_return'])
                            ? (new \DateTime($order['order_date_return']))->format('Y-m-d')
                            : null,
                        !empty($order['order_amount'])
                            ? $this->comaToPoint($order['order_amount'])
                            : null,
                    ]
                );
            }
            if (isset($data['kid']) && is_string($data['kid'])) {
                $data['kid'] = [$data['kid']];
            }

            foreach ($data['kid'] as $kid) {
                $this->db->query(
                    "INSERT INTO contract_kid (contract, kid) VALUES (?, ?)",
                    [
                        $entity->contract,
                        $kid,
                    ]
                );
            }

            $this->db->commit();

            $this->journal('Създаване на договор', 'info', $entity->contract);
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        $temp = parent::read($id);

        $data['company_type'] = !isset($data['company_type']) ? $temp->company_type : $data['company_type'];
        $data['date_application'] = !isset($data['date_application'])
            ? $temp->date_application
            : $data['date_application'];
        $data['sector'] = !isset($data['sector']) ? $temp->sector : $data['sector'];
        $data['sector_activity'] = !isset($data['sector_activity'])
            ? $temp->sector_activity
            : $data['sector_activity'];
        $data['cert_date'] = !isset($data['cert_date']) ? $temp->cert_date : $data['cert_date'];
        $data['cert_expire'] = !isset($data['cert_expire']) ? $temp->cert_expire : $data['cert_expire'];
        $data['cert_number'] = !isset($data['cert_number']) ? (int)$temp->cert_number : (int)$data['cert_number'];
        $data['cert_type'] = !isset($data['cert_type']) ? $temp->cert_type : $data['cert_type'];
        $data['period_reporting'] = !isset($data['period_reporting'])
            ? $temp->period_reporting
            : (int)$data['period_reporting'];
        $data['period_value'] = !isset($data['period_value']) ? $temp->period_value : (int)$data['period_value'];
        $data['declaration'] = !isset($data['declaration']) ? $temp->declaration : (int)$data['declaration'];


        $this->db->begin();

        try {
            $entity = parent::update($id, $data);
            $contractId = is_array($id) ? ($id['contract'] ?? $id) : $id;
            $contractUsers = $data['users'] ?? [];

            $this->db->query(
                "DELETE FROM contract_users WHERE contract = ?",
                [$contractId]
            );
            if (!empty($contractUsers)) {
                foreach ($contractUsers as $usr) {
                    $this->db->query(
                        "INSERT INTO contract_users (contract, usr) VALUES (?, ?)",
                        [$contractId, $usr]
                    );
                }
            }

            $this->db->query("DELETE FROM contract_banks WHERE contract = ?", [$contractId]);

            $banks = $data['bank'] ?? [];

            foreach ($banks as $bank) {
                $this->db->query(
                    "INSERT INTO contract_banks
             (contract, bank_date_from, bank_date_to, bank_amount, bank_name)
             VALUES (?, ?, ?, ?, ?)",
                    [
                        $contractId,
                        !empty($bank['bank_date_from'])
                            ? (new \DateTime($bank['bank_date_from']))->format('Y-m-d')
                            : null,
                        !empty($bank['bank_date_to'])
                            ? (new \DateTime($bank['bank_date_to']))->format('Y-m-d')
                            : null,
                        !empty($bank['bank_amount'])
                            ? (float) $bank['bank_amount']
                            : null,
                        $bank['bank_name'] ?? null
                    ]
                );
            }


            // //  contract_orders
            $this->db->query("DELETE FROM contract_orders WHERE contract = ?", [$contractId]);

            $orders = $data['order'] ?? [];

            foreach ($orders as $order) {
                $this->db->query(
                    "INSERT INTO contract_orders (contract, order_date, order_date_return, order_amount)
                         VALUES (?, ?, ?, ?)",
                    [
                        $contractId,
                        !empty($order['order_date'])
                            ? (new \DateTime($order['order_date']))->format('Y-m-d')
                            : null,
                        !empty($order['order_date_return'])
                            ? (new \DateTime($order['order_date_return']))->format('Y-m-d')
                            : null,
                        !empty($order['order_amount'])
                            ? (float) $order['order_amount']
                            : null,
                    ]
                );
            }

            $this->journal('Редактиране на договор', 'info', (int) $entity->contract);

            $this->db->commit();
            return $entity;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    public function checkEIK(string $eik): array
    {
        return $this->nomencService->checkEIK($eik);
    }
    /**
     * @return Collection<int|string, array{region: int, code: string, name: string}>
     */
    public function getRegions(): Collection
    {
        return $this->ekatteService->getRegions();
    }
    /**
     * @return Collection<int|string, array{municipality: int, code: string, name: string}>
     */
    public function getMunicipalities(?int $region = null): Collection
    {
        return $this->ekatteService->getMunicipalities($region);
    }
    /**
     * @return Collection<int|string, array{city: int, name: string}>
     */
    public function getCities(?int $municipality = null): Collection
    {
        return $this->ekatteService->getCities($municipality);
    }
    public function getCertificateTypes(): array
    {
        return $this->nomencService->getCertificateTypes();
    }
    public function getContractTypes(): array
    {
        return $this->nomencService->getContractTypes();
    }
    public function getCompanyTypes(): array
    {
        return $this->nomencService->getCompanyTypes();
    }
    public function getCompanies(): array
    {
        return $this->db->get("SELECT
            company, CONCAT(company_name,' - ', id) as name
            FROM companies
            ORDER BY company_name")
            ->toArray('company', 'name');
    }
    public function getSectors(): array
    {
        return $this->nomencService->getSectors();
    }
    public function getSectorActivities(int $sector): array
    {
        return $this->nomencService->getSectorActivities($sector);
    }
    public function getReportingPeriod(): array
    {
        return $this->nomencService->getReportingPeriod();
    }
    public function getContractUsers(): array
    {
        return $this->nomencService->getContractUsers();
    }
    public function getMunicipalitiesAndRegionByCity(int $selectedCity): array
    {
        return $this->nomencService->getMunicipalitiesAndRegionByCity($selectedCity);
    }
    public function checkContract(array $data): bool
    {
        $toDbDate = function (string $value): ?string {
            $dt = \DateTime::createFromFormat('d.m.Y', $value);
            return $dt ? $dt->format('Y-m-d') : null;
        };
        $params = [
            $data['cert_number'] ?? null,
            $data['contract_number'] ?? null,
            isset($data['contract_date']) ? $toDbDate($data['contract_date']) : null,
            isset($data['date_application']) ? $toDbDate($data['date_application']) : null
        ];
        $sql = "SELECT contract
        FROM contracts
        WHERE cert_number = ?
          AND contract_number = ?
          AND contract_date = ?
          AND date_application = ?
        LIMIT 1 ";

        $row = $this->db->one($sql, $params);

        return !empty($row);
    }
    public function comaToPoint(string $value): ?string
    {
        $value = str_replace(',', '.', trim($value));
        return is_numeric($value) ? $value : null;
    }
    protected function fromArray(Entity $entity, array $data = []): void
    {
        if (date('Y', strtotime($data['contract_date'] ?: 'now') ?: 0) > $this->config->getInt('MAX_YEAR_TO_BGN')) {
            $data['currency'] = 'eur';
        }

        parent::fromArray($entity, $data);
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $data = parent::toArray($entity, $relations);

        $banks = [];
        foreach ($entity->contract_banks as $bank) {
            /** @psalm-suppress PossiblyNullPropertyFetch */
            $banks[] = [
                'bank_date_from' => $bank->bank_date_from
                    ? (new \DateTime($bank->bank_date_from))->format('d.m.Y')
                    : '',
                'bank_date_to'   => $bank->bank_date_to
                    ? (new \DateTime($bank->bank_date_to))->format('d.m.Y')
                    : '',
                'bank_amount'    => $bank->bank_amount ?? '',
                'bank_name'      => $bank->bank_name ?? '',
            ];
        }


        $orders = [];
        foreach ($entity->contract_orders as $order) {
            $orders[] = [
                'order_date'        => isset($order->order_date)
                    ? (new \DateTime((string)$order->order_date))->format('d.m.Y')
                    : '',
                'order_date_return' => isset($order->order_date_return)
                    ? (new \DateTime((string)$order->order_date_return))->format('d.m.Y')
                    : '',
                'order_amount'      => $order->order_amount ?? '',
            ];
        }

        $data['bank'] = $banks;
        $data['order'] = $orders;
        $data['kid'] = $entity->nom_kid->clone()->pluck('kid')->toArray();
        $data['files'] = array_map('intval', explode(',', (string) $data['files']));
        $data['users'] = $entity->users->clone()->pluck('usr')->toArray();
        $data['region'] = $entity->cities?->municipalities->region;
        $data['municipality'] = $entity->cities?->municipalities->municipality;

        return $data;
    }
    public function isINV(): bool
    {
        return $this->permissionService->isINV();
    }
    public function isMIR(): bool
    {
        return $this->permissionService->isMIR();
    }
    public function currencies(): array
    {
        return [
            'eur' => "currency.eur",
            'bgn' => "currency.bgn",
        ];
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
    public function getContractFullInfo(int $contractId): ?array
    {
        // ===== CONTRACT =====
        $contract = $this->db->get(
            "SELECT
            c.*,
            comp.id,
            comp.company_name,
            comp.company_region,
            comp.company_municipality,
            comp.company_city,
            comp.company_address,
            comp.company_email
         FROM contracts c
         JOIN companies comp ON comp.company = c.company
         WHERE c.contract = ?",
            [$contractId]
        )->toArray();

        if (!$contract) {
            return null;
        }

        // ===== LAST REPORT =====
        $report = $this->db->one(
            "SELECT
            r.*
         FROM reports r
         WHERE r.contract_id = ?
         ORDER BY r.report DESC
         LIMIT 1",
            [$contractId]
        );

        if (!$report) {
            return [
                'contract'   => $contract[0],
                'report'     => null,
                'workplaces' => []
            ];
        }

        // ===== WORKPLACES =====
        $workplaces = $this->db->get(
            "SELECT
            w.*
         FROM workplaces w
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
             ORDER BY we.workplace_empl ASC",
                [$workplace['workplace']]
            )->toArray();

            $workplace['employees'] = $employees;
        }

        $kid = $this->db->all(
            "SELECT
         contract_kid.kid
         FROM contract_kid
         WHERE contract = ?",
            [$contractId]
        );

        return [
            'contract'   => $contract[0],
            'report'     => $report,
            'workplaces' => $workplaces,
            'kid' => $kid
        ];
    }

    public function createCopyContract(array $data): void
    {

        $toDbDate = function (?string $date): ?string {
            $date = trim((string)$date);

            if ($date === '') {
                return null;
            }

            $d = \DateTime::createFromFormat('d.m.Y', $date);

            if ($d instanceof \DateTime) {
                return $d->format('Y-m-d');
            }

            return $date;
        };

        $this->db->begin();

        try {
            $contract = $this->create($data);

            $reportId = (int)$this->db->query(
                "INSERT INTO reports (
                contract_id
                , report_number
                , date_from
                , date_to
                , status
                , percent_second,percent_third
            )
             VALUES (?, ?, ?, ?, ?)",
                [
                    $contract->contract,
                    (int)($data['report_number'] ?? 1),
                    $toDbDate($data['report_date_from'] ?? null),
                    $toDbDate($data['report_date_to'] ?? null),
                    0,
                    $data['percent_second'],
                    $data['percent_third']

                ]
            )->insertID();

            foreach (($data['workplaces'] ?? []) as $workplace) {
                $newWorkplaceId = (int)$this->db->query(
                    "INSERT INTO workplaces (report_id, position_id, workplace_no)
                 VALUES (?, ?, ?)",
                    [
                        $reportId,
                        $workplace['position_id'] ?? null,
                        $workplace['workplace'] ?? null
                    ]
                )->insertID();

                foreach (($workplace['employees'] ?? []) as $employee) {
                    $employeeId = (int)($employee['employee'] ?? 0);

                    if (!$employeeId) {
                        continue;
                    }

                    $identifier = trim((string)($employee['identifirer'] ?? ''));
                    $identifierType = (int)($employee['identifirer_type'] ?? 0);

                    $regixType = null;

                    if ($identifierType === 1) {
                        $regixType = 'EGN';
                    } elseif ($identifierType === 2) {
                        $regixType = 'LNCH';
                    }

                    $apiData = [];

                    if ($identifier !== '' && $regixType) {
                        $apiData = (array)$this->fetchEmployeeDataFromApi($identifier, $regixType, $reportId);
                    }

                    $this->db->query(
                        "INSERT INTO workplace_empls (
                        workplace_id,
                        employee_id,
                        start_date,
                        end_date,
                        refund_sum,
                        salary_amount,
                        project_start_date,
                        not_empl_report,
                        last_amend_date,
                        reason,
                        eco_code,
                        profession,
                        ekatte,
                        last_term,
                        sync_status,
                        last_sync,
                        type_expense
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $newWorkplaceId,
                            $employeeId,
                            $toDbDate($apiData['start_date'] ?? $data['report_date_from'] ?? null),
                            $toDbDate($apiData['end_date'] ?? null),
                            $apiData['refund_sum'] ?? 0,
                            $apiData['salary_amount'] ?? 0,
                            $toDbDate($apiData['project_start_date'] ?? null),
                            $apiData['not_empl_report'] ?? 0,
                            $toDbDate($apiData['last_amend_date'] ?? null),
                            $apiData['reason'] ?? null,
                            $apiData['eco_code'] ?? null,
                            $apiData['profession'] ?? ($workplace['position_id'] ?? null),
                            $apiData['ekatte'] ?? null,
                            $apiData['last_term'] ?? null,
                            $apiData['sync_status'] ?? 1,
                            $toDbDate($apiData['last_sync'] ?? date('d.m.Y')),
                            $apiData['type_expense'] ?? 1
                        ]
                    );
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function fetchEmployeeDataFromApi(string $identifier, string $regixType, int $reportId): array
    {
        return $this->syncEetzForPerson($identifier, $regixType, $reportId);
    }
    protected function syncEetzForPerson(string $identifier, string $type, int $reportId): array
    {
        $companyBulstat = (string)$this->getBulstatByReport($reportId, $identifier);
        $client = new RegixClient();
        $today = date('Y-m-d');

        $result = [
            'workplace_id' => null,
            'employee_id' => null,
            'start_date' => null,
            'end_date' => null,
            'refund_sum' => 0,
            'salary_amount' => 0,
            'project_start_date' => null,
            'not_empl_report' => 0,
            'last_amend_date' => null,
            'reason' => null,
            'eco_code' => null,
            'profession' => null,
            'ekatte' => null,
            'last_term' => null,
            'sync_status' => 2,
            'last_sync' => $today,
            'type_expense' => null,
        ];

        $dataRegix = $client->getEmploymentContracts($identifier, $type, $today);

        if (
            !isset($dataRegix['data']['Status']['Code']) ||
            (int)$dataRegix['data']['Status']['Code'] !== 0
        ) {
            return $result;
        }

        $contracts = $dataRegix['data']['EContracts']['EContract'] ?? [];

        if (!$contracts) {
            return $result;
        }

        if (isset($contracts['ContractorBulstat'])) {
            $contracts = [$contracts];
        }

        foreach ($contracts as $contract) {
            if (
                isset($contract['ContractorBulstat']) &&
                (string)$contract['ContractorBulstat'] === $companyBulstat
            ) {
                $nkpd = $this->db->one(
                    "SELECT nkpd
                        FROM nom_nkpd
                        WHERE regexp_replace(name, '[,]+', '', 'g') =
                        regexp_replace(?, '[,]+', '', 'g')",
                    $contract['ProfessionName']
                );

                return [
                    'start_date' => $contract['StartDate'] ?? null,
                    'end_date' => $contract['EndDate'] ?? null,
                    'last_amend_date' => $contract['LastAmendDate'] ?? ($contract['BeginAmendmentDate'] ?? null),
                    'reason' => $contract['Reason'] ?? null,
                    'eco_code' => $contract['EcoCode'] ?? null,
                    'profession' => $nkpd ?? null,
                    'ekatte' => $contract['EKATTECode'] ?? null,
                    'last_term' => $contract['LastTermId'] ?? null,
                    'sync_status' => 1,
                    'last_sync' => $today,
                ];
            }
        }

        return $result;
    }
    public function getBulstatByReport(int $reportId, string $egn): string
    {
        $sql = "SELECT id
        FROM companies c2
        join company_egns ce on c2.company = ce.company
        join contracts c on c2.company = c.company
        join reports r  on r.contract_id = c.contract
        where r.report  = ?
        and ce.egn  = ?";

        $params = [$reportId, $egn];
        return $this->db->one($sql, $params);
        // $report = $this->db->get($sql, $params)->toArray('empl_report', 'name');
    }
    public function getNKPD(): array
    {

        return $this->nomencService->getNkpd();
    }
}
