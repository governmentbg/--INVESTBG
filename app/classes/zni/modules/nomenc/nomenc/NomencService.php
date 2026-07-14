<?php

declare(strict_types=1);

namespace zni\modules\nomenc\nomenc;

use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\intl\Intl;
use vakata\user\User;
use webadmin\modules\common\crud\CRUDService;
use webadmin\modules\ModulesContainer;
use schema\NomCertificateTypesEntity;

/**
 * @extends CRUDService<NomCertificateTypesEntity>
 */
class NomencService extends CRUDService
{
    public function __construct(
        NomencModule $module,
        DBInterface $db,
        User $user,
        protected ModulesContainer $mc,
        protected Intl $intl,
        protected Config $config
    ) {
        parent::__construct($module, $db, $user);
    }

    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    public function getWorkplaces(int $reportId): array
    {
        $reportId = (int)$reportId;

        $workplaces = $this->db->get(
            "SELECT workplace, workplace_no
         FROM workplaces
         WHERE report_id = ?
         ORDER BY workplace_no",
            [$reportId]
        )->toArray();

        if (!$workplaces) {
            return [];
        }

        $ids = array_column($workplaces, 'workplace');
        $idsList = implode(',', array_map('intval', $ids));

        //$ids = [1, 2, 3]; // пример

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT
        workplace_id,
        start_date,
        end_date
    FROM workplace_empls
    WHERE workplace_id IN ($placeholders)
    ORDER BY workplace_id, start_date NULLS LAST, end_date NULLS LAST";

        $rows = $this->db->get($sql, $ids)->toArray();

        $byWp = [];
        foreach ($rows as $r) {
            $byWp[(int)$r['workplace_id']][] = $r;
        }

        $today = new \DateTimeImmutable('today');
        $result = [];

        foreach ($workplaces as $w) {
            $wpId = (int)$w['workplace'];
            $wpNo = (int)$w['workplace_no'];

            $periods = $byWp[$wpId] ?? [];


            if (!$periods) {
                $result[$wpId] = 'Работно място ' . $wpNo . ' (не е заемано)';
                continue;
            }


            $hasActive = false;
            foreach ($periods as $p) {
                if (empty($p['end_date'])) {
                    $hasActive = true;
                    break;
                }
            }
            if ($hasActive) {
                continue;
            }


            $closed = [];
            foreach ($periods as $p) {
                if (!empty($p['start_date']) && !empty($p['end_date'])) {
                    $closed[] = $p;
                }
            }

            if (!$closed) {
                $result[$wpId] = 'Работно място ' . $wpNo . ' (свободно)';
                continue;
            }
            usort($closed, fn($a, $b) => strcmp($a['start_date'], $b['start_date']));

            $totalFreeDays = 0;

            $closedCnt = count($closed);
            for ($i = 1; $i < $closedCnt; $i++) {
                $prevEnd = new \DateTimeImmutable($closed[$i - 1]['end_date']);
                $nextStart = new \DateTimeImmutable($closed[$i]['start_date']);

                $gapStart = $prevEnd->modify('+1 day');
                $gapEnd   = $nextStart->modify('-1 day');

                if ($gapStart <= $gapEnd) {
                    $totalFreeDays += $gapStart->diff($gapEnd)->days + 1;
                }
            }


            $lastEnd = new \DateTimeImmutable(end($closed)['end_date']);
            $freeFrom = $lastEnd->modify('+1 day');

            if ($freeFrom <= $today) {
                $totalFreeDays += $freeFrom->diff($today)->days + 1;
            }

            $lastEndStr = $lastEnd->format('d.m.Y');
            $result[$wpId] = 'Работно място ' . $wpNo . ' (свободно, общо незаето '
                . $totalFreeDays . ' дни, последно напускане' . $lastEndStr . ')';
        }

        return $result;
    }
    public function getAllWorkplaces(int $reportId): array
    {
        $reportId = (int)$reportId;

        $workplaces = $this->db->get(
            "SELECT workplace, workplace_no
         FROM workplaces
         WHERE report_id = ?
         ORDER BY workplace_no",
            [$reportId]
        )->toArray();

        $result = [];

        foreach ($workplaces as $row) {
            $result[$row['workplace']] = 'Работно място ' . $row['workplace_no'];
        }
        return $result;
    }
    public function getContractTypes(): array
    {
        return $this->db
            ->get("SELECT contract_type, name, pos FROM nom_contract_types ORDER BY pos")
            ->toArray('contract_type', 'name');
    }
    public function getCompanyTypes(): array
    {
        return $this->db
            ->get("SELECT company_type, name, pos FROM nom_company_types ORDER BY pos")
            ->toArray('company_type', 'name');
    }
    public function getSectors(): array
    {
        return $this->db
            ->get("SELECT sector, name, pos FROM nom_sectors ORDER BY pos")
            ->toArray('sector', 'name');
    }
    public function getSectorActivities(int $sector): array
    {
        return $this->db
            ->get("SELECT sector_activity, name, pos FROM nom_sector_activities
            WHERE sector = ?
            ORDER BY pos", $sector)
            ->toArray('sector_activity', 'name');
    }
    public function getReportingPeriod(): array
    {
        return $this->db
            ->get("SELECT reporting_period, name, pos FROM nom_reporting_periods ORDER BY pos")
            ->toArray('reporting_period', 'name');
    }
    public function getNkpd(): array
    {
        return $this->db
            ->get("SELECT nkpd,CONCAT(code,' -',name)as name  FROM nom_nkpd ORDER BY code")
            ->toArray('nkpd', 'name');
    }
    public function getKid(): array
    {
        return $this->db
            ->get("SELECT kid, CONCAT(code,'. ',name)as name FROM nom_kid ORDER BY code")
            ->toArray('kid', 'name');
    }
    public function getCertificateTypes(): array
    {
        return $this->db
            ->get("SELECT certificate_type, name, pos FROM nom_certificate_types ORDER BY pos")
            ->toArray('certificate_type', 'name');
    }
    public function getTypeExpense(): array
    {
        return $this->db
            ->get("SELECT type_expense, name, pos FROM nom_type_expense ORDER BY pos")
            ->toArray('type_expense', 'name');
    }
    public function checkEIK(string $eik): array
    {
        $ident = preg_replace('/\D/', '', $eik);
        if (empty($ident)) {
            return [];
        }

        $baseUrl = "https://portal.registryagency.bg/cr/api/Deeds/$ident";
        $headers = [
            'User-Agent: Mozilla/5.0',
            'Accept: application/json'
        ];

        $urlCompany = "$baseUrl/TRRULNCRepresentatives";
        $companyData = $this->fetchJson($urlCompany, $headers);
        if (!$companyData || !isset($companyData['companyFullName'])) {
            return [];
        }

        $companyName = $companyData['companyFullName'];
        $companyOwnerName = $companyData['subDeeds'][0]['fields'][0]['representativeList'][0]['subject']['name']
            ?? '';


        $urlSeat = "$baseUrl/Seat";
        $seatData = $this->fetchJson($urlSeat, $headers);
        if (!$seatData) {
            return [];
        }

        $addr = $seatData['address'] ?? [];
        $contacts = $seatData['contacts'] ?? [];


        $email = $contacts['eMail'] ?? '';
        $phone = $contacts['phone'] ?? '';


        $parts = [];
        foreach (['settlement', 'postCode', 'foreignPlace', 'housingEstate'] as $k) {
            if (!empty($addr[$k])) {
                $parts[] = $addr[$k];
            }
        }

        if (!empty($addr['street'])) {
            $street = $addr['street'];
            if (!empty($addr['streetNumber'])) {
                $street .= ' №' . $addr['streetNumber'];
            }
            $parts[] = $street;
        }

        foreach (['block' => 'бл.', 'entrance' => 'вх.', 'floor' => 'ет.', 'apartment' => 'ап.'] as $k => $prefix) {
            if (!empty($addr[$k])) {
                $parts[] = "$prefix " . $addr[$k];
            }
        }

        $fullAddress = implode(', ', $parts);

        return [
            'companyName'      => $companyName,
            'companyOwnerName' => $companyOwnerName,
            'email'            => $email,
            'phone'            => $phone,
            'city'             => $addr['settlementEKATTE'] ?? '',
            'fullAddress'      => $fullAddress
        ];
    }
    private function fetchJson(string $url, array $headers): ?array
    {
        $ch = curl_init($url);
        if (!$ch) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $err = curl_errno($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err || $http !== 200 || !$response) {
            return null;
        }

        return json_decode((string)$response, true);
    }
    public function getContractUsers(): array
    {

        $masterId = $this->config->getInt('MASTER_MIR');
        $subadminId = $this->config->getInt('RESPONSIBLE_MIR');
        $moderatorContractId = $this->config->getInt('CHECKING_MIR_CONTRACT');
        $moderatorId = $this->config->getInt('CHECKING_MIR');
        $adminId = $this->config->getInt('ADMIN_MIR');


        $groupIds = [$masterId, $adminId, $subadminId, $moderatorId, $moderatorContractId];
        $groupIds = array_map('intval', $groupIds);

        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));

