<?php

namespace zni\modules\companies;

use vakata\intl\Intl;
use vakata\user\User;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\collection\Collection;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use zni\modules\nomenc\nomenc\NomencService;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDServiceVersioned;
use webadmin\modules\common\ekatte\EkatteService;
use zni\permission\PermissionService;

/**
 * @extends CRUDServiceVersioned<\schema\CompaniesEntity>
 */
class CompaniesService extends CRUDServiceVersioned
{
    public function __construct(
        CompaniesModule $module,
        DBInterface $db,
        User $user,
        protected Intl $intl,
        protected Config $config,
        protected NomencService $nomencService,
        protected EkatteService $ekatteService,
        protected PermissionService $permissionService
    ) {
        parent::__construct($module, $db, $user);
    }
    public function name(Entity $entity): string
    {
        return $entity->company_name ?? "";
    }
    public function create(array $data = []): Entity
    {

        $check = $this->db->one('SELECT company from companies where id = ?', $data['id']);
        if ($check) {
            return  throw new CRUDException('Този ЕИК е регистриран в системта с ИД: ' . $check);
        }
        $data['created'] = date('Y-m-d H:i:s');
        $entity = parent::create($data);

        $this->db->query("DELETE FROM company_egns WHERE company = ?", $entity->company);

        $companyUser = $data['companyUser'] ?? [];
        $egn = [];
        foreach ($companyUser as $row) {
            $row['egn'] = trim($row['egn']);
            if (in_array($row['egn'], $egn)) {
                continue;
            }

            if (strlen($row['egn'])) {
                $this->db->query("INSERT INTO company_egns(company, egn,name, moderator)
                 VALUES (?,?,?,?)", [$entity->company, $row['egn'], $row['name'], $row['moderator']]);
            }
        }

        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        if (!$this->permissions() && !$this->isModerator()) {
            throw new CRUDException('Not allowed');
        }

        $entity = $this->read($id);
        $data['id_type'] = $entity->id_type;
        $data['created'] = $entity->created;
        $entity = parent::update($id, $data);

        $this->db->query("DELETE FROM company_egns WHERE company = ?", $entity->company);

        $companyUser = $data['companyUser'] ?? [];
        $egn = [];

        foreach ($companyUser as $row) {
            $row['egn'] = trim($row['egn']);
            if (in_array($row['egn'], $egn)) {
                continue;
            }

            if (strlen($row['egn'])) {
                $this->db->query("INSERT INTO company_egns(company, egn,name, moderator)
                 VALUES (?,?,?,?)", [$entity->company, $row['egn'], $row['name'], $row['moderator']]);
            }
            $egn[] = $row['egn'];
        }
        return $entity;
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $data = parent::toArray($entity, $relations);
        $companyUser = [];
        foreach ($entity->company_egns as $row) {
            /** @psalm-suppress PossiblyNullPropertyFetch */
            $companyUser[] = ['egn' => $row->egn, 'name' => $row->name, 'moderator' => $row->moderator];
        }
        $data['companyUser'] = json_encode($companyUser);
        return $data;
    }
    protected function entities(): TableQueryMapped
    {
        $entities = parent::entities();

        if ($this->permissions()) {
            return $entities;
        }
        $egn = $this->user->getData()['egn'];
        $entities = $entities->filter('company_egns.egn', $egn);
        return $entities;
    }
    public function listQuery(): TableQueryMapped
    {
        return parent::listQuery();
    }
    public function delete(mixed $id): void
    {
        throw new CRUDException();
    }
    public function permissions(): bool
    {
        $allowGroups = [
            $this->config->getInt('MASTER_MIR'),
            $this->config->getInt('RESPONSIBLE_MIR'),
            $this->config->getInt('CHECKING_MIR_CONTRACT'),
            $this->config->getInt('ADMIN_MIR'),
            $this->config->getInt('GROUP_ADMINS'),
            // $this->config->getInt('NORMAL_INV'),
            // $this->config->getInt('ADMIN_INV'),
        ];

        $userGroups = array_keys($this->user->getGroups());
        return (bool) array_intersect($allowGroups, $userGroups);
    }

    public function checkEIK(string $eik): array
    {
        return $this->nomencService->checkEIK($eik);
    }
    /**
     * @return Collection<int|string, array{region: int, code: string, name: string}>
     */
    public function getRegions(): Collection
    {
        return $this->ekatteService->getRegions();
    }
    /**
     * @return Collection<int|string, array{municipality: int, code: string, name: string}>
     */
    public function getMunicipalities(?int $region = null): Collection
    {
        return $this->ekatteService->getMunicipalities($region);
    }
    /**
     * @return array
     */
    public function getMunicipalitiesAndRegionByCity(int $selectedCity): array
    {
        return $this->nomencService->getMunicipalitiesAndRegionByCity($selectedCity);
    }

    /**
     * @return Collection<int|string, array{city: int, name: string}>
     */
    public function getCities(?int $municipality = null): Collection
    {
        return $this->ekatteService->getCities($municipality);
    }
    public function isINV(): bool
    {
        return $this->permissionService->isINV();
    }
    public function isMIR(): bool
    {
        return $this->permissionService->isMIR();
    }

    public function isModerator(): bool
    {
        if (!$this->user->auth) {
            return false;
        }
        $details = $this->user->auth[0]['details'];
        $details = json_decode($details, true);
        $egn = $details['egn'];
        $isModerator = $this->db->one('SELECT moderator from company_egns where egn = ? and moderator = 1', $egn);

        if ($isModerator) {
            return true;
        }
        return false;
    }
}
