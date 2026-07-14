<?php

declare(strict_types=1);

namespace zni\modules\employees;

use DateTime;
use DateTimeImmutable;
use schema\EmployeesEntity;
use vakata\config\Config;
use vakata\di\DIContainer;
use vakata\http\Request;
use vakata\intl\Intl;
use vakata\user\User;
use webadmin\components\html\Button;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use zni\enums\MaintenanceReportStatus;
use zni\enums\ReportStatus;
use zni\modules\employees\EmployeesService;
use zni\permission\PermissionService;

/**
 * @extends CRUDModule<EmployeesEntity,EmployeesService>
 */
class EmployeesModule extends CRUDModule
{
    public const string NAME = 'employees';

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
            'id badge outline',
            'teal',
            'contracts',
            'employees',
            CRUDController::class,
            EmployeesService::class,
            __DIR__ . '/views'
        );
    }

    public function canRead(): bool
    {
        return true;
    }
    public function canCreate(): bool
    {
        $uri = $this->container->instance(\vakata\http\Uri::class);
        $id = $uri->getSegment(2);

        if (!$id) {
            return false;
        }
        return true;
    }

    public function canUpdate(): bool
    {
        $uri = $this->container->instance(\vakata\http\Uri::class);
        $id  = $uri->getSegment(2);

        if (!$id) {
            return true;
        }

        $entity = $this->getService()->read($id);


        $reportsById = [];

        foreach ($entity->workplace_empls as $we) {
            $workplace = $we->workplaces ?? null;
            if (!$workplace) {
                continue;
            }

            $report = $workplace->reports ?? null;
            if (!$report) {
                continue;
            }

            $rid = (int)($report->report ?? 0);
            if ($rid > 0) {
                $reportsById[$rid] = $report;
            }
        }

        if (!$reportsById) {
            return true;
        }
        foreach ($reportsById as $r) {
            // if ((int)($r->locked ?? 0) === 1) {
            //     continue;
            // }

            $status = $r->status ?? null;

            // if (!empty($r->not_empl_report)) {
            if (
                in_array($status, [
                    MaintenanceReportStatus::Draft->value,
                    MaintenanceReportStatus::ReturnedForCorrection->value,
                    ReportStatus::Draft->value,
                    ReportStatus::ReturnedForCorrection->value,
                ], true)
            ) {
                return true;
            }
            //     continue;
            // }

            // if (
            //     in_array($status, [
            //         ReportStatus::Draft->value,
            //         ReportStatus::ReturnedForCorrection->value,
            //     ], true)
            // ) {
            //     return true;
            // }
        }

        return true;
    }
    public function canDelete(): bool
    {
        return false;
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

    public function listingDefaults(): array
    {
        return [];
    }

    public function listingCallback(Table $table): Table
    {
        $table = parent::listingCallback($table);
        $intl = $this->intl;

        $table->getColumn('identifirer_type')
            ->setMap(function (mixed $value) use ($intl): string {
                $map = [
                    1 => $intl('egn'),
                    2 => $intl('lnch')
                ];

                return $map[(int)$value] ?? '';
            });

        $table->getColumn('identifirer')
            ->setMap(function (mixed $value): string {
                $value = (string)$value;
                if ($value === '') {
                    return '';
                }
                $len = mb_strlen($value);

                if ($len <= 4) {
                    return '****';
                }
                return mb_substr($value, 0, $len - 4) . '****';
            });


        $table->addColumn(
            (new TableColumn('employee_status'))
                ->setMap(function (mixed $value, EmployeesEntity $entity): HTML {

                    if (empty($entity->workplace_empls)) {
                        return new HTML('Няма назначение');
                    }

                    $empls = $entity->workplace_empls->toArray();

                    usort(
                        $empls,
                        fn($a, $b) =>
                        strcmp((string)$b->start_date, (string)$a->start_date)
                    );

                    $last = $empls[0];

                    if (empty($last->end_date)) {
                        return new HTML('<span class="ui green label">Активен</span>');
                    }

                    $days = (new DateTimeImmutable($last->end_date))
                        ->diff(new DateTimeImmutable('today'))
                        ->days;

                    if ($days > 180) {
                        return new HTML(
                            '<span class="ui red label">Напуснал преди ' . $days . ' дни</span>'
                        );
                    } else {
                        return new HTML(
                            '<span class="ui orange label">Напуснал преди ' . $days . ' дни</span>'
                        );
                    }
                })
        );

        $table->addColumn(
            (new TableColumn('reports'))
                ->setFilter(
                    (new Form())->addField(
                        new Field('text', ['name' => 'reports'], ['label' => $this->name . '.reports'])
                    )
                )
                ->setMap(function (mixed $value, EmployeesEntity $entity): HTML {

                    if (empty($entity->workplace_empls)) {
                        return new HTML('—');
                    }

                    $reportIds = [];

                    foreach ($entity->workplace_empls->toArray() as $we) {
                        if (!empty($we->workplaces) && !empty($we->workplaces->report_id)) {
                            $reportIds[] = (int)$we->workplaces->report_id;
                        }
                    }

                    $reportIds = array_unique($reportIds);

                    if (!$reportIds) {
                        return new HTML('—');
                    }

                    $tags = [];

                    foreach ($reportIds as $rid) {
                        $tags[] =
                            '<a class="ui tiny label" href="reports?p=1&l=25&report=' . $rid . '">' .
                            'Отчет #' . $rid .
                            '</a>';
                    }

                    return new HTML(implode(' ', $tags));
                })
        );

        $table->removeOperation('create');

        $table->addOperation(
            (new Button("create"))
                ->setLabel($this->name . '.operations.reports')
                ->setIcon('list')
                ->setClass('green icon labeled button')
                ->setAttr('href', 'reports')
        );
        return $table;
    }

    public function formCallback(Form $form): Form
    {
        $form = parent::formCallback($form);
        $form->removeField('contract_id');
        $form->removeField('profession');
        $service = $this->getService();
        $data = $form->getContext('data', []);
        $entity = $form->getContext('entity');
        $type = $form->getContext('type');
        $dataRegix = [];
        $layout = [];
        $layoutSalary = [];
        $intl = $this->intl;


        /** @var Request $request */
        $request = $this->container->get(Request::class);
        $body = (array) ($request->getParsedBody() ?? []);

        $segment2 = $request->getUrl()->getSegment(2);
        $segment3 = $request->getUrl()->getSegment(3);

        $reportId = null;
        if ($entity) {
            foreach ($entity->workplace_empls as $we) {
                if (!empty($we->workplaces->report_id)) {
                    $reportId = $we->workplaces->report_id;
                    break;
                }
            }
        }
        if (!$reportId) {
            /**
             * @psalm-suppress InvalidArgument
             */
            $reportId =
                ($body['report_id'] ?? null)
                ?? ($body['reportId'] ?? null)
                ?? ($body['hiddenReport'] ?? null);
        }

        if (!$reportId) {
            $reportId = $segment2;
        }
        $reportId = (int)$reportId;

        $form->addField(new Field(
            'hidden',
            [
                'name'  => 'hiddenReport',
                'value' => $reportId
            ],
            []
        ));

        $form->getField('identifirer_type')
            ->setType('select')
            // ->setAttr('data-redraw', true)
            ->setOption('values', [
                '' => $intl('please.selec.identifier'),
                '1' => $intl('egn'),
                '2' => $intl('lnch')
            ]);

        if ($type === 'update' || $type === 'create') {
            $form->addField(
                (new Field('custom'))
                    ->setName('checkNra')
                    ->setOption('view', 'employees::checkNra')
            );


            $form->addField(
                (new Field('custom'))
                    ->setName('checkNraError')
                    ->setOption('view', 'employees::checkNraError')
                    ->hide()
            );

            $form->addField(
                new Field(
                    'text',
                    [
                        'name' => 'checkNraHidden',
                        'data-redraw' => true,
                        'value' => 1
                    ],
                    []
                )
            );
        }

        $positions = $service->getPositions();

        $form->addField(new Field(
            'select',
            ['name' => 'position', 'readonly' => true],
            [
                'values' => ['' => 'please.select'] + $positions,
                'label' => $this->slug . '.columns.position',
                'translate' => true
            ]
        ));


        if ($type === 'create') {
            $workplaces = $service->getWorkplaces((int) $reportId);
            $report = $service->getReport($reportId);
        } else {
            $workplaces = $service->getAllWorkplaces((int) $reportId);
        }

        $form->addField(new Field(
            'select',
            ['name' => 'workplace_id'],
            [
                'values' => ['' => 'please.select'] + $workplaces,
                'label' => $this->slug . '.columns.workplace_id',
                'translate' => true
            ]
        ));

        if ($segment3) {
            $form->getField('workplace_id')
                ->setValue($segment3);
        }

        if ($type !== 'create') {
            $form->getField('workplace_id')->setAttr('readonly', true);
            $form->getField('identifirer_type')->setAttr('readonly', true);
            $form->getField('identifirer')->setAttr('readonly', true);
        }

        $form->addField(new Field(
            'date',
            ['name' => 'start_date', 'readonly' => true],
            ['label' => $this->slug . '.columns.start_date']
        ));

        $form->addField(new Field(
            'date',
            ['name' => 'end_date', 'readonly' => true],
            ['label' => $this->slug . '.columns.end_date']
        ));

        $form->addField(new Field(
            'date',
            ['name' => 'last_amend_date', 'readonly' => true],
            ['label' => $this->slug . '.columns.last_amend_date']
        ));

        $form->addField(new Field(
            'text',
            ['name' => 'reason', 'readonly' => true],
            ['label' => $this->slug . '.columns.reason']
        ));
        $form->addField(new Field(
            'text',
            ['name' => 'eco_code', 'readonly' => true],
            ['label' => $this->slug . '.columns.eco_code']
        ));

        $form->addField(new Field(
            'text',
            ['name' => 'ekatte', 'readonly' => true],
            ['label' => $this->slug . '.columns.ekatte']
        ));

        $form->addField(new Field(
            'text',
            ['name' => 'last_term', 'readonly' => true],
            ['label' => $this->slug . '.columns.last_term']
        ));


        $syncStatuses = $service->regixStatuses();
        $form->addField(new Field(
            'select',
            ['name' => 'sync_status', 'readonly' => true],
            ['label' => $this->slug . '.columns.sync_status', 'values' => $syncStatuses]
        ));

        $form->addField(new Field(
            'datetime',
            ['name' => 'last_sync', 'readonly' => true],
            ['label' => $this->slug . '.columns.last_sync']
        ));

        $form->addField(new Field(
            'date',
            ['name' => 'project_start_date'],
            ['label' => $this->slug . '.columns.project_start_date']
        ));

        $form->addField(new Field(
            'text',
            ['name' => 'refund_sum'],
            ['label' => $this->slug . '.columns.refund_sum']
        ));

        $form->addField(new Field(
            'text',
            ['name' => 'salary_amount'],
            ['label' => $this->slug . '.columns.salary_amount']
        ));

        $form->getField('name')->setAttr('readonly', true);

        $click = (int)($body['checkNraHidden'] ?? 0);
        $last  = (int)($body['checkNraLast'] ?? 0);

        if (!empty($body) && $click > $last) {
            $identifierType = (int)($body['identifirer_type'] ?? 0);
            $identifier     = trim((string)($body['identifirer'] ?? ''));

            if ($identifierType <= 0) {
                $errorText = $intl('Моля, въведете идентификатор');
                $field = $form->getField('checkNraError');
                $field->setValue($errorText);
                $field->show();
            } else {
                $validator = new \vakata\validation\Validator();
                $validator->required('identifirer')
                    ->bgIDN($intl->get('companies.validation.egn_lnc'));

                $errors = $validator->run(['identifirer' => $identifier]);

                if (!empty($errors)) {
                    $errorText = $intl('Грешно ЕГН или ЛНЧ');
                    $field = $form->getField('checkNraError');
                    $field->setValue($errorText);
                    $field->show();
                } else {
                    $workplaceEntity = null;
                    $employee = null;
                    if ($entity) {
                        $employee = $entity->employee;
                        $workplaceEntity = $entity->workplace_empls[0]->workplace_id;
                    }
                    $typeId = ($identifierType === 1) ? 'EGN' : 'LNCH';
                    $dataRegix = $service->getEETZ($identifier, $typeId, $reportId, $employee, $workplaceEntity);

                    if ($dataRegix['data']['sync_status'] === 2) {
                        $errorText = $intl('Няма данни за това лице към вашата компания !');
                        $field = $form->getField('checkNraError');
                        $field->setValue($errorText);
                        $field->show();
                    }
                }
            }

            $last = $click;

            if ($form->hasField('checkNraLast')) {
                $form->getField('checkNraLast')->setValue((string)$last);
            } else {
                $body['checkNraLast'] = $last;
            }
        }

        $type_expenses = $service->getTypeExpense();
        $form->addField(new Field(
            'select',
            ['name' => 'type_expense'],
            [
                'label' => $this->slug . '.columns.type_expense',
                // 'values' => ['' => $intl('please.select.type_expense')]  + $type_expenses
                'values' => $type_expenses
            ]
        ));

        $options = [];
        $tmp = [];

        if ($entity) {
            foreach ($entity->workplace_empls as $we) {
                $report = $we->workplaces->reports ?? null;

                if (!$report) {
                    continue;
                }

                $rid = (int)$report->report;

                $status = ReportStatus::from((int)$report->status)->label();

                $text =
                    'Отчет номер ' .
                    $report->report_number .
                    ' (' .
                    date('d.m.Y', strtotime($report->date_from) ?: 0) .
                    ' - ' .
                    date('d.m.Y', strtotime($report->date_to) ?: 0) .
                    ') - ' .
                    'Договор ' . $report->contracts->contract_number
                    . ' / ' . $status;

                $tmp[] = [
                    'id' => $rid,
                    'number' => (int)$report->report_number,
                    'text' => $text
                ];
            }

            usort($tmp, fn($a, $b) => $a['number'] <=> $b['number']);

            foreach ($tmp as $r) {
                $options[$r['id']] = $r['text'];
            }
        }


        $form->addField(
            new Field(
                'select',
                ['name' => 'allReports', 'data-redraw' => true],
                [
                    'label'  => $this->slug . '.columns.allReports',
                    'values' => $options
                ]
            )
        );

        if (count($options) === 1) {
            $onlyReportId = (string)array_key_first($options);

            $form->getField('allReports')->setValue($onlyReportId);
            $body['allReports'] = $onlyReportId;
        }

        $selectedReport = (int)($body['allReports'] ?? $form->getField('allReports')->getValue() ?? 0);
        $layoutSalary = [];

        if ($selectedReport && !in_array($type, ['read', 'history'])) {
            $report = null;
            $assignment = null;
            $totalSalary   = 0.0;
            $totalInsurance = 0.0;
            $currency = "EUR";
            $projectStartDate = null;
            foreach ($entity->workplace_empls as $we) {
                if ($we->workplaces && (int)$we->workplaces->reports->report === $selectedReport) {
                    $report = $we->workplaces->reports;
                    $assignment = $we;
                    $projectStartDate = $we->project_start_date;

                    break;
                }
            }

            if ($report && $assignment) {
                $currency = $report->contracts->currency ?? "EUR";
                $start = new DateTime($projectStartDate ?? "now");
                if ($start->format('j') > 1) {
                    $start->modify('first day of next month');
                }

                $end   = new DateTime($report->date_to);

                $end->modify('last day of this month');

                $salaryMap = $this->getService()->getEmployeeSalary($assignment->workplace_empl);

                $editable = in_array(
                    (int)$report->status,
                    [
                        ReportStatus::Draft->value,
                        ReportStatus::ReturnedForCorrection->value
                    ],
                    true
                );

                while ($start <= $end) {
                    $month = (int)$start->format('n');
                    $year  = (int)$start->format('Y');
                    $label = $start->format('m.Y');

                    $key = $year . '_' . $month;

                    $salary    = $salaryMap[$key]['salary'] ?? '';
                    $insurance = $salaryMap[$key]['insurance'] ?? '';
                    $percent   = $salaryMap[$key]['percent'] ?? $report->percent_third;

                    if (is_numeric($salary)) {
                        $totalSalary += $salary;
                    }
                    if (is_numeric($insurance)) {
                        $totalInsurance += $insurance;
                    }

                    $salaryName    = "salary[$key]";
                    $insuranceName = "insurance[$key]";
                    $percentName   = "percent[$key]";
                    $layoutSalary[] = $label;

                    $layoutSalary[] = [
                        $salaryName,
                        $insuranceName,
                        $percentName
                    ];

                    $form->addField(new Field(
                        'text',
                        ['name' => $salaryName, 'value' => $salary],
                        [
                            'label' => 'Заплата (' . $label . ')',
                            'suffix' => 'currency.' . $report->contracts->currency
                        ]
                    ));

                    $form->addField(new Field(
                        'text',
                        ['name' => $insuranceName, 'value' => $insurance],
                        [
                            'label' => 'Осигуровка (' . $label . ')',
                            'suffix' => 'currency.' . $report->contracts->currency
                        ]
                    ));

                    $form->addField(new Field(
                        'select',
                        [
                            'name' => $percentName,
                            'value' => $percent
                        ],
                        [
                            'label' => 'Процент (' . $label . ')',
                            'values' => [
                                $report->percent_second
                                => $intl('reports.options.percent_second', [$report->percent_second]),
                                $report->percent_third
                                => $intl('reports.options.percent_third', [$report->percent_third]),
                            ],
                        ]
                    ));

                    if ($type === 'read' || !$editable) {
                        $form->getField($salaryName)->disable();
                        $form->getField($insuranceName)->disable();
                        $form->getField($percentName)->disable();
                    }

                    $start->modify('+1 month');
                }
            }
            $form->addField(new Field(
                'text',
                [
                    'name' => 'totalSalary',
                    'value' => $totalSalary,
                    'readonly' => true
                ],
                [
                    'label' => $intl('total.sallary'),
                    'suffix' => 'currency.' . $currency
                ]
            ));

            $form->addField(new Field(
                'text',
                [
                    'name' => 'totalInsurance',
                    'value' => $totalInsurance,
                    'readonly' => true
                ],
                [
                    'label' => 'Общо осигуровки',
                    'suffix' => 'currency.' . $currency
                ]
            ));
            $layoutSalary[] = ['totalSalary', 'totalInsurance'];

            $data['totalSalary'] = round($totalSalary, 2);
            $data['totalInsurance'] = round($totalInsurance, 2);

            $form->getField('refund_sum')
                ->setOption('suffix', 'currency.' . $currency)
                ->disable();
            $form->getField('salary_amount')
                ->setOption('suffix', 'currency.' . $currency);
        }

        $layout[] = ['checkNraError'];
        if ($type === 'update') {
            $layout[] = ['allReports'];
        }

        $layout[] = ['workplace_id', 'identifirer_type', 'identifirer', 'checkNra'];
        if ($type === 'update') {
            $layout[] = ['project_start_date', 'type_expense', 'refund_sum', 'salary_amount'];
        } else {
            $layout[] = ['project_start_date', 'type_expense'];
        }

        $layout[] = 'regix.data';
        $layout[] = ['name'];
        $layout[] = ['position', 'start_date', 'end_date', 'last_amend_date'];
        $layout[] = ['reason', 'eco_code'];
        $layout[] = ['ekatte', 'last_term'];
        $layout[] = ['sync_status', 'last_sync'];


        $layout = array_merge($layout, $layoutSalary);

        if ($request->getUrl()->getSegment(4) || isset($data['mr'])) {
            $form->addField(
                (new Field('hidden', ['name' => 'mr'], []))
                    ->setValue(isset($data['mr']) ? $data['mr'] : $request->getUrl()->getSegment(4))
            );
            $layout[] = ['mr'];
        }

        if ($type === 'read') {
            foreach ($this->showReports($entity, $form, $intl) as $l) {
                $layout[] = $l;
            }

            $form->getField('allReports')->hide();
        } else {
            $form->getField('allReports')->show();
        }

        $form->setLayout($layout);

        // if ($entity) {
        //     $form->populate($entity->toArray());

        //     $reportId = (int)$form
        //         ->getField('hiddenReport')
        //         ->getValue();

        //     $assignment = null;

        //     foreach ($entity->workplace_empls as $we) {
        //         $workplace = $we->workplaces;

        //         if ($workplace && (int)$workplace->report_id === $reportId) {
        //             $assignment = $we;
        //             break;
        //         }
        //     }

        //     if ($assignment) {
        //         $form->populate($assignment->toArray());

        //         $form->populate([
        //             'position'        => $assignment->workplaces->position_id,
        //             'workplace_id'    => $assignment->workplaces->workplace,
        //             'workplace_no'    => $assignment->workplaces->workplace_no,
        //         ]);

        //         $form->populate([
        //             'start_date'         => $assignment->start_date,
        //             'end_date'           => $assignment->end_date,
        //             'salary_amount'      => $assignment->salary_amount,
        //             'refund_sum'         => $assignment->refund_sum,
        //             'project_start_date' => $assignment->project_start_date,
        //             'reason'             => $assignment->reason,
        //         ]);
        //     }
        // }

        // $form->populate($data);

        if ($entity) {
            $form->populate($entity->toArray());
        }

        $form->populate($data);

        if ($entity) {
            $selectedReportId = (int)($body['allReports'] ?? 0);

            $form->populate([
                'position'           => null,
                'workplace_id'       => null,
                'workplace_no'       => null,
                'start_date'         => null,
                'end_date'           => null,
                'salary_amount'      => null,
                'refund_sum'         => null,
                'project_start_date' => null,
                'reason'             => null,
            ]);

            $assignment = null;

            if ($selectedReportId > 0) {
                foreach ($entity->workplace_empls as $we) {
                    $workplace = $we->workplaces ?? null;
                    $report = $workplace->reports ?? null;

                    if (!$workplace || !$report) {
                        continue;
                    }

                    if ((int)$report->report !== $selectedReportId) {
                        continue;
                    }

                    $assignment = $we;
                    break;
                }
            }

            if ($assignment) {
                $form->populate($assignment->toArray());

                $form->populate([
                    'position'           => $assignment->workplaces->position_id ?? null,
                    'workplace_id'       => $assignment->workplaces->workplace ?? null,
                    'workplace_no'       => $assignment->workplaces->workplace_no ?? null,
                    'start_date'         => $assignment->start_date ?? null,
                    'end_date'           => $assignment->end_date ?? null,
                    'salary_amount'      => $assignment->salary_amount ?? null,
                    'refund_sum'         => $assignment->refund_sum ?? null,
                    'project_start_date' => $assignment->project_start_date ?? null,
                    'reason'             => $assignment->reason ?? null,
                ]);
            }
        }


        if ($dataRegix && isset($dataRegix['data']) && $dataRegix['data']) {
            // dd($dataRegix);
            $dataRegix['data']['checkNraHidden'] = 0;
            $form->populate($dataRegix['data']);
            // if((int)$form->getField('checkLeftHidden')===1){


            // }
        } elseif (
            !empty($body) &&
            !empty($body['checkNraHidden']) &&
            (isset($body['checkNraFinal']) && $body['checkNraFinal'] === '')
        ) {
            $form->getField('checkNraError')->show();
        }

        $validator = $form->getValidator();

        if ($type === 'create') {
            $validator->required('identifirer_type', $intl('identifirer_type.reuqired'));

            if ($form->hasField('replace')) {
                $replace = (int) $form->getField('replace')->getValue();

                if ($replace === 1) {
                    $validator->required(
                        'left_persons',
                        $intl('left_persons.reuqired')
                    );
                }
            }
        }

        $validator->required('name', $intl('name.reuqired'))
            // ->required('start_date', $intl('start_date.reuqired'))
            //->required('refund_sum', $intl('refund_sum.reuqired'))
            // ->required('type_expense', $intl('type_expense.reuqired'))
            ->required('identifirer', $intl('type_expense.identifirer'));

        // ->required('remuneration', $intl('remuneration.reuqired'));

        if ($type === 'create') {
            // if ($leftPersons) {
            //     $validator->required('left_persons');
            // }
            $validator->required('project_start_date')
                ->required('type_expense')
                ->required('workplace_id', $intl('workplace_id.required'));
            // if ((int) $form->getField('identifirer_type')->getValue() === 3) {
            //     $validator->required('start_date', $intl('start_date.reuqired'));
            // }
        } else {
            $validator->required('allReports', $intl('allReports.reuqired'));
        }

        return $form;
    }

    private function showReports(EmployeesEntity $entity, Form $form, Intl $intl): array
    {
        $layout = [];

        foreach ($entity->workplace_empls as $assignment) {
            /**
             * @psalm-suppress PossiblyNullPropertyFetch
             */
            $report = $assignment->workplaces->reports;
            $status = ReportStatus::from($report->status ?? 0)->label();

            $totalSalary   = 0.0;
            $totalInsurance = 0.0;

            $text =
                'Отчет номер ' .
                ($report->report_number ?? "") .
                ' (' .
                date('d.m.Y', strtotime($report->date_from ?? "") ?: 0) .
                ' - ' .
                date('d.m.Y', strtotime($report->date_to ?? "") ?: 0) .
                ') - ' .
                'Договор ' . ($report->contracts->contract_number ?? "")
                . ' / ' . $status;

            $layout[] = 'acc:' . $text;

            //salary
            if ($report && $assignment) {
                $start = new DateTime($assignment->project_start_date ?? "now");
                if ($start->format('j') > 1) {
                    $start->modify('first day of next month');
                }

                $end   = new DateTime($report->date_to ?? "now");

                $end->modify('last day of this month');

                $salaryMap = $this->getService()->getEmployeeSalary($assignment->workplace_empl);

                while ($start <= $end) {
                    $month = (int)$start->format('n');
                    $year  = (int)$start->format('Y');
                    $label = $start->format('m.Y');

                    $key = $year . '_' . $month;

                    $salary    = $salaryMap[$key]['salary'] ?? '';
                    $insurance = $salaryMap[$key]['insurance'] ?? '';
                    $percent   = $salaryMap[$key]['percent'] ?? $report->percent_third;

                    if (is_numeric($salary)) {
                        $totalSalary += $salary;
                    }
                    if (is_numeric($insurance)) {
                        $totalInsurance += $insurance;
                    }

                    $salaryName    = "salary[$key]";
                    $insuranceName = "insurance[$key]";
                    $percentName   = "percent[$key]";
                    $layout[] = $label;

                    $layout[] = [
                        $salaryName,
                        $insuranceName,
                        $percentName
                    ];

                    $form->addField(new Field(
                        'text',
                        ['name' => $salaryName, 'value' => $salary, 'readonly' => true],
                        [
                            'label' => 'Заплата (' . $label . ')',
                            'suffix' => 'currency.' . $report->contracts?->currency
                        ]
                    ));

                    $form->addField(new Field(
                        'text',
                        ['name' => $insuranceName, 'value' => $insurance, 'readonly' => true],
                        [
                            'label' => 'Осигуровка (' . $label . ')',
                            'suffix' => 'currency.' . $report->contracts?->currency
                        ]
                    ));

                    $form->addField(new Field(
                        'select',
                        [
                            'name' => $percentName,
                            'value' => $percent,
                            'readonly' => true
                        ],
                        [
                            'label' => 'Процент (' . $label . ')',
                            'values' => [
                                $report->percent_second
                                => $intl('reports.options.percent_second', [$report->percent_second]),
                                $report->percent_third
                                => $intl('reports.options.percent_third', [$report->percent_third]),
                            ],
                        ]
                    ));

                    $start->modify('+1 month');
                }
            }
            $form->addField(new Field(
                'text',
                [
                    'name' => 'totalSalary',
                    'value' => $totalSalary,
                    'readonly' => true
                ],
                [
                    'label' => $intl('total.sallary'),
                    'suffix' => 'currency.' . ($report->contracts->currency ?? "EUR")
                ]
            ));

            $form->addField(new Field(
                'text',
                [
                    'name' => 'totalInsurance',
                    'value' => $totalInsurance,
                    'readonly' => true
                ],
                [
                    'label' => 'Общо осигуровки',
                    'suffix' => 'currency.' . ($report->contracts->currency ?? "EUR")
                ]
            ));
            $layout[] = ['totalSalary', 'totalInsurance'];
        }

        return $layout;
    }
}