        $sql = "
        SELECT u.usr, u.name
        FROM users u
        JOIN user_groups ug ON u.usr = ug.usr
        WHERE ug.grp IN ($placeholders)
        AND disabled = 0
        ORDER BY name
    ";

        // $rows = $this->db->get($sql);
        $rows = $this->db->get($sql, $groupIds);

        return $rows->toArray('usr', 'name');
    }
    public function getMunicipalitiesAndRegionByCity(int $selectedCity): array
    {
        $sql = ("SELECT
            m.name as municipalityName,
            m.municipality,
            r.name as regionName,
            r.region,
            c.name as cityName,
            c.city
            from regions r
            join municipalities m  on m.region = r.region
            join cities c on c.municipality = m.municipality
            where c.city = :selectedCity ");
        $params = ['selectedCity' => (int) $selectedCity];

        $row = $this->db->row($sql, $params);

        return [
            'municipality' => [
                'name' => $row['municipalityname'] ?? '',
                'id' => $row['municipality'] ?? [],
            ],
            'region' => [
                'name' => $row['regionname'] ?? '',
                'id' => $row['region'] ?? [],
            ],
            'city' => [
                'name' => $row['cityname'] ?? '',
                'id' => $row['city'] ?? [],
            ]

        ];
    }
    public function normalizeItems(mixed $input, string $requiredKey): array
    {
        $result = [];

        if (empty($input)) {
            return $result;
        }

        foreach ((array) $input as $item) {
            if (is_string($item)) {
                $decoded = json_decode($item, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    foreach ($decoded as $decodedItem) {
                        if (is_array($decodedItem) && isset($decodedItem[$requiredKey])) {
                            $result[] = $decodedItem;
                        }
                    }
                }
                break;
            }
        }

        return $result;
    }
    public function isModerator(string $egn): bool
    {

        $isModerator = $this->db->table('comapny_egn')
            ->where('egn', [$egn])
            ->select(['moderator']);

        return true;
    }
}
