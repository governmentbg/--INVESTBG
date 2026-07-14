<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\contracts;

use zni\modules\reportsAll\base\CommonService;

class ReportsContractService extends CommonService
{
    public function select(array $params): array
    {
        // $where = ['empl_reports.status = ?'];
        // $par = [ReportStatus::Approved->value];
        $where = [];
        $par = [];

        if (!empty($params['contract_date_from'])) {
            $where['contract_date_from'] = 'contracts.date_application >= ?';
            $par[] = date('Y-m-d', strtotime($params['contract_date_from']) ?: 0);
        }
        if (!empty($params['contract_date_to'])) {
            $where['contract_date_to'] = 'contracts.date_application <= ?';
            $par[] = date('Y-m-d', strtotime($params['contract_date_to']) ?: 0);
        }

        if (!empty($params['start_project_date'])) {
            $where['start_project_date'] = 'contracts.contract_date >= ?';
            $par[] = date('Y-m-d', strtotime($params['start_project_date']) ?: 0);
        }
        if (!empty($params['to_project_date'])) {
            $where['to_project_date'] = 'contracts.contract_date <= ?';
            $par[] = date('Y-m-d', strtotime($params['to_project_date']) ?: 0);
        }

        if (!empty($params['completion_date_from'])) {
            $where['completion_date_from'] = 'contracts.contract_term >= ?';
            $par[] = date('Y-m-d', strtotime($params['completion_date_from']) ?: 0);
        }
        if (!empty($params['completion_date_to'])) {
            $where['completion_date_to'] = 'contracts.contract_term <= ?';
            $par[] = date('Y-m-d', strtotime($params['completion_date_to']) ?: 0);
        }

        if (isset($params['region']) && (int) $params['region']) {
            $where['region'] = 'regions.region = ?';
            $par[] = (int)$params['region'];
        }
        if (isset($params['municipality']) && (int) $params['municipality']) {
            $where['municipality'] = 'municipalities.municipality = ?';
            $par[] = (int)$params['municipality'];
        }
        if (isset($params['city']) && (int) $params['city']) {
            $where['city'] = 'contracts.city = ?';
            $par[] = (int)$params['city'];
        }
        $kids = $params['kid'] ?? [];
        /** @var array $kids */
        $kids = array_filter($kids);
        if (count($kids)) {
            $placeholders = implode(',', array_fill(0, count($params['kid']), '?'));
            $where['kid'] = 'contract_kid.kid in (' . $placeholders . ')';
            $par = array_merge($par, $params['kid']);
        }

        if (
            isset($params['employment_reporting_period_from'])
            && (int) $params['employment_reporting_period_from']
        ) {
            $where['employment_reporting_period_from'] = 'reports.date_from >= ?';
            $par[] = $params['employment_reporting_period_from'] . '-01-01';
        }
        if (
            isset($params['employment_reporting_period_to'])
            && (int) $params['employment_reporting_period_to']
        ) {
            $where['employment_reporting_period_to'] = 'reports.date_to <= ?';
            $par[] = $params['employment_reporting_period_to'] . '-12-31';
        }

        if (
            isset($params['еmployment_maintenance_period_from'])
             && (int) $params['еmployment_maintenance_period_from']
        ) {
            $where['еmployment_maintenance_period_from'] = 'contracts.period_maintenance_start >= ?';
            $par[] = $params['еmployment_maintenance_period_from'] . '-01-01';
        }
        if (
            isset($params['еmployment_maintenance_period_to'])
            && (int) $params['еmployment_maintenance_period_to']
        ) {
            $where['еmployment_maintenance_period_to'] = 'contracts.period_maintenance_end <= ?';
            $par[] = $params['еmployment_maintenance_period_to'] . '-01-01';
        }

        if (!empty($params['application_date_from'])) {
            $where['application_date_from'] = 'contracts.date_application >= ?';
            $par[] = date('Y-m-d', strtotime($params['application_date_from']) ?: 0);
        }
        if (!empty($params['application_date_to'])) {
            $where['application_date_to'] = 'contracts.date_application <= ?';
            $par[] = date('Y-m-d', strtotime($params['application_date_to']) ?: 0);
        }

        if (!empty($params['contract_amount_from'])) {
            $where['contract_amount_from'] = 'contracts.period_value >= ?';
            $par[] = $params['contract_amount_from'];
        }
        if (!empty($params['contract_amount_to'])) {
            $where['contract_amount_to'] = 'contracts.period_value <= ?';
            $par[] = $params['contract_amount_to'];
        }

        return $this->db->all(
            "SELECT
                contracts.contract
                , contracts.cert_number
                , contracts.contract_term
                , contracts.cert_expire
                , contracts.cert_date
                , contracts.date_application
                , contracts.contract_date
                , contracts.contract_number
                , contracts.period_reporting
                , contracts.period_maintenance_start
                , contracts.period_maintenance_end
                , regions.name as region_name
                , municipalities.name as municipality_name
                , cities.name as city_name
                , string_agg(DISTINCT nom_kid.code || ' ' || nom_kid.name, ', ') as kid_names
                , companies.company_name
                , contracts.period_value
                , contracts.invest_amount
                , contracts.number_persons
            FROM
                contracts
            JOIN companies ON companies.company = contracts.company
            JOIN cities on cities.city = contracts.city
            JOIN municipalities on municipalities.municipality = cities.municipality
            JOIN regions on regions.region = municipalities.region
            JOIN reports on contracts.contract = reports.contract_id
            JOIN contract_kid on contracts.contract = contract_kid.contract
            JOIN nom_kid on contract_kid.kid = nom_kid.kid
            " . (count($where) ? " WHERE " . implode(" AND ", $where) : "")
            . " GROUP BY contracts.contract
                    , companies.company_name
                    , regions.name
                    , municipalities.name
                    , cities.name",
            $par
        );
    }

    public function columns(): array
    {
        return [
            'contract_number',
            'contract_date',
            'contract_term',
            'kid_names',
            'company_name',
            'city',
            'cert_number',
            'cert_expire',
            'period_value',
            'invest_amount',
            'number_persons',
            'period_reporting',
            'period_maintenance',
            'date_application',
        ];
    }
}
