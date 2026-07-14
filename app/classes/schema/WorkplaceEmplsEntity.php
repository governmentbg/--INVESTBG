<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $workplace_empl
 * @property int $workplace_id
 * @property int $employee_id
 * @property ?string $start_date
 * @property ?string $end_date
 * @property ?int $refund_sum
 * @property ?int $salary_amount
 * @property ?string $project_start_date
 * @property ?int $not_empl_report
 * @property ?string $last_amend_date
 * @property ?string $reason
 * @property ?string $eco_code
 * @property ?string $profession
 * @property ?string $ekatte
 * @property ?string $last_term
 * @property ?int $sync_status
 * @property ?string $last_sync
 * @property ?int $type_expense
 * @property EmployeesEntity $employees
 * @property ?NomTypeExpenseEntity $nom_type_expense
 * @property WorkplacesEntity $workplaces
 * @property \vakata\collection\Collection<int,EmployeeSalaryEntity> $employee_salary
 * @property \vakata\collection\Collection<int,ReportEmployeeCommentsEntity> $report_employee_comments
 * @property \vakata\collection\Collection<int,MaintenanceReportsEmployeesEntity> $maintenance_reports_employees
 */
class WorkplaceEmplsEntity extends Entity
{
}
