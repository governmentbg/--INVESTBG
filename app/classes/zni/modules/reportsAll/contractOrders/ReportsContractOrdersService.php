<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\contractOrders;

use zni\modules\reportsAll\base\CommonService;

class ReportsContractOrdersService extends CommonService
{
    public function select(array $params): array
    {
        $where = [];
        $par = [];

        if (!empty($params['order_date'])) {
            $where['order_date'] = 'co.order_date >= ?';
            $par[] = date('Y-m-d', strtotime($params['order_date']) ?: 0);
        }
        if (!empty($params['return_date'])) {
            $where['return_date'] = 'co.order_date_return <= ?';
            $par[] = date('Y-m-d', strtotime($params['return_date']) ?: 0);
        }

        return $this->db->all(
            'SELECT
                cs.company_name
                , c.contract_number
                , co.order_date as order_date
                , co.order_date_return as return_date
                , co.order_amount
                FROM contract_orders co
                JOIN contracts c ON c.contract = co.contract
                JOIN companies cs ON cs.company = c.company'
                . (count($where) ? " WHERE " . implode(' AND ', $where) : "")
                . ' ORDER BY co.order_date ASC',
            $par
        );
    }

    public function columns(): array
    {
        return [
            'company_name',
            'contract_number',
            'order_date',
            'return_date',
            'order_amount'
        ];
    }
}
