<?php

declare(strict_types=1);

namespace zni\modules\companies;

use vakata\intl\Intl;
use vakata\user\User;
use vakata\config\Config;
use vakata\di\DIContainer;
use schema\CompaniesEntity;
use vakata\database\DB;
use webadmin\components\html\Form;
use webadmin\components\html\Field;
use webadmin\components\html\Table;
use webadmin\components\html\Button;
use webadmin\modules\common\crud\CRUDModule;

/**
 * @extends CRUDModule<\schema\CompaniesEntity,CompaniesService>
 */
class CompaniesModule extends CRUDModule
{
    public const string NAME = 'companies';
    public function __construct(
        DIContainer $container,
        protected User $user,
        protected Intl $intl,
        protected Config $config,
        string $slug = '',
    ) {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'users',
            'teal',
            '',
            'companies',
            CompaniesController::class,
            namespace\CompaniesService::class,
            __DIR__ . '/views'
        );
    }

    public function hasHistory(): bool
    {
        return true;
    }
    public function canCreate(): bool
    {
        $groups = $this->user->getGroups();
        $allowed = [
            $this->config->getInt('RESPONSIBLE_MIR'),
            $this->config->getInt('CHECKING_MIR_CONTRACT'),
        ];

        foreach ($allowed as $gid) {
            if (array_key_exists($gid, $groups)) {
                return true;
            }
        }

        return false;
    }

    public function canUpdate(): bool
    {
        if ($this->getService()->isModerator()) {
            return true;
        }

        if ($this->getService()->permissions()) {
            return true;
        }

        // $groups = $this->user->getGroups();
        // $allowed = [
        //     $this->config->getInt('RESPONSIBLE_MIR')
        // ];

        // foreach ($allowed as $gid) {
        //     if (array_key_exists($gid, $groups)) {
        //         return true;
        //     }
        // }

        return false;
    }

    public function canRead(): bool
    {
        return true;
    }
    public function canDelete(): bool
    {
        return false;
    }

    public function formCallback(Form $form): Form
    {
        $form = parent::formCallback($form);
        $intl = $this->intl;
        $service = $this->getService();
        $data =  $form->getContext('data', null);
        $entity = $form->getContext('entity', null);
        $form->removeField('company');
        $form->removeField('created');

        $form->getField('id_type')->setType('select')
            ->setOption('values', [1 => 'БУЛСТАТ/ЕИК']);
        $form->getField('id');
        $form->getField('company_name')
            ->setAttr('readonly', true);

        $maxUser = $this->config->getInt('MAX_COMPANY_USERS');
        if ($entity) {
            $maxUser -= count($entity->company_egns);
        }

        $form->addField(
            (new Field(
                'json',
                ['name' => 'companyUser'],
                ['label' => $intl('company.create.person.with.access.info')]
            ))
            ->setOption('min', 1)
            ->setOption('max', 5)
            ->setOption('new', $maxUser)
            ->setOption(
                'form',
                (new Form())
                    ->addField(new Field(
                        'text',
                        ['name' => 'egn', 'maxlength' => 10],
                        ['label' => $intl('egn / lnch')]
                    ))
                    ->addField(new Field(
                        'text',
                        ['name' => 'name'],
                        ['label' => $intl('name.acc')]
                    ))
                    ->addField(new Field(
                        'checkbox',
                        ['name' => 'moderator'],
                        ['label' => $intl('moderator.acc')]
                    ))
            )
        );

        $form->addField(
            new Field(
                'text',
                ['name' => 'check_count', 'data-redraw' => true],
                []
            )
        );

        $form->getField('company_region')
            ->setType('select')
            //->setAttr('readonly', true)
            ->setAttr('data-redraw', true)
            ->setOption('label', $this->slug . '.columns.company_region')
            ->setOption('values', ['' => 'Изберете регион'] + $service->getRegions()
                ->pluck('name')
                ->toArray());

        $form->getField('company_municipality')
            ->setType('select')
            //->setAttr('readonly', true)
            ->setAttr('data-redraw', true)
            ->setOption('label', $this->slug . '.columns.company_municipality')
            ->setOption('values', (['' => 'Моля, първо изберете област']) +
                $service->getMunicipalities((int) ($data['region'] ?? null))
                ->pluck('name')
                ->toArray());


        $form->getField('company_city')
            ->setType('select')
            //->setAttr('readonly', true)
            ->setAttr('data-redraw', true)
            ->setOption('label', $this->slug . '.columns.company_city')
            ->setOption(
                'values',
                (
                    ['' => 'Моля, първо изберете община']) +
                    $service->getCities((int) ($data['company_municipality'] ?? null))
                    ->pluck('name')
                    ->toArray()
            );

        if (!empty($data) && isset($data['check_count']) && ((int)$data['check_count'] === 1)) {
            $form->getField('id')->setAttr('readonly', true);
            $form->getField('company_name')->setAttr('readonly', true);
        }

        if ($form->hasValidator()) {
            $validator = $form->getValidator();
            $validator->remove('created');
            if ($this->getService()->isMIR()) {
                $validator->required('company_name')
                    ->required('company_region')
                    ->required('company_municipality')
                    ->required('company_city');
            }

            if ($this->getService()->isINV()) {
                $validator->remove('id_type');
                $validator->remove('id');
            }

            $validator->optional('companyUser.*.egn')
                ->bgIDN($intl->get('companies.validation.egn_lnc'))
                ->optional('companyUser.*.name')
                ->required('companyUser.*.egn');
        }
        if ($this->getService()->isINV()) {
            $form->getField('id_type')->disable();
            $form->getField('id')->disable();
            $form->getField('company_name')->disable();
            $form->getField('company_region')->disable();
            $form->getField('company_municipality')->disable();
            $form->getField('company_city')->disable();
            $form->getField('company_address')->disable();
            $form->getField('company_email')->disable();
        }

        $layout = [];
        $layout[] = 'company.info';
        $layout[] = ['id_type', 'id'];
        $layout[] = ['company_name'];
        $layout[] = ['company_region', 'company_municipality', 'company_city'];
        $layout[] = ['company_address', 'company_email'];
        $layout[] = 'persons.with.access';
        $layout[] = ['companyUser'];

        $form->setLayout($layout);

        if ($entity) {
            $form->populate($this->getService()->toArray($entity));
        }

        $form->populate($form->getContext('data', []));

        return $form;
    }

    public function listingCallback(Table $table): Table
    {
        $table =  parent::listingCallback($table);

        $table->removeColumn('id_type');
        $table->removeColumn('created');

        $table->getColumn('company_city')
            ->setMap(
                function (mixed $v, CompaniesEntity $entity): string {
                    return $entity->cities->name ?? "";
                }
            );

        $table->getColumn('company_municipality')
            ->setMap(
                function (mixed $v, CompaniesEntity $entity): string {
                    return $entity->municipalities->name ?? "";
                }
            );

        $table->getColumn('company_region')
            ->setMap(
                function (mixed $v, CompaniesEntity $entity): string {
                    return $entity->regions->name ?? "";
                }
            );

        foreach ($table->getRows() as $v) {
            $operations = $v->getOperations(true);

            $data = $v->getData();
            $temp = [];

            $temp['read'] = $operations['read']->show();

            if ($this->getService()->permissions()) {
                $temp['update'] = $operations['update']->show();
                $temp['history'] = $operations['history']->show();
            } elseif ($this->getService()->isModerator()) {
                $temp['update'] = $operations['update']->show();
            }

            if (count($data->contracts ?? [])) {
                $temp['company_contracts'] = (new Button('companyContracts'))
                    ->setLabel($this->name . '.operations.companyContracts')
                    ->setIcon('list')
                    ->setClass('skip mini blue icon button')
                    ->setAttr('href', 'contracts?companies.company=' . $data->company);
            }

            if ($this->canCreate()) {
                $temp['create_contract'] = (new Button('createContract'))
                    ->setLabel($this->name . '.operations.createContract')
                    ->setIcon('plus')
                    ->setClass('skip mini green icon button')
                    ->setAttr('href', 'contracts/create/' . $data->company);
            }

            $v->setOperations($temp);
        }

        return $table;
    }
}
