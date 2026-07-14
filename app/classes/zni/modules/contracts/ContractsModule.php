<?php

declare(strict_types=1);

namespace zni\modules\contracts;

use DateTime;
use vakata\intl\Intl;
use vakata\user\User;
use vakata\http\Request;
use vakata\config\Config;
use vakata\di\DIContainer;
use schema\ContractsEntity;
use vakata\http\Uri;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Field;
use webadmin\components\html\Table;
use webadmin\components\html\Button;
use zni\permission\PermissionService;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\PermissionsModuleInterface;
use zni\enums\ReportStatus;

/**
 * @extends CRUDModule<ContractsEntity,ContractsService>
 */
class ContractsModule extends CRUDModule implements PermissionsModuleInterface
{
    public const string NAME = 'contracts';

    public function __construct(
        DIContainer $container,
        protected Intl $intl,
        protected User $user,
        protected Config $config,
        protected PermissionService $permissionService,
        string $slug = ''
    ) {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'suitcase',
            'green',
            'contracts',
            'contracts',
            ContractsController::class,
            ContractsService::class,
            __DIR__ . '/views'
        );
    }

    public function canCreate(): bool
    {
        // if ($this->user->hasPermission('users/master')) {
        //     return true;
        // }

        $groups = $this->user->getGroups();
        $allowed = [
            $this->config->getInt('RESPONSIBLE_MIR'),
            $this->config->getInt('CHECKING_MIR_CONTRACT')
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
        // if ($this->user->hasPermission('users/master')) {
        //     return true;
        // }

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

    public function canDelete(): bool
    {
        return false;
    }

    public function canRead(): bool
    {
        return true;
    }

    public function hasHistory(): bool
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

    public function permissions(): array
    {
        return [
            'contracts/unlock'
        ];
    }

    public function listingDefaults(): array
    {
        return [];
    }

    public function listingCallback(Table $table): Table
    {
        $table = parent::listingCallback($table);
        $url = $this->container->instance(Uri::class);

        $table->removeColumn('contract_type');
        $table->removeColumn('company_type');
        $table->removeColumn('sector');
        $table->removeColumn('sector_activity');
        $table->removeColumn('date_bai');
        $table->removeColumn('cert_date');
        $table->removeColumn('cert_expire');
        $table->removeColumn('invest_amount');
        $table->removeColumn('number_persons');
        $table->removeColumn('date_application');
        $table->removeColumn('period_reporting');
        $table->removeColumn('period_maintenance_start');
        $table->removeColumn('period_maintenance_end');
        $table->removeColumn('period_invest_start');
        $table->removeColumn('period_invest_end');
        $table->removeColumn('period_value');
        $table->removeColumn('currency');
        $table->removeColumn('declaration');
        $table->removeColumn('files');
        $table->removeColumn('region');
        $table->removeColumn('municipality');
        $table->removeColumn('city');
        $table->removeColumn('kid');

        $table->addColumn(new TableColumn('contract_number'));

        $table->addColumn(
            (new TableColumn('reports'))
                ->setFilter(
                    (new Form())
                        ->addField(
                            new Field(
                                'text',
                                ['name' => 'reports',],
                                [
                                    'label'  => $this->name . '.reports',
                                ]
                            )
                        )
                )
                ->setMap(function (mixed $value, ContractsEntity $entity) use ($url): HTML {
                    $count = count($entity->reports ?? []);
                    $text  = $count . '/' . $entity->period_reporting;

                    if ($count === 0) {
                        return new HTML($text);
                    }

                    return new HTML(
                        '<a href="' . $url('reports?contracts.contract=' .
                            $entity->contract .
                            '') . '">' .
                            $text .
                            '</a>'
                    );
                })
        );

        $table
            ->getColumn('contract_date')
            ->addClass('left aligned')
            ->setMap(function (mixed $v) {
                return new HTML(
                    '<i class="ui clock icon"></i> ' .
                        (($temp = DateTime::createFromFormat('Y-m-d', $v)) ?
                            $temp->format('d.m.Y') : '')
                );
            });

        $table
            ->getColumn('contract_term')
            ->addClass('left aligned')
            ->setMap(function (mixed $v) {
                return new HTML(
                    '<i class="ui clock icon"></i> ' .
                        (($temp = DateTime::createFromFormat('Y-m-d', $v)) ?
                            $temp->format('d.m.Y') : '')
                );
            });

        $table
            ->getColumn('company')
            ->setMap(function (mixed $v, ContractsEntity $entity) {
                return $entity->companies->company_name ?? "";
            });

        $table
            ->getColumn('cert_type')
            ->addClass('left aligned')
            ->setMap(function (mixed $v, ContractsEntity $entity) {
                return new HTML(
                    '<span> ' . ($entity->nom_certificate_types->name ?? "") . ' </span> '
                );
            });

        $canCreateReportGroups = $this->permissionService->isINV();

        foreach ($table->getRows() as $v) {
            $operations = $v->getOperations(true);

            $data = $v->getData();

            $temp = [];

            if ($this->canUpdate()) {
                $temp['update'] = $operations['update']->show();
            }

            $temp['read'] = $operations['read']->show();

            $canCreateReport = false;
            $reportsCnt = count($data->reports ?? []);

            if ($data->period_reporting > $reportsCnt) {
                $canCreateReport = true;
            }

            // if (
            //     ($data->period_reporting === 1 && count($data->reports ?? []) === 0) ||
            //     ($data->period_reporting === 2 && count($data->reports ?? []) < 2) ||
            //     ($data->period_reporting === 3 && count($data->reports ?? []) < 3)
            // ) {
            //     $canCreateReport = true;
            // }

            $lastReport = null;

            foreach (($data->reports ?? []) as $r) {
                if (!$lastReport || $r->report_number > $lastReport->report_number) {
                    $lastReport = $r;
                }
            }

            if (
                $this->canCreate()
                && $reportsCnt
                && (int)$lastReport->status === ReportStatus::Approved->value
            ) {
                $temp['copyContract'] = (new Button('copyContract'))
                ->setLabel($this->name . '.operations.copyContract')
                ->setIcon('copy')
                ->setClass('skip mini yellow icon button')
                ->setAttr('href', $this->slug . '/copyContract/' . $data->contract);
            }

            if (
                $canCreateReport && $canCreateReportGroups
                && $reportsCnt
                && in_array((int)$lastReport->status, [ReportStatus::Approved->value, ReportStatus::Rejected->value])
            ) {
                $temp['emplReportCreate'] = (new Button('emplReportCreate'))
                    ->setLabel($this->name . '.operations.emplReportCreate')
                    ->setIcon('calculator')
                    ->setClass('skip mini green icon button')
                    ->setAttr('href', 'reports/createSub/' . $data->contract);
            } elseif ($canCreateReport && $canCreateReportGroups && $reportsCnt == 0) {
                $temp['emplReportCreate'] = (new Button('emplReportCreate'))
                    ->setLabel($this->name . '.operations.emplReportCreate')
                    ->setIcon('calculator')
                    ->setClass('skip mini green icon button')
                    ->setAttr('href', 'reports/create/' . $data->contract);
            }

            if ($reportsCnt) {
                $temp['emplReportList'] = (new Button('emplReportList'))
                    ->setLabel($this->name . '.operations.emplReportList')
                    ->setIcon('list')
                    ->setClass('skip mini blue icon button')
                    ->setAttr('href', 'reports?contracts.contract=' . $data->contract);
            }

            $v->setOperations($temp);
        }
        $table->setOrder([
            'contract_number',
            'company',
            'contract_date',
            'contract_term',
            'reports',
            'cert_number',
            'cert_type'
        ]);
        return $table;
    }

    public function formCallback(Form $form): Form
    {
        $form = parent::formCallback($form);
        $service = $this->getService();
        $data = $form->getContext('data', []);
        $form->removeField('contract');
        $entity = $form->getContext('entity');
        $layout = [];
        $intl = $this->intl;

        /** @var Request $request */
        $request = $this->container->get(Request::class);
        $body = (array) $request->getParsedBody();

        $form->addField(new Field(
            'hidden',
            ['name' => 'companyId'],
            []
        ));

        $segment2 = $request->getUrl()->getSegment(2);

        $contractTypes = $service->getContractTypes();
        $form->getField('contract_type')
            ->setType('select')
            ->setOption('values', ['' => $intl('please.select')]  + $contractTypes);

        $certificateTypes = $service->getCertificateTypes();
        $form->getField('cert_type')
            ->setType('select')
            ->setOption('values', ['' => $intl('please.select')]  + $certificateTypes);

        $companyTypes = $service->getCompanyTypes();
        $form->getField('company_type')
            ->setType('select')
            ->setOption('values', ['' => $intl('please.select')]  + $companyTypes);

        $form->getField('company')
            ->setType('select')
            ->setOption('values', ['' => $intl('please.select')]  + $service->getCompanies());

        $form->addField(new Field(
            'multipleselect',
            ['name' => 'kid'],
            [
                'label' => $this->slug . '.columns.kid',
                'values' => ['' => $intl('please.select.kid')] + $service->getKid()
            ]
        ));

        $form->addField(new Field(
            'select',
            ['name' => 'region', 'data-redraw' => 1],
            [
                'label' => $this->slug . '.columns.region',
                'values' =>  ['' => 'Изберете регион'] + $service->getRegions()
                    ->pluck('name')
                    ->toArray()
            ]
        ));

        $form->addField(new Field(
            'select',
            ['name' => 'municipality', 'data-redraw' => 1, 'disabled' => 'disabled'],
            [
                'label' => $this->slug . '.columns.municipality',
                'values' => (['' => 'Моля, първо изберете област']) +
                    $service->getMunicipalities((int) ($data['region'] ?? null))
                    ->pluck('name')
                    ->toArray()
            ]
        ));

        $form->getField('city')
            ->setType('select')
            ->setAttr('readonly', true)
            ->setOption('label', $this->slug . '.columns.city')
            ->setOption(
                'values',
                (
                    ['' => 'Моля, първо изберете община']) +
                    $service->getCities((int) ($data['municipality'] ?? null))
                    ->pluck('name')
                    ->toArray()
            )
            ->disable();

        $sectors = $service->getSectors();
        $form->getField('sector')
            ->setType('select')
            ->setAttr('data-redraw', true)
            ->setOption('values', ['' => $intl('please.select')]  + $sectors);

        $selectedSector = (int) $form->getField('sector')->getValue();

        $sectorActivities = $service->getSectorActivities($selectedSector);
        $form->getField('sector_activity')
            ->setType('select')
            ->setOption('values', ['' => $intl('please.select')]  + $sectorActivities);

        $reportingPeriod = $service->getReportingPeriod();
        $form->getField('period_reporting')
            ->setType('select')
            ->setOption('values', ['' => $intl('please.select')]  + $reportingPeriod);

        $form->getField('currency')
            ->setType('select')
            ->setOption('translate', 1)
            ->setOption('values', $this->getService()->currencies());

        $form->getField('contract_date')
            ->setAttr('data-redraw', true);

        $contractStartDate = $form->getField('contract_date')->getValue('');
        if (date('Y', strtotime($contractStartDate ?: 'now') ?: 0) > $this->config->getInt('MAX_YEAR_TO_BGN')) {
            $form->getField('currency')
                ->setOption('values', ['eur' => 'currency.eur'])
                ->disable();
        }

        $form->getField('period_value')
            ->setType('text')
            ->setAttr('class', 'currency-input');

        $form->getField('invest_amount')
            ->setType('text')
            ->setAttr('class', 'currency-input');

        $form->addField(new Field(
            'text',
            ['name' => 'period_value_alt'],
            ['label' => $this->slug . '.columns.period_value']
        ));
        $form->addField(new Field(
            'text',
            ['name' => 'invest_amount_alt'],
            ['label' => $this->slug . '.columns.invest_amount']
        ));

        $form->getField('declaration')
            ->setType('file')
            ->setOption('multiple', false)
            ->setOption('picker', false)
            ->setOption('types', 'pdf,doc,docx,jpeg,png')
            ->setOption('size', 1024 * 100 * 100);

        $form->getField('files')
            ->setType('files')
            ->setOption('picker', false)
            ->setOption('types', 'pdf,doc,docx,jpeg,png')
            ->setOption('size', 1024 * 100 * 100);

        $form->getField('cert_date')->setAttr('data-redraw', true);
        if (!empty($form->getField('cert_date')->getValue(''))) {
            $certExpireField = $form->getField('cert_expire');
            $cd = new DateTime($form->getField('cert_date')->getValue(''));
            $cd->modify("+ 3 years");
            $ceDate = $cd->format('Y-m-d');
            $certExpireField->setValue($ceDate);
            if (
                empty($data['cert_expire'])
                || strtotime($data['cert_expire']) != strtotime($certExpireField->getValue(''))
            ) {
                $data['cert_expire'] = $ceDate;
            }
        }

        if ($form->getContext('type', '') == 'create') {
            if (!empty($data) && isset($data['companyId'])) {
                $form->getField('company')->setValue($data['company']);
            } elseif (!empty($segment2) && ctype_digit((string)$segment2)) {
                $form->getField('company')->setValue($segment2);
            }

            if ($data['region'] ?? false) {
                $form->getField('municipality')->enable();
            }
            if ($data['municipality'] ?? false) {
                $form->getField('city')->enable();
            }
        }
        if ($form->getContext('type') === 'update' || $form->getContext('type') === 'read') {
            $form->getField('company')->disable();
            $form->getField('region')->disable();
            $form->getField('municipality')->disable();
            $form->getField('city')->disable();
            $form->getField('kid')->disable();

            $form->getField('company_type')->setAttr('disabled', true);
            $form->getField('date_application')->setAttr('disabled', true);
            $form->getField('sector')->setAttr('disabled', true);
            $form->getField('sector_activity')->setAttr('disabled', true);
            $form->getField('cert_date')->setAttr('disabled', true);
            $form->getField('cert_expire')->setAttr('disabled', true);
            $form->getField('cert_number')->setAttr('disabled', true);
            $form->getField('cert_type')->setAttr('disabled', true);
            $form->getField('contract_term')->disable();
            $form->getField('number_persons')->disable();

            if (count($entity->reports) > 0) {
                $form->getField('period_reporting')->setAttr('disabled', true);
            }
        }

        if ($form->getContext('type') === 'history') {
            if ($entity && ($entity->reports > 0)) {
                $form->getField('contract_number')->disable();
                $form->getField('contract_date')->disable();
                $form->getField('period_reporting')->disable();
                $form->getField('period_maintenance_start')->disable();
                $form->getField('period_maintenance_end')->disable();
                $form->getField('period_invest_start')->disable();
                $form->getField('period_invest_end')->disable();
                $form->getField('period_value')->disable();
                $form->getField('invest_amount')->disable();
                $form->getField('number_persons')->disable();
                $form->getField('declaration')->disable();
                $form->getField('files')->disable();
                $form->getField('region')->disable();
                $form->getField('municipality')->disable();
                $form->getField('city')->disable();
                $form->getField('kid')->disable();
            }
        }

        #region ORDER

        $orderForm = (new Form())
            ->addField(new Field(
                'date',
                ['name' => 'order_date'],
                ['label' => $this->slug . '.columns.order.order_date']
            ))
            ->addField(new Field(
                'date',
                ['name' => 'order_date_return'],
                ['label' => $this->slug . '.columns.order.order_date_return']
            ))
            ->addField(new Field(
                'text',
                ['name' => 'order_amount'],
                ['label' => $this->slug . '.columns.order.order_amount']
            ))
            ->setLayout([
                ['order_date', 'order_date_return'],
                ['order_amount']
            ]);

        $orderForm->getValidator()
            ->required('order_date')
            ->required('order_date_return')
            ->required('order_amount');

        $form->addField(
            new Field(
                'jsoncustom',
                ['name' => 'order'],
                ['form' => $orderForm]
            )
        );

        if ($form->getContext('type') !== 'history') {
            if ($entity && count($entity->reports) > 0) {
                $form->getField('order')
                    ->setOption('delete', true)
                    ->setOption('add', true);
            }
        }

        #endregion

        #region BANK
        // phpcs:disable Generic.Files.LineLength
        $bankForm = (new Form())
            ->addField(new Field(
                'date',
                ['name' => 'bank_date_from'],
                ['label' => $this->slug . '.columns.bank.date_from']
            ))
            ->addField(new Field(
                'date',
                ['name' => 'bank_date_to'],
                ['label' => $this->slug . '.columns.bank.date_to']
            ))
            ->addField(new Field(
                'text',
                ['name' => 'bank_amount'],
                ['label' => $this->slug . '.columns.bank.amount']
            ))
            ->addField(new Field(
                'text',
                ['name' => 'bank_name'],
                ['label' => $this->slug . '.columns.bank.name']
            ))
            ->setLayout([
                ['bank_date_from', 'bank_date_to'],
                ['bank_amount', 'bank_name']
            ]);

        $bankForm->getValidator()
            ->required('bank_date_from')
            ->required('bank_date_to')
            ->required('bank_amount');

        $form->addField(
            new Field(
                'jsoncustom',
                ['name' => 'bank'],
                ['form' => $bankForm]
            )
        );
        if (!$service->isMIR()) {
            $form->getField('order')->disable();
            $form->getField('bank')->disable();
        }

        if ($form->getContext('type') !== 'history') {
            if ($entity && count($entity->reports) > 0) {
                $form->getField('bank')
                    ->setOption('delete', true)
                    ->setOption('add', true);
            }
        }
        #endregion

        $contractUsers = $service->getContractUsers();
        $form->addField(new Field(
            'multipleselect',
            ['name' => 'users[]'],
            ['values' => $contractUsers]
        ));
        if (in_array($form->getContext('type'), ['history', 'read'])) {
            $form->getField('users[]')->show()
                ->disable();
        }

        $validator = $form->getValidator();
        $validator
            ->required('contract_type')
            ->required('bulstat')
            ->required('company')
            ->required('company_type')
            ->required('date_application')
            ->required('sector')
            ->required('sector_activity')
            ->required('cert_date')
            ->required('cert_expire')
            ->required('cert_number')
            ->required('cert_type')
            ->required('contract_number')
            ->required('contract_date')
            ->required('contract_term')
            ->required('period_reporting')
            ->required('period_value')
            ->required('invest_amount')
            ->required('number_persons')
            ->required('declaration', $intl('declarations.is.required'))
            ->required('contract_users')
            ->required('kid', $intl('field.required'))
            ->required('region', $intl('field.required'))
            ->required('municipality', $intl('field.required'))
            ->required('city', $intl('field.required'))
            ->optional('contract_term')
                ->minDateRelation(
                    'contract_date',
                    null,
                    'reports.error.contract_term_before_contract_date'
                );
        // ->required('order_date')
        // ->required('order_date_return')
        // ->required('order_amount')
        // ->required('bank_date_from')
        // ->required('bank_date_to')
        // ->required('bank_amount');

        if ($form->getContext('type') === 'update') {
            $validator = $form->getValidator();
            $validator->remove('company')
                ->remove('company_type')
                ->remove('sector')
                ->remove('sector_activity')
                ->remove('date_application')
                ->remove('date_bai')
                ->remove('cert_date')
                ->remove('cert_expire')
                ->remove('cert_number')
                ->remove('period_reporting')
                ->remove('declaration')
                ->remove('cert_type')
                ->remove('kid')
                ->remove('region')
                ->remove('municipality')
                ->remove('city');
        }


        $layout[] = 'text.new.contract';
        $layout[] = ['contract'];
        $layout[] = ['contract_type'];
        $layout[] = 'text.company';
        $layout[] = ['company', 'company_type'];
        $layout[] = ['kid'];
        $layout[] = ['region', 'municipality', 'city'];
        $layout[] = 'text.data.contract';
        $layout[] = ['date_application', 'sector', 'sector_activity'];
        $layout[] = ['cert_date', 'cert_expire', 'cert_number', 'cert_type'];
        $layout[] = ['contract_number', 'contract_date', 'contract_term'];
        $layout[] = ['period_reporting:2', 'currency:2', 'period_value:3', 'invest_amount:5', 'number_persons:4'];
        $layout[] = ['period_maintenance_start', 'period_maintenance_end', 'period_invest_start', 'period_invest_end'];
        $layout[] = ['declaration'];
        $layout[] = 'text.orders';
        $layout[] = ['order'];
        $layout[] = 'text.bank.warranty';
        $layout[] = ['bank'];
        $layout[] = 'text.files';
        $layout[] = ['files'];
        $layout[] = 'text.contract_users';
        $layout[] = ['users[]'];
        $layout[] = 'text.related_reports';

        if ($entity && $entity->reports) {
            foreach ($entity->reports as $rIndex => $report) {
                $reportId = (int)$report->report;
                $layout[] = 'acc:Отчет № ' . $reportId;

                $form->addField(new Field('text', [
                    'name' => "rep_{$reportId}[report]",
                    'disabled' => 'disabled',
                    'value' => $report->report
                ], [
                    'label' => 'Отчет № ' . $reportId,
                ]));

                $form->addField(new Field('text', [
                    'name' => "rep_{$reportId}[date_from]",
                    'disabled' => 'disabled',
                    'value' => date('d.m.Y', strtotime($report->date_from) ?: 0)

                ], ['label' => $intl('report.start_date'), 'translate' => true]));

                $form->addField(new Field('text', [
                    'name' => "rep_{$reportId}[date_to]",
                    'disabled' => 'disabled',
                    'value' => date('d.m.Y', strtotime($report->date_to) ?: 0)

                ], [
                    'label' => $intl('report.end_date'), 'translate' => true
                ]));

                $form->addField(new Field('text', [
                    'name' => "rep_{$reportId}[status]",
                    'disabled' => 'disabled',
                    'value' => ReportStatus::tryFrom($report->status)?->label()

                ], ['label' => $intl('status')]));

                $form->addField(new Field('text', [
                    'name' => "rep_{$reportId}[workplaces]",
                    'disabled' => 'disabled',
                    'value' => $report->workplaces

                ], ['label' => $intl('workplaces')]));
                $layout[] = ["rep_{$reportId}[report]", "rep_{$reportId}[date_from]", "rep_{$reportId}[date_to]", "rep_{$reportId}[status]", "rep_{$reportId}[workplaces]"];
            }
        }
        $form->setLayout($layout);

        if ($entity) {
            $form->populate($service->toArray($entity));
        }

        if (isset($data['kid']) && !is_array($data['kid'])) {
            $data['kid'] = (array)$data['kid'];
        }

        $form->populate($data);
        $form->setValidator($validator);

        return $form;
    }
}
