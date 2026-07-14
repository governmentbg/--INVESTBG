<?php

declare(strict_types=1);

namespace webadmin\modules\common\ekatte;

use schema\CitiesEntity;
use schema\MunicipalitiesEntity;
use schema\RegionsEntity;
use vakata\collection\Collection;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\user\User;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDService<CitiesEntity>
 */
class EkatteService extends CRUDService
{
    public function __construct(EkatteModule $module, DBInterface $db, User $user)
    {
        parent::__construct($module, $db, $user);
    }

    public function listQuery(): TableQueryMapped
    {
        return parent::listQuery()
            ->with('municipalities')
            ->with('municipalities.regions')
            ->sort('pos');
    }

    public function create(array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }

    public function update(mixed $id, array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }

    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }

    /**
     * @return Collection<int|string, array{region: int, code: string, name: string}>
     */
    public function getRegions(
        ?string $indexBy = 'region',
        ?string $orderBy = 'pos',
    ): Collection {
        $repo = $this->db->tableMapped('regions');

        if ($orderBy) {
            $repo->sort($orderBy);
        }

        return $repo->collection([ 'region', 'code', 'name' ])
            // @phpstan-ignore-next-line
            ->mapKey(fn (RegionsEntity $v, int $k): int|string => $indexBy ? $v->{$indexBy} : $k)
            // @phpstan-ignore-next-line
            ->map(fn (RegionsEntity $v) => [
                'region' => $v->region,
                'code'   => $v->code,
                'name'   => $v->name,
            ]);
    }

    /**
     * @return Collection<int|string, array{municipality: int, code: string, name: string}>
     */
    public function getMunicipalities(
        ?int $region = null,
        ?string $indexBy = 'municipality',
        ?string $orderBy = 'pos',
    ): Collection {
        $repo = $this->db->tableMapped('municipalities');

        if ($region) {
            $repo->filter('region', $region);
        }

        if ($orderBy) {
            $repo->sort($orderBy);
        }

        return $repo->collection([ 'municipality', 'code', 'name' ])
            // @phpstan-ignore-next-line
            ->mapKey(fn (MunicipalitiesEntity $v, int $k): int|string => $indexBy ? $v->{$indexBy} : $k)
            // @phpstan-ignore-next-line
            ->map(fn (MunicipalitiesEntity $v) => [
                'municipality' => $v->municipality,
                'code'         => $v->code,
                'name'         => $v->name,
            ]);
    }

    /**
     * @return Collection<int|string, array{city: int, name: string}>
     */
    public function getCities(
        ?int $municipality = null,
        ?string $indexBy = 'city',
        ?string $orderBy = 'pos',
    ): Collection {
        $repo = $this->db->tableMapped('cities');

        if ($municipality) {
            $repo->filter('municipality', $municipality);
        }

        if ($orderBy) {
            $repo->sort($orderBy);
        }

        return $repo->collection([ 'city', 'name', 'municipality' ])
            // @phpstan-ignore-next-line
            ->mapKey(fn (CitiesEntity $v, int $k): int|string => $indexBy ? $v->{$indexBy} : $k)
            // @phpstan-ignore-next-line
            ->map(fn (CitiesEntity $v) => [
                'city' => $v->city,
                'name' => $v->name,
            ]);
    }
}
