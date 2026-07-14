<?php

declare(strict_types=1);

namespace zni\modules\nomenc\sectorActivities;

use schema\NomSectorActivitiesEntity;
use vakata\collection\Collection;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDService<NomSectorActivitiesEntity>
 */
class SectorActivitiesService extends CRUDService
{
    /**
     * @return Collection<int|string, mixed>
     */
    public function getSectors(): Collection
    {
        return $this->db->tableMapped('nom_sectors')
            ->collection()
            ->pluckKeyVal('sector', 'name');
    }
}
