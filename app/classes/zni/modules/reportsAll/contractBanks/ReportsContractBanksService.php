<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\contractBanks;

use zni\modules\reportsAll\base\CommonService;

class ReportsContractBanksService extends CommonService
{
    public function select(array $params): array
    {
        $where = [];
        $par = [];

        if (!empty($params['from_date'])) {
            $where['from_date'] = 'cb.bank_date_from >= ?';
            $par[] = date('Y-m-d', strtotime($params['from_date']) ?: 0);
        }
        if (!empty($params['to_date'])) {
            $where['to_date'] = 'cb.bank_date_to <= ?';
            $par[] = date('Y-m-d', strtotime($params['to_date']) ?: 0);
        }
        if (!empty($params['bank'])) {
            $where['bank'] = 'cb.bank_name ilike ?';
            $par[] = '%' . $params['bank'] . '%';
        }

        return $this->db->all(
            'SELECT
                cs.company_name
                , c.contract_number
                , cb.bank_date_from as from_date
                , cb.bank_date_to as to_date
                , cb.bank_amount as amount
                , cb.bank_name
                FROM contract_banks cb
                JOIN contracts c ON c.contract = cb.contract
                JOIN companies cs ON cs.company = c.company'
                . (count($where) ? " WHERE " . implode(' AND ', $where) : "")
                . ' ORDER BY cb.bank_date_from ASC',
            $par
        );
    }

    public function columns(): array
    {
        return [
            'company_name',
            'contract_number',
            'from_date',
            'to_date',
            'amount',
            'bank_name'
        ];
    }
}
