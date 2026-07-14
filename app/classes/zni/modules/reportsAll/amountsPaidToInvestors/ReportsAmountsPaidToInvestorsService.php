<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\amountsPaidToInvestors;

use zni\modules\reportsAll\base\CommonService;

class ReportsAmountsPaidToInvestorsService extends CommonService
{
    public function select(array $params): array
    {
        $where = [];
        $par = [];

        if (!empty($params['company'])) {
            $where['company'] = 'cm.company = ?';
            $par[] = (int) $params['company'];
        }
        if (!empty($params['contract_date'])) {
            $where['contract_date'] = 'p.payment_date >= ?';
            $par[] = date('Y-m-d', strtotime($params['contract_date']) ?: 0);
        }
        if (!empty($params['end_date'])) {
            $where['contract_term'] = 'p.payment_date <= ?';
            $par[] = date('Y-m-d', strtotime($params['end_date']) ?: 0);
        }

        return $this->db->all(
            "SELECT
             p.*
            , c.contract_number
            , c.contract_date
            , c.contract_term
            , cm.company_name
            FROM payments p
            JOIN contracts c ON c.contract = p.contract
            JOIN companies cm ON cm.company = c.company
            " . (count($where) ? " WHERE " . implode(' AND ', $where) : ""),
            $par
        );
    }

    public function columns(): array
    {
        return [
            'company_name',
            'contract_number',
            'contract_date',
            'contract_term',
            'payment_date',
            'amount',
        ];
    }
}
