<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $report_id
 * @property int $workplace_empl
 * @property string $comment
 * @property ?string $created_at
 * @property ?string $updated_at
 * @property WorkplaceEmplsEntity $workplace_empls
 * @property ReportsEntity $reports
 */
class ReportEmployeeCommentsEntity extends Entity
{
}
