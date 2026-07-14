<?php

declare(strict_types=1);

namespace schema;

use vakata\database\schema\Entity;

/**
 * @property int $workplace
 * @property int $report_id
 * @property ?int $position_id
 * @property int $workplace_no
 * @property ?NomNkpdEntity $nom_nkpd
 * @property ReportsEntity $reports
 * @property \vakata\collection\Collection<int,WorkplaceEmplsEntity> $workplace_empls
 */
class WorkplacesEntity extends Entity
{
}
