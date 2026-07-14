<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $mr
 * @property int $workplace_empl
 * @property ?string $comment
 * @property MaintenanceReportsEntity $maintenance_reports
 * @property WorkplaceEmplsEntity $workplace_empls
 */
class MaintenanceReportsEmployeesEntity extends Entity
{
}
