<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\paymentRequest;

use zni\modules\reportsAll\base\CommonService;

class ReportsPaymentRequestService extends CommonService
{
    public function select(array $params): array
    {
        // $where = ['reports.status = ?'];
        // $par = [ReportStatus::Approved->value];

        $where = [];
        $par = [];

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
            isset($params['reporting_year'])
            && (int) $params['reporting_year']
        ) {
            $where['reporting_year'] = 'EXTRACT(YEAR FROM gs.year_date) = ?';
            $par[] = $params['reporting_year'];
        }

        return $this->db->all(
            "WITH yearly_payments AS (
                SELECT
                    contract,
                    EXTRACT(YEAR FROM payment_date) AS pay_year,
                    SUM(amount) AS total_paid
                FROM payments
                GROUP BY contract, pay_year
            )
            SELECT
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
                , EXTRACT(YEAR FROM gs.year_date) AS contract_year
                , COALESCE(yp.total_paid, 0) AS yearly_payment_sum
            FROM
                contracts
            JOIN companies ON companies.company = contracts.company
            JOIN cities on cities.city = contracts.city
            JOIN municipalities on municipalities.municipality = cities.municipality
            JOIN regions on regions.region = municipalities.region
            JOIN reports on contracts.contract = reports.contract_id
            JOIN contract_kid on contracts.contract = contract_kid.contract
            JOIN nom_kid on contract_kid.kid = nom_kid.kid
            CROSS JOIN LATERAL generate_series(
                date_trunc('year', contracts.contract_date),
                date_trunc('year', contracts.contract_term),
                '1 year'
            ) AS gs(year_date)
            LEFT JOIN yearly_payments yp ON yp.contract = contracts.contract
                AND yp.pay_year = EXTRACT(YEAR FROM gs.year_date) "
                . (count($where) ? " WHERE " . implode(" AND ", $where) : "")
                . " GROUP BY contracts.contract
                    , companies.company_name
                    , regions.name
                    , municipalities.name
                    , cities.name
                    , contract_year
                    , yp.total_paid
                ORDER BY contract_number, contract_year",
            $par
        );
    }

    public function columns(): array
    {
        return [
            'contract_number',
            'contract_date',
            'contract_term',
            'contract_year',
            'kid_names',
            'company_name',
            'city',
            'cert_number',
            'cert_expire',
            'period_value',
            'yearly_payment_sum',
            'invest_amount',
            'number_persons',
            'period_reporting',
            'period_maintenance',
            'date_application',
        ];
    }
}
