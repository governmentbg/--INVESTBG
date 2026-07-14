<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\base;

use zni\modules\reportsAll\base\BaseService;

class CommonService extends BaseService
{
    public function select(array $params): array
    {
        return [];
    }
    public function columns(): array
    {
        return [];
    }


    public function nomenc(string $type, int $id = 0): array
    {
        switch ($type) {
            case 'companies':
                return $this->db->all("SELECT company, company_name as name
                    FROM companies
                    ORDER BY company_name", null, 'company', true);
            case 'region':
                return $this->db->all("SELECT region, name
                    FROM regions
                    ORDER BY name", null, 'region', true);
            case 'municipalities':
                return $this->db->all(
                    "SELECT municipality, name
                    FROM municipalities
                    WHERE region = ?
                    ORDER BY name",
                    [$id],
                    'municipality',
                    true
                );
            case 'cities':
                return $this->db->all(
                    "SELECT city, name
                    FROM cities
                    WHERE type in (1,2)
                        and municipality = ?
                    ORDER BY name",
                    [$id],
                    'city',
                    true
                );
            case 'kid':
                return $this->db->all("SELECT kid, (code || ' ' || name) as name
                    FROM nom_kid
                    ORDER BY name", null, 'kid', true);
            case 'reporting_period':
                return $this->db->all("SELECT reporting_period, name, pos
                    FROM nom_reporting_periods
                    ORDER BY pos", null, 'reporting_period', true);
            case 'certificates':
                return $this->db->all(
                    "SELECT certificate_type, name
                    FROM nom_certificate_types
                    ORDER BY name",
                    null,
                    'certificate_type',
                    true
                );
            default:
                return [];
        }
    }

    public function yearsOptions(int $max): array
    {
        $currentYear = (int)date("Y");
        $yearsRange = range($currentYear - $max, $currentYear + $max);
        return array_combine($yearsRange, $yearsRange);
    }
}
