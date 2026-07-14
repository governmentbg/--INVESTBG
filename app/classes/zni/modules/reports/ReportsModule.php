<?php

declare(strict_types=1);

namespace zni\modules\reports;

use DateTime;
use DateTimeImmutable;
use schema\ReportsEntity;
use schema\WorkplacesEntity;
use vakata\config\Config;
use vakata\di\DIContainer;
use vakata\http\Request;
use vakata\http\Uri;
use vakata\intl\Intl;
use vakata\user\User;
use webadmin\components\html\Button;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDModule;
use zni\enums\ReportStatus;
use zni\modules\reports\ReportsService;
use zni\permission\PermissionService;

/**
 * @extends CRUDModule<ReportsEntity,ReportsService>
 */
class ReportsModule extends CRUDModule
{
    public const string NAME = 'reports';

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
            'bullhorn',
            'yellow',
            'contracts',
            'reports',
            ReportsController::class,
            ReportsService::class,
            __DIR__ . '/views'
        );
    }

    public function canCreate(): bool
    {
        $uri = $this->container->instance(\vakata\http\Uri::class);
        $id = $uri->getSegment(2);

        if (!$id) {
            return false;
        }

        return $this->getService()->canReportAccess((int)$id);
    }

    public function canUpdate(): bool
    {
        $uri = $this->container->instance(\vakata\http\Uri::class);
        $id = $uri->getSegment(2);


        if ($this->getService()->isMIR()) {
            return false;
        }
        if (!$id) {
            return true;
        }

        $entity = $this->getService()->read($id);

        if ((int)$entity->locked === 1) {
            return false;
        }
        if (
            $entity->status !== ReportStatus::Draft->value &&
            $entity->status !== ReportStatus::ReturnedForCorrection->value
        ) {
            return false;
        }

        return true;
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

    public function formCallback(Form $form): Form
    {
        $service = $this->getService();
        $form = parent::formCallback($form);
        $entity = $form->getContext('entity');
        $layout = [];
        $intl = $this->intl;

        $form->removeField('contract_id');
        $form->removeField('report_number');
        /** @var Request $request */
        $request = $this->container->get(Request::class);
        $url = $this->container->get(Uri::class);
        $body = (array) $request->getParsedBody();

        $segment2 = $request->getUrl()->getSegment(2);

        $form->removeField('locked');

        $form->addField(new Field(
            'hidden',
            ['name' => 'hiddenContract', 'value' => $segment2],
            []
        ));

        $form->getField('status')
            ->setType('select')
            ->setOption('values', ReportStatus::options())
            ->setAttr('disabled', true);

        $form->getField('workplaces')
            ->setType('number')
            ->setAttr('min', 0)
            ->setAttr('step', 1);

        if ($form->getContext('type') === 'create') {
            $data = $service->getContract((int)$segment2);
            $reports = $data['reports'] ?? [];
            $contract = $data['contract'] ?? [];

            $form->getField('status')->setValue(ReportStatus::Draft->value);

            $form->getField('date_to')
                ->setType('date');

            if ($contract[0]['period_reporting'] === 1) {
                //ako contract report === 1 - vzemam datite za otcheta ot report
                $setDateField = function (string $fieldName, string $hiddenName, string $date) use ($form): void {
                    $formatted = (new \DateTime($date))->format('d.m.Y');

                    $form->getField($fieldName)
                        ->setValue($formatted)
                        ->disable();

                    $form->addField(new Field(
                        'hidden',
                        ['name' => $hiddenName, 'value' => $formatted],
                        []
                    ));
                };

                $setDateField('date_from', 'hidden_date_from', $contract[0]['contract_date']);
                // $setDateField('date_to', 'hidden_date_to', $contract[0]['contract_term']);
            } elseif ($contract[0]['period_reporting'] === 2) { // ako contract report ===2
                if (!$reports) {
                    // ako nqma report - zadavam nachalna ot dogovora i krainata e plavashta (max- srok na dogovor)
                    $form->getField('date_from')
                        ->setType('date')
                        ->setValue($contract[0]['contract_date'])
                        ->setOption('maxDate', (new \DateTime($contract[0]['contract_term']))->format('d.m.Y'))
                        ->setOption('minDate', (new \DateTime($contract[0]['contract_date']))->format('d.m.Y'))
                        ->disable();

                    $form->addField(new Field(
                        'hidden',
                        ['name' => 'hidden_date_from', 'value' => $contract[0]['contract_date']],
                        []
                    ));

                    $form->getField('date_to')
                        ->setType('date')
                        // ->setOption('maxDate', (new \DateTime($contract[0]['contract_term']))->format('d.m.Y'))
                        ->setOption('minDate', (new \DateTime($contract[0]['contract_date']))->format('d.m.Y'));
                } else { // ако има, вземам data от предния отчет + 1 ден и слагам крайната да е от договора
                    $dateFrom = (new \DateTime($reports[0]['date_to']))
                        ->modify('+1 day')
                        ->format('d.m.Y');

                    $setDateField = function (string $fieldName, string $hiddenName, string $date) use ($form): void {
                        $formatted = (new \DateTime($date))->format('d.m.Y');

                        $form->getField($fieldName)
                            ->setValue($formatted)
                            ->disable();

                        $form->addField(new Field(
                            'hidden',
                            ['name' => $hiddenName, 'value' => $formatted],
                            []
                        ));
                    };

                    $setDateField('date_from', 'hidden_date_from', $dateFrom);
                    // $setDateField('date_to', 'hidden_date_to', $contract[0]['contract_term']);
                }
            } elseif ($contract[0]['period_reporting'] === 3) { // ako dogovra e na 3 otchitaniq
                if (!$reports) {
                    // ako nqma report - zadavam nachalna ot dogovora i krainata e plavashta (max- srok na dogovor)
                    $form->getField('date_from')
                        ->setType('date')
                        ->setValue($contract[0]['contract_date'])
                        ->setOption('maxDate', (new \DateTime($contract[0]['contract_term']))->format('d.m.Y'))
                        ->setOption('minDate', (new \DateTime($contract[0]['contract_date']))->format('d.m.Y'))
                        ->disable();

                    $form->addField(new Field(
                        'hidden',
                        ['name' => 'hidden_date_from', 'value' => $contract[0]['contract_date']],
                        []
                    ));

                    $form->getField('date_to')
                        ->setType('date')
                        ->setOption('maxDate', (new \DateTime($contract[0]['contract_term']))->format('d.m.Y'))
                        ->setOption('minDate', (new \DateTime($contract[0]['contract_date']))->format('d.m.Y'));
                } else { // ако има, вземам data от предния отчет
                    $dateFrom = (new \DateTime($reports[0]['date_to']))
                        ->modify('+1 day')
                        ->format('d.m.Y');

                    $setDateField = function (string $fieldName, string $hiddenName, string $date) use ($form): void {
                        $formatted = (new \DateTime($date))->format('d.m.Y');

                        $form->getField($fieldName)
                            ->setValue($formatted)
                            ->disable();

                        $form->addField(new Field(
                            'hidden',
                            ['name' => $hiddenName, 'value' => $formatted],
                            []
                        ));
                    };

                    $setDateField('date_from', 'hidden_date_from', $dateFrom);
                    $setDateField('date_to', 'hidden_date_to', $contract[0]['contract_term']);
                }
            }
        } elseif ($form->getContext('type') === 'update') {
            // dd($entity);
            $form->getField('status')
                ->setType('select')
                ->setOption('values', ReportStatus::options());

            $form->getField('date_from')->setAttr('disabled', true);
            $getEmployee = $service->getEmployeeByReport($entity->report);

            if ($getEmployee > 0) {
                $form->getField('date_to')->setAttr('disabled', true);
            }
        }

        $form->getField('general_comment')
            ->setType('textarea')
            ->setAttr('disabled', true);
        $form->getField('correction_numb')
            ->setType('text')
            ->setAttr('disabled', true);

        $form->getField('correction_end_date')
            ->setType('date')
            ->setAttr('disabled', true);

        if ($form->getContext('type') === 'update') {
            if ($entity) {
                $correctionInfo = $service->getCorrectionDays($entity->correction_end_date);
            } else {
                $correctionInfo = $service->getCorrectionDays($form->getContext('data')['correction_end_date']);
            }

            $form->addField(new Field(
                'text',
                ['name' => 'correction_days_left', 'value' => $correctionInfo['days_left'], 'disabled' => true],
                ['label' => $this->slug . 'correction_days_left']
            ));
        }

        $form->getField('percent_second')
            ->setType('number')
            ->setOption('suffix', '%');
        $form->getField('percent_third')
            ->setType('number')
            ->setOption('suffix', '%');

        $layout[] = ['report_number', 'status'];
        $layout[] = ['date_from', 'date_to'];
        $layout[] = ['workplaces', 'percent_second', 'percent_third'];

        $documents = [
            'payment_request'                  => $intl('Искане за плащане'),
            'technical_report'                 => $intl('Технически доклад'),
            'financial_report'                 => $intl('Финансов отчет'),
            'employment_contracts_report'      => $intl('Справка за действащи трудови договори (Excel)'),
            'other_public_funding_report'      => $intl('Справка за друго публично финансиране'),
            'expenses_eligibility_declaration' => $intl('Декларация за допустимост на разходите'),
            'auditor_report'                   => $intl('Доклад от сертифициран одитор'),
            'state_aid_declaration'            => $intl('Декларация за получени държавни помощи'),
            'statistics_documents'             => $intl('Документи по Закона за статистиката'),
            // 'other_documents'                  => $intl('Други'),
        ];

        $layout[] = 'report.documents';

        foreach ($documents as $name => $label) {
            $form->addField(new Field(
                'file',
                ['name' => $name],
                ['label' => $label, 'picker' => false, 'size' => 1024 * 100 * 100, 'types' => 'pdf,doc,docx,jpeg,png']
            ));

            if ($form->getContext('type') === 'read') {
                $form->getField($name)->disable();
            }
        }

        $form->addField(new Field(
            'files',
            ['name' => 'other_documents'],
            [
                'label' => $intl('Други'),
                'picker' => false,
                'size' => 1024 * 100 * 100,
                'types' => 'pdf,doc,docx,jpeg,png']
        ));

        $layout[] = ['payment_request', 'technical_report'];
        $layout[] = ['financial_report', 'employment_contracts_report'];
        $layout[] = ['other_public_funding_report', 'expenses_eligibility_declaration'];
        $layout[] = ['auditor_report', 'state_aid_declaration'];
        $layout[] = ['statistics_documents', 'other_documents'];

        if ($form->getContext('type') === 'read') {
            $layout[] = ['pdf_sign'];
        }

        $status = $form->getField(name: 'status')->getValue();
        if ($status == ReportStatus::Approved->value && $this->getService()->isMIR()) {
            $form->getField('mir_doc')
                ->setType('file')
                ->setAttr('picker', false);
            $form->getField('mir_checklist')
                ->setType('file')
                ->setAttr('picker', false);
            $layout[] = ['mir_doc', 'mir_checklist'];
        }

        if (
            $status === ReportStatus::Approved->value ||
            $status === ReportStatus::Rejected->value ||
            $status === ReportStatus::ReturnedForCorrection->value
        ) {
            $layout[] = 'Корекции';
            $layout[] = ['correction_numb', 'correction_end_date', 'correction_days_left'];
            $layout[] = ['general_comment'];
        }

        if ($entity && $entity->workplaces_report_id) {
            $workplaces = $entity->workplaces_report_id->toArray();
            $layout[] = 'acc:Workplaces';

            foreach ($workplaces as $workplace) {
                $wid = $workplace->workplace;
                $wno = $workplace->workplace_no;

                $layout[] = 'Работно място № ' . $wno;

                $wpNoName = "workplace[$wid][workplace_no]";
                $nkpdName = "workplace[$wid][nkpd]";
                $statusId = "workplace[$wid][status]";

                // workplace_no
                $form->addField(new Field(
                    'text',
                    ['name' => $wpNoName, 'value' => $wno, 'disabled' => true],
                    ['label' => 'workplaces.workplace_no']
                ));

                // position
                $form->addField(new Field(
                    'text',
                    ['name' => $nkpdName, 'value' => $workplace->nom_nkpd->name ?? null, 'disabled' => true],
                    ['label' => 'workplaces.position']
                ));

                // status (общ за workplace)
                $statusData = $this->getWorkplaceStatus($workplace);
                $status = $statusData['status'];
                $days = $statusData['days'];
                if ($status === 0) {
                    $statusName = $intl('Not occupied');
                } elseif ($status === 1) {
                    $statusName = $intl('Occupied');
                } else {
                    $statusName = $intl('Free') . " ($days " . $intl('days without employee') . ")";
                }

                $form->addField(new Field(
                    'text',
                    ['name' => $statusId, 'value' => $statusName, 'disabled' => true],
                    ['label' => $intl('status')]
                ));

                $isFree = ($status === 0 || $status === 2);

                $empls = $workplace->workplace_empls ? $workplace->workplace_empls->toArray() : [];

                if (!$empls) {
                    $rowKey = 0;
                    $emplName = "workplace[$wid][empl][$rowKey][name]";
                    $startName = "workplace[$wid][empl][$rowKey][start_date]";
                    $endName   = "workplace[$wid][empl][$rowKey][end_date]";

                    $form->addField(new Field(
                        'text',
                        ['name' => $emplName, 'value' => '', 'disabled' => true],
                        ['label' => 'employees.columns.name']
                    ));

                    $form->addField(new Field(
                        'text',
                        ['name' => $startName, 'value' => '', 'disabled' => true],
                        ['label' => 'employees.start_date']
                    ));
                    $form->addField(new Field(
                        'text',
                        ['name' => $endName, 'value' => '', 'disabled' => true],
                        ['label' => 'employees.end_date']
                    ));

                    $layoutRow = [$wpNoName, $nkpdName, $emplName, $startName, $endName, $statusId];

                    if (
                        $isFree &&
                        ($entity->status === ReportStatus::Draft->value
                            ||
                            $entity->status === ReportStatus::ReturnedForCorrection->value)
                    ) {
                        $btnName = "workplace[$wid][empl][$rowKey][add_employee_btn]";
                        $form->addField(
                            (new Field('custom'))
                                ->setName($btnName)
                                ->setOption('view', 'reports::buttonAddEmployee')
                                ->setOption('href', $url('employees/create/' . $entity->report . '/' . $wid))
                                ->setOption('text', 'Добави служител към място ' . $wno)
                        );

                        $layoutRow[] = $btnName;
                    }

                    $layout[] = $layoutRow;
                    continue;
                }


                foreach ($empls as $employee) {
                    $rowKey = $employee->workplace_empl;
                    $emplName = "workplace[$wid][empl][$rowKey][name]";
                    $startName = "workplace[$wid][empl][$rowKey][start_date]";
                    $endName   = "workplace[$wid][empl][$rowKey][end_date]";

                    $form->addField(new Field(
                        'text',
                        ['name' => $emplName, 'value' => $employee->employees->name, 'disabled' => true],
                        ['label' => 'employees.columns.name']
                    ));

                    $form->addField(new Field(
                        'text',
                        [
                            'name' => $startName,
                            'value' => date('d.m.Y', strtotime($employee->start_date) ?: 0),
                            'disabled' => true
                        ],
                        ['label' => 'employees.start_date']
                    ));

                    $form->addField(new Field(
                        'text',
                        [
                            'name' => $endName,
                            'value' => $employee->end_date ? date('d.m.Y', strtotime($employee->end_date) ?: 0) : '',
                            'disabled' => true
                        ],
                        ['label' => 'employees.end_date']
                    ));

                    $layoutRow = [$wpNoName, $nkpdName, $emplName, $startName, $endName, $statusId];

                    if (
                        $isFree &&
                        ($entity->status === ReportStatus::Draft->value
                            ||
                            $entity->status === ReportStatus::ReturnedForCorrection->value)
                    ) {
                        $btnName = "workplace[$wid][empl][$rowKey][add_employee_btn]";

                        $form->addField(
                            (new Field('custom'))
                                ->setName($btnName)
                                ->setOption('view', 'reports::buttonAddEmployee')
                                ->setOption('href', '/employees/create/' . $wno)
                                ->setOption('text', 'Добави служител към място ' . $wno)
                        );

                        $layoutRow[] = $btnName;
                    }

                    $layout[] = $layoutRow;
                }
            }
        }
        $data = [];

        if (
            $entity &&
            !empty($entity->report_documents) &&
            isset($entity->report_documents[0])
        ) {
            $data = $entity->report_documents[0]->toArray();
        }

        $form->populate($data);
        // dd($entity->report_documents);

        if (!$form->getField('date_to')->hasAttr('disabled')) {
            $form->getValidator()->required('date_to');
        }
        $form->getValidator()->required('workplaces')->numeric($intl('Може да въведе число в диапазон 0 - 2000'));

        $form->setLayout($layout);
        return $form;
    }

    public function listingCallback(Table $table): Table
    {
        $service = $this->getService();
        $url = $this->container->instance(Uri::class);
        $table = parent::listingCallback($table);
        $table->removeColumn('general_comment');
        $table->removeColumn('correction_numb');
        $table->removeColumn('correction_end_date');
        $table->removeColumn('contract_id');
        $table->removeColumn('percent_second');
        $table->removeColumn('percent_third');
        $table->removeColumn('pdf_sign');
        $table->removeColumn('mir_doc');
        $table->removeColumn('mir_checklist');

        $table->addColumn(
            (new TableColumn('contracts.contract'))
                ->setFilter(
                    (new Form())
                        ->addField(
                            new Field(
                                'text',
                                ['name' => 'contracts.contract',],
                                [
                                    'label'  => $this->name . '.filters.contracts.contract',

                                ]
                            )
                        )
                )
                ->setMap(function (mixed $value, ReportsEntity $entity) use ($url): HTML {
                    $contract = $entity->contracts ?? null;
                    return $contract
                        ? new HTML('<a href="' . $url('contracts/read/' . $entity->contract_id) . '">' . 'Договор '
                            . $contract->contract_number . '</a>')
                        : new HTML('');
                })
        );

        $table
            ->getColumn('date_from')
            ->addClass('left aligned')
            ->setMap(function (mixed $v) {
                return new HTML(
                    '<i class="ui clock icon"></i> ' .
                        (($temp = DateTime::createFromFormat('Y-m-d', $v)) ?
                            $temp->format('d.m.Y') : '')
                );
            });

        $table
            ->getColumn('date_to')
            ->addClass('left aligned')
            ->setMap(function (mixed $v) {
                return new HTML(
                    '<i class="ui clock icon"></i> ' .
                        (($temp = DateTime::createFromFormat('Y-m-d', $v)) ?
                            $temp->format('d.m.Y') : '')
                );
            });

        $table->addColumn(
            (new TableColumn('locked'))
                ->setFilter(
                    (new Form())
                        ->addField(
                            new Field(
                                'text',
                                ['name' => 'locked'],
                                ['label'  => $this->name . '.filters.locked']
                            )
                        )
                )
                ->setMap(
                    function (mixed $value, ReportsEntity $entity): HTML {
                        if ((int)$entity->locked === 1) {
                            return new HTML('<i class="lock icon red" title="Заключен"></i>');
                        } else {
                            return new HTML('<i class="unlock icon green" title="Отключен"></i>');
                        }
                    }
                )
        );

        $table->addColumn(
            (new TableColumn('status'))
                ->setFilter(
                    (new Form())
                        ->addField(
                            new Field(
                                'text',
                                ['name' => 'status'],
                                ['label'  => $this->name . '.filters.status']
                            )
                        )
                )
                ->setMap(
                    function (mixed $value, ReportsEntity $entity) use ($service): HTML {
                        $status = ReportStatus::tryFrom((int)$entity->status);

                        $text = $status ? $status->label() : '';

                        if ($status === ReportStatus::ReturnedForCorrection) {
                            $daysLeft = $service->getCorrectionDays($entity->correction_end_date)['days_left'];
                            $text .=  ' (' . $daysLeft . 'дни)';
                        }

                        return new HTML($text);
                    }
                )
        );

        $table->getColumn('report_number')
            ->setFilter(
                (new Form())
                    ->addField(
                        new Field(
                            'text',
                            ['name' => 'report_number'],
                            ['label'  => $this->name . '.filters.report_number']
                        )
                    )
            )
            ->setMap(
                function (mixed $value, ReportsEntity $entity): HTML {

                    $report_number = $entity->report_number ?? null;
                    return $report_number && !empty($entity->contracts)
                        ? new HTML($report_number . ' / ' . $entity->contracts->period_reporting)
                        : new HTML('');
                }
            );

        if ($this->getService()->hasSpecialPermissions()) {
            $table->addColumn(
                (new TableColumn('unlock'))
                    ->setMap(
                        function (mixed $v, ReportsEntity $entity): HTML {
                            return new HTML(
                                '<button
                                    data-value="0"
                                    data-field="locked"
                                    class="state-button ui mini basic icon button ' .
                                    (!$entity->locked ? 'hide' : '') . '">
                                        <i class="ui key icon"></i></button>'
                            );
                        }
                    )
            );
        }

        foreach ($table->getRows() as $v) {
            $operations = $v->getOperations(true);
            //$reportCount = 0;
            $rowData = $v->getData();

            //$contracts = $rowData->contracts ?? null;
            $temp = [];

            $temp['read'] = $operations['read']->show();

            $temp['workplaces'] = (new Button('workplaces'))
                ->setLabel($this->name . '.operations.workplaces')
                ->setIcon('people carry')
                ->setClass('skip mini violet icon button')
                ->setAttr('href', $this->slug . '/workplaces/' .  $rowData->report);

            if (
                $rowData->locked !== 1 &&
                $this->canUpdate() &&
                ($rowData->status === ReportStatus::Draft->value
                    ||
                    $v->getData()->status === ReportStatus::ReturnedForCorrection->value)
            ) {
                $temp['update'] = $operations['update']->show();

                $temp['addPerson'] = (new Button('addPerson'))
                    ->setLabel($this->name . '.operations.addPerson')
                    ->setIcon('child')
                    ->setClass('skip mini green icon button')
                    ->setAttr('href', 'employees/create/' . $rowData->report);
            }
            $hasEmployees = false;
            foreach ($rowData->workplaces_report_id as $w) {
                if ($w->workplace_empls->count()) {
                    $hasEmployees = true;
                }
            }
            if (
                ($rowData->locked !== 1) &&
                $this->canUpdate() &&
                ($rowData->status === ReportStatus::Draft->value
                    ||
                    $rowData->status === ReportStatus::ReturnedForCorrection->value) &&
                $hasEmployees
            ) {
                $temp['exportExcel'] = (new Button('exportExcel'))
                    ->setLabel($this->name . '.operations.exportExcel')
                    ->setIcon('download')
                    ->setClass('skip mini grey icon button')
                    ->setAttr('href', $this->slug . '/excel/' .  $rowData->report);

                $temp['importExcel'] = (new Button('importExcel'))
                    ->setLabel($this->name . '.operations.importExcel')
                    ->setIcon('upload')
                    ->setClass('skip mini yellow icon button')
                    ->setAttr('href', $this->slug . '/importExcel/' . $rowData->report);
            }
            if ($rowData->reports_imports->count()) {
                $temp['imports'] = (new Button('imports'))
                    ->setLabel($this->name . '.operations.imports')
                    ->setIcon('file excel')
                    ->setClass('skip mini green icon button')
                    ->setAttr('href', $this->slug . '/imports/' . $rowData->report);
            }

            if ($this->hasHistory()) {
                $temp['history'] = $operations['history']->show();
            }
            if ($rowData->status === ReportStatus::Submitted->value && $this->getService()->isMIR()) {
                $temp['submitReportMir'] = (new Button('submitReportMir'))
                    ->setLabel($this->name . '.operations.submitReportMir')
                    ->setIcon('handshake')
                    ->setClass('skip mini brown icon button')
                    ->setAttr('href', $this->slug . '/submitReportMir/' .  $rowData->report);
            }

            if ($rowData->status === ReportStatus::Approved->value) {
                $temp['submitedReportRead'] = (new Button('submitedReportRead'))
                    ->setLabel($this->name . '.operations.submitreportрead')
                    ->setIcon('flag checkered')
                    ->setClass('skip mini pink icon button')
                    ->setAttr('href', $this->slug . '/submitedReportRead/' .  $rowData->report);

                if ($this->getService()->isMIR()) {
                    $temp['mirDoc'] = (new Button('mirDoc'))
                        ->setLabel($this->name . '.operations.mir_doc_report')
                        ->setIcon('file')
                        ->setClass('skip mini purple icon button')
                        ->setAttr('href', $this->slug . '/uploadDocument/' .  $rowData->report);
                }
            }
            $groups = $this->user->getGroups();
            $allowed = [
                $this->config->getInt('ADMIN_INV')
            ];

            foreach ($allowed as $gid) {
                if (array_key_exists($gid, $groups)) {
                    if (
                        $rowData->locked !== 1 &&
                        $this->canUpdate() &&
                        ($rowData->status === ReportStatus::Draft->value ||
                            $rowData->status === ReportStatus::ReturnedForCorrection->value)
                    ) {
                        $temp['sendReport'] = (new Button('sendReport'))
                            ->setLabel($this->name . '.operations.sendReport')
                            ->setIcon('envelope outline')
                            ->setClass('skip mini sky icon button')
                            ->setAttr('href', 'reports/SendReport/' .  $rowData->report);
                    }
                }
            }

            $v->setOperations($temp);
        }

        $table->removeOperation('create');

        $table->addOperation(
            (new Button("create"))
                ->setLabel($this->name . '.operations.contracts')
                ->setIcon('list')
                ->setClass('green icon labeled button')
                ->setAttr('href', 'contracts')
        );

        return $table;
    }

    public function getWorkplaceStatus(WorkplacesEntity $workplace): array
    {
        $empls = $workplace->workplace_empls
            ? $workplace->workplace_empls->toArray()
            : [];

        $today = new DateTimeImmutable('today');

        if (empty($empls)) {
            return [
                'status'  => 0,
                'status_label' => 'не е заемано',
                'days'         => 0,
                'free_from'    => null,
            ];
        }

        $hasActive = false;
        $periods = [];

        foreach ($empls as $e) {
            $start = !empty($e->start_date) ? new DateTimeImmutable($e->start_date) : null;
            $end   = !empty($e->end_date) ? new DateTimeImmutable($e->end_date) : null;

            // активен запис
            if ($start && !$end && $start <= $today) {
                $hasActive = true;
            }

            // приключил период
            if ($start && $end) {
                $periods[] = [
                    'start' => $start,
                    'end'   => $end,
                ];
            }
        }

        if ($hasActive) {
            return [
                'status'  => 1,
                'status_label' => 'зает',
                'days'         => 0,
                'free_from'    => null,
            ];
        }

        if (empty($periods)) {
            return [
                'status'  => 2,
                'status_label' => 'свободно',
                'days'         => 0,
                'free_from'    => null,
            ];
        }

        usort($periods, fn($a, $b) => $a['start'] <=> $b['start']);

        $lastPeriod = end($periods);
        $freeFrom = $lastPeriod['end']->modify('+1 day');

        $freeDays = 0;
        if ($freeFrom <= $today) {
            $freeDays = $freeFrom->diff($today)->days + 1;
        }

        return [
            'status'  => 2,
            'status_label' => 'свободно',
            'days'         => $freeDays,
            'free_from'    => $freeFrom->format('Y-m-d'),
        ];
    }
    // public function getWorkplaceStatus(WorkplacesEntity $workplace): array
    // {
    //     $empls = $workplace->workplace_empls
    //         ? $workplace->workplace_empls->toArray()
    //         : [];

    //     if (!$empls) {
    //         return ['status' => 0, 'days' => 0];
    //     }

    //     $i = 0;
    //     foreach ($empls as $e) {
    //         if (!empty($e->end_date)) {
    //             $i++;
    //         }
    //     }

    //     $periods = [];

    //     foreach ($empls as $e) {
    //         if ($e->start_date && $e->end_date) {
    //             $periods[] = [
    //                 'start' => new DateTimeImmutable($e->start_date),
    //                 'end'   => new DateTimeImmutable($e->end_date),
    //             ];
    //         }
    //     }

    //     if (count($periods) !== $i) {
    //         return ['status' => 1, 'days' => 0];
    //     }

    //     usort($periods, fn($a, $b) => $a['start'] <=> $b['start']);

    //     $today = new DateTimeImmutable('today');
    //     $freeDays = 0;
    //     $cntPeriod = count($periods);
    //     for ($i = 1; $i < $cntPeriod; $i++) {
    //         $gapStart = $periods[$i - 1]['end']->modify('+1 day');
    //         $gapEnd   = $periods[$i]['start']->modify('-1 day');

    //         if ($gapStart <= $gapEnd) {
    //             $freeDays += $gapStart->diff($gapEnd)->days + 1;
    //         }
    //     }

    //     $lastEnd = end($periods)['end'];
    //     $from = $lastEnd->modify('+1 day');

    //     if ($from <= $today) {
    //         $freeDays += $from->diff($today)->days + 1;
    //     }

    //     return ['status' => 2, 'days' => $freeDays];
    // }
}
