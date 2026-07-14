<?php

declare(strict_types=1);

namespace zni\modules\reports;

use base\components\files\Files;
use PhpOffice\PhpSpreadsheet\IOFactory;
use schema\ReportsImportsEntity;
use stdClass;
use vakata\config\Config;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\http\Uri;
use vakata\intl\Intl;
use vakata\spreadsheet\Reader;
use vakata\user\User;
use vakata\validation\Validator;
use webadmin\components\html\Button;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\components\html\TableRow;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDNotFoundException;
use zni\enums\ReportImportType;
use zni\enums\ReportStatus;
use zni\modules\reports\ReportsService;
use zni\permission\PermissionService;

/**
 * @extends CRUDController<\schema\ReportsEntity,ReportsService>
 */
class ReportsController extends CRUDController
{
    public function getRead(Request $request): Response
    {
        $this->service->journal('Преглед на отчет', 'info', (int)$request->getUrl()->getSegment(2));
        return parent::getRead($request);
    }

    public function getCreateSub(Request $request): Response
    {
        //TODO predi da se zaerdqt vischki, trybva da se proveri i da se zapishe direktno v db dali ne sa napusnali
        $intl = $this->intl;
        try {
            $full = $this->service->getContractFullInfo((int)$request->getUrl()->getSegment(2));
            // $full = $this->service->getContract((int)$request->getUrl()->getSegment(2));
            $contract = $full['contract'] ?? [];
            $reports  = $full['reports'] ?? [];

            if (count($reports) > $contract['period_reporting']) {
                throw new CRUDNotFoundException('reports.validaiton.max_reports');
            }

            // dd($contract, $reports);

            $form = new Form();
            $form->addField(
                new Field(
                    'hidden',
                    ['name' => 'hiddenContract', 'value' => (int)$request->getUrl()->getSegment(2)],
                    []
                )
            );

            $form->addField(
                new Field(
                    'hidden',
                    ['name' => 'hiddenReport', 'value' => $reports[0]['report']],
                    []
                )
            );

            // Основни данни за договор
            // phpcs:disable Generic.Files.LineLength
            $form->addField(new Field(
                'text',
                ['name' => 'contract[contract_number]', 'value' => $contract['contract_number'], 'disabled' => true],
                ['label' => 'contracts.columns.contract_number', 'translate' => 1]
            ));
            $form->addField(new Field(
                'text',
                ['name' => 'contract[id]', 'value' => $contract['id'], 'disabled' => true],
                ['label' => 'eik', 'translate' => 1]
            ));
            $form->addField(new Field(
                'text',
                ['name' => 'contract[company_name]', 'value' => $contract['company_name'], 'disabled' => true],
                ['label' => 'company.name', 'translate' => 1]
            ));

            $nextReportNumber = count($reports) + 1;

            $layout = [
                'Добавяне на отчет ' . $nextReportNumber . '/' . $contract['period_reporting'],
                ['contract[contract_number]', 'contract[company_name]', 'contract[id]']
            ];

            $layout[] = 'acc:open:Отчет ' . $nextReportNumber;
            $lastReport = $reports[0] ?? [];

            $dateFrom = (new \DateTime($lastReport['date_to']))
                ->modify('+1 day')
                ->format('d.m.Y');

            $dateFromFiled = new Field('date', [
                'name' => "date_from",
                'value' => $dateFrom,
                'disabled' => true
            ], ['label' => $intl('report.start_date')]);

            $hiddenDateFromFIeld =  new Field('hidden', [
                'name' => "hidden_date_from",
                'value' => $dateFrom,
            ], ['label' => $intl('report.start_date')]);

            if ($contract['period_reporting'] == $nextReportNumber) {
                $dateToField = new Field('date', [
                    'name' => "date_to",
                    'value' => $contract['contract_term'],
                ], ['label' => $intl('report.date_end')]);

                $hiddenDateToFIeld =  new Field('hidden', [
                    'name' => "hidden_date_to",
                    'value' => $dateFrom,
                ], ['label' => $intl('report.date_to')]);
                $form->addField($hiddenDateToFIeld);
                $layout[] = ["hidden_date_to"];
            } else {
                $dateToField = new Field(
                    'date',
                    ['name' => "date_to"],
                    [
                        'label' => $intl('report.date_end'),
                        'maxDate' => (new \DateTime($contract['contract_term']))->format('d.m.Y')
                    ]
                );

                $form->getValidator()
                    ->required('date_to');
            }

            $form->addField($dateFromFiled);
            $form->addField($dateToField);
            $form->addField($hiddenDateFromFIeld);

            $layout[] = ["date_from", "hidden_date_from", "date_to"];

            $form->addField(new Field(
                'text',
                ['name' => 'workplaces'],
                ['label' => 'report.workplaces']
            ));
            $form->addField(new Field(
                'number',
                ['name' => 'percent_second'],
                ['label' => 'reports.columns.percent_second', 'suffix' => '%']
            ));
            $form->addField(new Field(
                'number',
                ['name' => 'percent_third'],
                ['label' => 'reports.columns.percent_third', 'suffix' => '%']
            ));

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
                'other_documents'                  => $intl('Други'),
            ];

            $layout[] = 'report.documents';

            foreach ($documents as $name => $label) {
                $form->addField(new Field(
                    'file',
                    ['name' => $name],
                    [
                        'label' => $label,
                        'picker' => false,
                        'size' => 1024 * 100 * 100,
                        'types' => 'pdf,doc,docx,jpeg,png'
                    ]
                ));

                if ($form->getContext('type') === 'read') {
                    $form->getField($name)->disable();
                }
            }

            $layout[] = ['payment_request', 'technical_report'];
            $layout[] = ['financial_report', 'employment_contracts_report'];
            $layout[] = ['other_public_funding_report', 'expenses_eligibility_declaration'];
            $layout[] = ['auditor_report', 'state_aid_declaration'];
            $layout[] = ['statistics_documents', 'other_documents'];

            foreach ($reports as $rIndex => $report) {
                $reportKey = $report['report'];

                // Статус и дати на отчет
                // phpcs:disable Generic.Files.LineLength
                $form->addField(new Field(
                    'select',
                    ['name' => $reportKey . 'status', 'value' => $report['status'], 'disabled' => true],
                    ['label' => $intl('report.status'),  'values' => ReportStatus::options()]
                ));
                $form->addField(new Field(
                    'date',
                    ['name' => "{$reportKey}[date_from]", 'value' => $report['date_from'], 'disabled' => true],
                    ['label' => $intl('report.start_date')]
                ));
                $form->addField(new Field(
                    'date',
                    ['name' => "{$reportKey}[date_to]", 'value' => $report['date_to'], 'disabled' => true],
                    ['label' => $intl('report.date_end')]
                ));

                $layout[] = 'acc:Отчет ' . $report['report_number'];
                $layout[] = [$reportKey . 'status', $reportKey . '[date_from]', $reportKey . '[date_to]'];

                foreach ($report['workplaces'] as $workplace) {
                    $layout[] = 'Работно място № ' . $workplace['workplace_no'];
                    $wid = $workplace['workplace'];
                    $wpNoName   = "workplace[$wid][workplace_no]";
                    $nkpdName   = "workplace[$wid][nkpd]";
                    $statusId = "workplace[$wid][status]";
                    $startName = "workplace[$wid][start_date]";
                    $endName = "workplace[$wid][end_date]";

                    $statusData = $this->service->getWorkplaceStatus($workplace);
                    $status = $statusData['status'];
                    $days = $statusData['days'];
                    if ($status === 0) {
                        $statusName = $intl('Not occupied');
                    } elseif ($status === 1) {
                        $statusName = $intl('Occupied');
                    } else {
                        $statusName = $intl('Free') . " ($days " . $intl('days without employee') . ")";
                    }

                    // workplace_no
                    $form->addField(new Field(
                        'text',
                        ['name' => $wpNoName, 'value' => $workplace['workplace_no'], 'disabled' => true],
                        ['label' => 'workplaces.workplace_no']
                    ));

                    // position
                    $form->addField(new Field(
                        'text',
                        ['name' => $nkpdName, 'value' => $workplace['nkpd_name'] ?? null, 'disabled' => true],
                        ['label' => 'workplaces.position']
                    ));

                    $form->addField(new Field(
                        'text',
                        ['name' => $statusId, 'value' => $statusName, 'disabled' => true],
                        ['label' => $intl('status')]
                    ));

                    foreach ($workplace['employees'] as $employee) {
                        $rowKey = $employee['workplace_empl'];
                        $startName = "workplace[$wid][empl][$rowKey][start_date]";
                        $endName   = "workplace[$wid][empl][$rowKey][end_date]";

                        $form->addField(new Field(
                            'text',
                            [
                                'name' => $startName,
                                'value' => date('d.m.Y', strtotime($employee['start_date']) ?: 0),
                                'disabled' => true
                            ],
                            ['label' => 'employees.start_date']
                        ));

                        $form->addField(new Field(
                            'text',
                            [
                                'name' => $endName,
                                'value' => $employee['end_date']
                                    ? date('d.m.Y', strtotime($employee['end_date']) ?: 0)
                                    : '',
                                'disabled' => true
                            ],
                            ['label' => 'employees.end_date']
                        ));
                        $layout[] = [$wpNoName, $nkpdName, $startName, $endName, $statusId];
                    }
                }
            }

            $this->service->createSubValidator($form->getValidator());

            $form->setLayout($layout);
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        return (new Response())->setBody(
            $this->render('createSub', [
                'form' => $form,
                'back' => $request->getUrl()->linkTo(
                    $this->session->get($this->moduleName . '.index', $this->module->getSlug())
                )
            ])
        );
    }
    public function postCreateSub(Request $request): Response
    {
        try {
            $parsedBody = (array) ($request->getParsedBody() ?? []);
            $validator = $this->service->createSubValidator(new Validator());
            $errors = $validator->run($parsedBody);
            if (count($errors)) {
                foreach ($errors as $k => $v) {
                    if (!$v['message']) {
                        $errors[$k]['message'] = 'validation.' . $v['key'] . '.' . $v['rule'];
                    }
                }
                throw (new CRUDException("validation", 400))->setErrors($errors);
            }

            $this->service->createWithOld($parsedBody);
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        return (new Response(303))->withHeader('Location', $request->getUrl()->linkTo());
    }
    public function getExcel(Request $request): ?Response
    {
        $segment2 = (int) $request->getUrl()->getSegment(2);

        try {
            $entity = $this->service->read($segment2);

            if (
                !in_array($entity->status, [
                    ReportStatus::Draft->value,
                    ReportStatus::ReturnedForCorrection->value
                ])
            ) {
                throw new CRUDNotFoundException('crud.messages.notfound');
            }

            $this->service->generateExcel($segment2);
            return null;
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
    }
    public function getImportExcel(Request $request): Response
    {
        $emplReportId = (int)$request->getUrl()->getSegment(2);

        try {
            $entity = $this->service->read($emplReportId);

            if (
                !in_array($entity->status, [
                    ReportStatus::Draft->value,
                    ReportStatus::ReturnedForCorrection->value
                ])
            ) {
                throw new CRUDNotFoundException('crud.messages.notfound');
            }
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        $form = new Form();

        $form->addField(new Field(
            'file',
            ['name' => 'excel_file', 'accept' => '.xlsx,.xls'],
            ['label' => 'Изберете Excel файл', 'picker' => false]
        ));

        $form->addField(
            (new Field('hidden', ['name' => 'empl_report']))->setValue($emplReportId)
        );

        $form->setLayout([
            'Импорт на Excel файл',
            ['excel_file'],
        ]);

        return (new Response())->setBody(
            $this->render('importForm', [
                'form' => $form,
                'back' => $request->getUrl()->linkTo(
                    $this->session->get($this->moduleName . '.index', $this->module->getSlug())
                )
            ])
        );
    }
    public function postImportExcel(Request $request, Files $files, User $user): mixed
    {
        $reportId = (int)$request->getUrl()->getSegment(2);

        try {
            $entity = $this->service->read($reportId);

            if (
                !in_array($entity->status, [
                    ReportStatus::Draft->value,
                    ReportStatus::ReturnedForCorrection->value
                ])
            ) {
                throw new CRUDNotFoundException('crud.messages.notfound');
            }
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        $file = $files->get($request->getPost('excel_file'));
        $path = $file->path();
        $originalName = $file->name();

        $tmp = sys_get_temp_dir() . '/' . uniqid('excel_', true) . '_' . $originalName;
        copy((string)$path, $tmp);

        $spreadsheet = IOFactory::load($tmp);

        $properties = $spreadsheet->getProperties();

        $excelHash = $properties->getCustomPropertyValue('identifirer_hash');

        if (!$excelHash) {
            throw new CRUDNotFoundException('Липсва identifirer_hash в Excel файла.');
        }

        $reader = Reader::fromFile($tmp);
        $data = $reader->toArray();

        $excelHash = (string) $excelHash;
        $this->service->importExcel($data, $reportId, $excelHash);
        $this->service->logImports(
            $reportId,
            (int)$request->getPost('excel_file'),
            ReportImportType::report->value
        );

        return (new Response(303))
            ->withHeader(
                'Location',
                $request->getUrl()->linkTo('reports')
            );
    }
    public function getSendReport(Request $request): Response
    {
        $intl = $this->intl;

        try {
            $entity = $this->service->read($request->getUrl()->getSegment(2));

            if (
                !in_array($entity->status, [
                    ReportStatus::Draft->value,
                    ReportStatus::ReturnedForCorrection->value
                ])
            ) {
                throw new CRUDNotFoundException('crud.messages.notfound');
            }

            if (!empty($entity->contracts) && $entity->contracts->contract !== null) {
                $full = $this->service->getContractFullInfo((int)$entity->contracts->contract);
            } else {
                $full = null;
            }

            $contract = $full['contract'] ?? [];
            $reports  = $full['reports'] ?? [];

            $form = new Form();

            $form->addField(new Field(
                'text',
                ['name' => 'contract[contract]', 'value' => $contract['contract_number'] ?? ''],
                ['label' => $intl('contract.number')]
            ))->disable();

            $form->addField(new Field(
                'text',
                ['name' => 'contract[id]', 'value' => $contract['id'] ?? ''],
                ['label' => $intl('eik')]
            ))->disable();

            $form->addField(new Field(
                'text',
                ['name' => 'contract[company_name]', 'value' => $contract['company_name'] ?? ''],
                ['label' => $intl('company.name')]
            ))->disable();

            $form->addField(new Field(
                'text',
                [
                    'name' => 'contract[contract_date]',
                    'value' => $contract['contract_date']
                        ? date('d.m.Y', strtotime($contract['contract_date']) ?: 0)
                        : ''
                ],
                ['label' => $intl('company.contract_date')]
            ))->disable();

            $form->addField(new Field(
                'text',
                [
                    'name' => 'contract[contract_term]',
                    'value' => $contract['contract_term']
                        ? date('d.m.Y', strtotime($contract['contract_term']) ?: 0)
                        : ''
                ],
                ['label' => $intl('company.contract_term')]
            ))->disable();
            $downloadUrl = $request->getUrl()->linkTo(
                $this->module->getSlug() . '/downloadReport/' . $entity->report
            );

            $form->addField(
                (new Field('custom'))
                    ->setName('download_pdf')
                    ->setOption('view', 'reports::downloadButtonPDF')
                    ->setOption('href', $downloadUrl)
                    ->setOption('text', 'Изтегли PDF ')
            );

            $layout = [
                $intl('sendreport.info'),
                ['contract[contract]', 'contract[company_name]', 'contract[id]'],
                ['contract[contract_date]', 'contract[contract_term]']
            ];

            if (empty($reports)) {
                throw new CRUDNotFoundException('Няма отчет.');
            }

            $key = array_search($entity->report, array_column($reports, 'report'));
            $report = $reports[$key] ?? [];
            $reportKey = 'report';

            $form->addField(new Field(
                'select',
                ['name' => "{$reportKey}[status]", 'value' => $report['status']],
                ['label' => $intl('sendreport.status'), 'values' => ReportStatus::options()]
            ))->disable();

            $form->addField(new Field(
                'date',
                ['name' => "{$reportKey}[date_from]", 'value' => $report['date_from']],
                ['label' => $intl('sendReport.date_from')]
            ))->disable();

            $form->addField(new Field(
                'date',
                ['name' => "{$reportKey}[date_to]", 'value' => $report['date_to']],
                ['label' => $intl('sendReport.date_to')]
            ))->disable();

            $layout[] = $intl('Report');
            $layout[] = [
                "{$reportKey}[status]",
                "{$reportKey}[date_from]",
                "{$reportKey}[date_to]"
            ];
            $form->addField(new Field(
                'text',
                ['name' => "{$reportKey}[totalSalary]"],
                ['label' => $intl('sendReport.total_salaries'), 'suffix' => 'currency.' . $report['currency']]
            ))->disable();
            $form->addField(new Field(
                'text',
                ['name' => "{$reportKey}[totalInsurance]"],
                ['label' => $intl('sendReport.total_insurance'), 'suffix' => 'currency.' . $report['currency']]
            ))->disable();
            $form->addField(new Field(
                'text',
                ['name' => "{$reportKey}[totalMonts]"],
                ['label' => $intl('sendReport.total_monts')]
            ))->disable();

            $layout[] = [
                "{$reportKey}[totalSalary]",
                "{$reportKey}[totalInsurance]",
                "{$reportKey}[totalMonts]"
            ];


            $layout[] = 'Декларация';
            $layout[] = ['download_pdf', 'pdf_sign'];

            $totalSalary = 0;
            $totalInsurance = 0;
            $totalMonts = 0;

            $layoutWorkplaces = [];

            foreach ($report['workplaces'] as $wIdx => $workplace) {
                $jobKey = "{$reportKey}[workplace][{$wIdx}]";
                $layoutWorkplaces[] = 'acc:' . 'Работно място' . ' #' . $workplace['workplace_no'];

                $totalSalary += $workplace['total_salary'];
                $totalInsurance += $workplace['total_insurance'];
                $totalMonts += $workplace['total_months'];

                foreach ($workplace['employees'] as $idx => $employee) {
                    $personKey = "{$jobKey}[persons][{$idx}]";

                    $form->addField(new Field(
                        'text',
                        ['name' => "{$personKey}[identifirer]", 'value' => $employee['identifirer']],
                        ['label' => 'ЕГН']
                    ))->disable();

                    $form->addField(new Field(
                        'text',
                        ['name' => "{$personKey}[name]", 'value' => $employee['name']],
                        ['label' => 'Име']
                    ))->disable();

                    $form->addField(new Field(
                        'date',
                        ['name' => "{$personKey}[start_date]", 'value' => $employee['start_date']],
                        ['label' => 'Постъпил']
                    ))->disable();

                    $form->addField(new Field(
                        'date',
                        ['name' => "{$personKey}[end_date]", 'value' => $employee['end_date']],
                        ['label' => 'Напуснал']
                    ))->disable();

                    $form->addField(new Field(
                        'text',
                        ['name' => "{$personKey}[salary]", 'value' => $employee['salary']],
                        ['label' => 'Заплата', 'suffix' => 'currency.' . $report['currency']]
                    ))->disable();

                    $form->addField(new Field(
                        'text',
                        ['name' => "{$personKey}[insurance]", 'value' => $employee['insurance']],
                        ['label' => 'Осигуровки', 'suffix' => 'currency.' . $report['currency']]
                    ))->disable();

                    $layoutWorkplaces[] = 'Служител #' . $employee['workplace_empl'];
                    $layoutWorkplaces[] = [
                        "{$personKey}[identifirer]",
                        "{$personKey}[name]",
                        "{$personKey}[start_date]",
                        "{$personKey}[end_date]",
                        "{$personKey}[salary]",
                        "{$personKey}[insurance]"
                    ];
                }
            }

            $form->getField("{$reportKey}[totalSalary]")->setValue($totalSalary);
            $form->getField("{$reportKey}[totalInsurance]")->setValue($totalInsurance);
            $form->getField("{$reportKey}[totalMonts]")->setValue($totalMonts);

            $layout = array_merge($layout, $layoutWorkplaces);

            $form->addField(new Field(
                'file',
                ['name' => 'pdf_sign'],
                ['label' => $this->moduleName . '.pdf_sign', 'picker' => false]
            ));

            $form->setValidator(
                $this->service->sendReportValidator($form->getValidator())
            );

            $form->setLayout($layout);
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        return (new Response())->setBody(
            $this->render('sendReport', [
                'form' => $form,
                'back' => $request->getUrl()->linkTo(
                    $this->session->get($this->moduleName . '.index', $this->module->getSlug())
                )
            ])
        );
    }
    public function getDownloadReport(Request $request): Response
    {
        $entityId = (int)$request->getUrl()->getSegment(2);
        $entity = $this->service->read($entityId);

        if (!empty($entity->contracts) && $entity->contracts->contract !== null) {
            $full = $this->service->getContractFullInfo((int)$entity->contracts->contract);
        } else {
            $full = null;
        }

        $contract = $full['contract'] ?? [];
        $reports  = $full['reports'] ?? [];

        $key = array_search($entity->report, array_column($reports, 'report'));
        $report = $reports[$key] ?? [];

        $totalSalaries = 0;
        $totalSocialCosts = 0;

        if (!empty($report['workplaces'])) {
            foreach ($report['workplaces'] as $workplace) {
                $totalSalaries += (float)($workplace['total_salary'] ?? 0);
                $totalSocialCosts += (float)($workplace['total_insurance'] ?? 0);
            }
        }

        $rows = [];

        if (!empty($entity->workplaces_report_id)) {
            foreach ($entity->workplaces_report_id as $workplaceReport) {
                $workplaceNo = $workplaceReport->workplace_no ?? '';
                $profession = $workplaceReport->nom_nkpd->name ?? '';

                if (!empty($workplaceReport->workplace_empls)) {
                    foreach ($workplaceReport->workplace_empls as $empl) {
                        $rows[] = [
                            'employee_name' => $empl->employees->name ?? '',
                            'workplace_no'  => $workplaceNo,
                            'start_date'    => !empty($empl->start_date)
                                ? date('d.m.Y', strtotime($empl->start_date) ?: 0)
                                : '',
                            'end_date'      => !empty($empl->end_date)
                                ? date('d.m.Y', strtotime($empl->end_date) ?: 0)
                                : '',
                            'profession'    => $profession,
                        ];
                    }
                } else {
                    $rows[] = [
                        'employee_name' => '',
                        'workplace_no'  => $workplaceNo,
                        'start_date'    => '',
                        'end_date'      => '',
                        'profession'    => $profession,
                    ];
                }
            }
        }

        $submittedBy = $this->service->getUser();

        $data = [
            'contract' => $contract,
            'report'   => $report,
            'rows'     => $rows,
            'submitted_by' => trim($submittedBy),
            'current_date' => date('d.m.Y'),
            'jobs_count' => $report['workplaces_count'] ?? 0,
            'total_salaries_eur' => number_format($totalSalaries, 2, '.', ''),
            'total_social_costs_eur' => number_format($totalSocialCosts, 2, '.', ''),
        ];

        ob_start();
        require __DIR__ . '/views/sendReportPDF.php';
        $html = ob_get_clean();

        $pdf = new \TCPDF();
        $pdf->setCreator('e-INVESTBG');
        $pdf->setAuthor('e-INVESTBG');
        $pdf->setTitle('Report');
        $pdf->setMargins(10, 10, 10);
        $pdf->setAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->setFont('dejavusans', '', 10);
        $pdf->writeHTML($html ?: "", true, false, true, false, '');

        $filename = 'report_' . ($report['report'] ?? '') . '-'
            . ($report['date_from'] ?? '') . '-' . ($report['date_to'] ?? '') . '.pdf';
        $content = $pdf->Output($filename, 'S');

        return (new Response())
            ->withAddedHeader('Content-Type', 'application/pdf')
            ->withAddedHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($content);
    }
    public function postSendReport(Request $request): Response
    {
        try {
            $entity = $this->service->read($request->getUrl()->getSegment(2));
            $validator = $this->service->sendReportValidator(new Validator());
            $errors = $validator->run($request->getPost());
            if (count($errors)) {
                throw (new CRUDException("validation", 400))->setErrors($errors);
            }
            $pdfSign = (int) $request->getPost('pdf_sign', 0);

            if (
                !in_array($entity->status, [
                    ReportStatus::Draft->value,
                    ReportStatus::ReturnedForCorrection->value
                ])
            ) {
                throw new CRUDNotFoundException('crud.messages.notfound');
            }
            //с update метода чисти всички файлове в report_documents
            $this->service->partialUpdate($entity->report, [
                'pdf_sign' => $pdfSign,
                'status' => ReportStatus::Submitted->value,
                'locked' => 1
            ]);
            if ($pdfSign) {
                $this->service->logImports(
                    $entity->report,
                    $pdfSign,
                    ReportImportType::pdf->value
                );
            }
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        return (new Response(303))->withHeader(
            'Location',
            $request->getUrl()->linkTo(
                $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
            )
        );
    }

    public function getSubmitReportMir(
        Request $request,
        PermissionService $permissionService
    ): Response {
        if (!$permissionService->isMIR()) {
            return $this->exceptionResponse(
                $request,
                new CRUDNotFoundException('reports.messages.notfound')
            );
        }
        $form = new Form();
        $data = $this->service->getSubmitMir(
            (int)$request->getUrl()->getSegment(2)
        );
        $form->addField(
            (new Field('custom'))
                ->setName('mir_table')
                ->setValue([
                    'data' => $data,
                    'hideComments' => !$this->service->isINV(),
                    'disableComments' => false,
                ])
                ->setOption('view', 'reports::submitMir')
        );

        return (new Response())->setBody(
            $this->render('submitReportMir', [
                'form' => $form,
                'back' => $request->getUrl()->linkTo(
                    $this->session->get(
                        $this->moduleName . '.index',
                        $this->module->getSlug()
                    )
                )
            ])
        );
    }

    public function postSubmitReportMir(
        Request $request,
        PermissionService $permissionService
    ): Response {
        if (!$permissionService->isMIR()) {
            return $this->exceptionResponse(
                $request,
                new CRUDNotFoundException('reports.messages.notfound')
            );
        }

        try {
            $reportId = (int)$request->getUrl()->getSegment(2);

            $status = (int)$request->getPost('action');
            $generalComment = $request->getPost('general_comment', null);
            $comments = $request->getPost('comments', []);


            $data = [
                'status' => $status,
                'general_comment' => $generalComment
            ];
            if ($status == ReportStatus::ReturnedForCorrection->value) {
                $data['locked'] = 0;
            }

            $this->service->partialUpdate($reportId, $data);

            foreach ($comments as $workplaceEmpl => $comment) {
                $this->service->savePersonComment($reportId, [(int)$workplaceEmpl => $comment]);
            }
        } catch (\Throwable $e) {
            /**
             * @psalm-suppress ArgumentTypeCoercion
             * @phpstan-ignore argument.type
             */
            return $this->exceptionResponse($request, $e);
        }

        return (new Response(303))->withHeader(
            'Location',
            $request->getUrl()->linkTo(
                $this->session->get($this->moduleName . '.index', $this->module->getSlug())
            )
        );
    }

    public function getSubmitedReportRead(Request $request, Config $config): Response
    {
        $form = new Form();
        $data = $this->service->getSubmitMir(
            (int)$request->getUrl()->getSegment(2)
        );

        $entity = $this->service->read((int) $request->getUrl()->getSegment(2));

        $disableComments = in_array($entity->status, [
            ReportStatus::Approved->value,
            ReportStatus::Rejected->value,
        ]) && ($entity->correction_end_date ?? "") > date('Y-m-d')
            && $entity->correction_numb <= $config->getInt('CORRECTION_ATTEMPT');

        $form->addField(
            (new Field('custom'))
                ->setName('mir_table')
                ->setValue([
                    'data' => $data,
                    'hideComments' => !$this->service->isINV(),
                    'disableComments' => !$disableComments,
                ])
                ->setOption('view', 'reports::submitMir')
        );


        return (new Response())->setBody(
            $this->render('submitedReportRead', [
                'form' => $form,
                'back' => $request->getUrl()->linkTo(
                    $this->session->get(
                        $this->moduleName . '.index',
                        $this->module->getSlug()
                    )
                )
            ])
        );
    }

    public function postPartial(Request $request): Response
    {
        try {
            if (!$this->service->hasSpecialPermissions()) {
                throw new CRUDNotFoundException('crud.update.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
            $data = array_merge($this->service->toArray($entity), $request->getPost());
            $entity = $this->service->partialUpdate($entity->report, $data);
        } catch (CRUDException) {
            return (new Response(400));
        }
        return (new Response(200));
    }

    public function getImports(Request $request, Files $files, Uri $url): Response
    {
        $params = $this->normalizeParams($request->getQuery());
        $entities = $this->service->importsList((int)$request->getUrl()->getSegment(2));

        $table = new Table();
        $table->setAttr('x-data-paging', false);
        $table->setAttr('x-data-params', $params);
        $table->setAttr('x-data-filters', []);
        $table->setAttr('x-data-search', false);
        $table->addClass('basic selectable compact table-read');

        $table->addColumn(new TableColumn('report'));
        $table->addColumn(new TableColumn('usr'));
        $table->addColumn(new TableColumn('created_at'));
        $table->addColumn(new TableColumn('type'));
        $table->addColumn(new TableColumn('file_id'));

        foreach ($entities as $v) {
            $table->addRow(
                (new TableRow($v))
                    ->setAttr('id', $v->ri)
                    ->setData($v)
            );
        }

        $table->getColumn('report')
            ->setMap(function (mixed $value, ReportsImportsEntity $entity) use ($url): HTML {
                return new HTML(
                    '<a href="' . $url('reports/read/' . $entity->report) . '">Отчет ' .
                        ($entity->reports->report_number ?? "") . ' / '
                        . $entity->reports->contracts?->period_reporting
                        . ' от договор '
                        . $entity->reports->contracts?->contract_number . ' </a>'
                );
            });

        $table->getColumn('type')
            ->setMap(function (mixed $value, ReportsImportsEntity $row): HTML {
                $types = ReportImportType::tryFrom((int)$row->type);

                return new HTML($types ? $types->label() : '');
            });

        $table
            ->getColumn('created_at')
            ->setMap(function (mixed $v) {
                return $v ?
                    date('d.m.Y H:i', strtotime($v) ?: 0) :
                    '';
            });

        $table->getColumn('usr')
            ->setMap(function (mixed $value, ReportsImportsEntity $entity): HTML {
                return new HTML($entity->users->name);
            });

        $table->getColumn('file_id')
            ->setMap(function (mixed $value, ReportsImportsEntity $entity) use ($files): HTML {
                return new HTML("<a href='" . $files->toLink($entity->file()) . "'>
                    <i class=\"arrow alternate circle down icon\"></i> Свали файла
                </a>");
            });

        return (new Response())->setBody(
            $this->render(
                'imports',
                [
                    'module'     => $this->module,
                    'params'     => $params,
                    'table'      => $table,
                    'created'    => $this->session->get('success') === $this->moduleName . '.messages.created',
                    'updated'    => $this->session->get('success') === $this->moduleName . '.messages.update',
                    'back' => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }

    public function getUploadDocument(Request $request): Response
    {
        try {
            $entity = $this->service->read($request->getUrl()->getSegment(2));

            if (!$this->service->isMIR() && $entity->status == ReportStatus::Approved->value) {
                throw new CRUDNotFoundException('crud.update.notallowed');
            }
            $layout = [];
            $form = new Form();
            $form->addField(new Field(
                'file',
                ['name' => 'mir_doc', 'value' => $entity->mir_doc],
                [
                    'label' => 'reports.columns.mir_doc',
                    'picker' => false,
                    'size' => 1024 * 100 * 100,
                    'types' => 'pdf,doc,docx,jpeg,png'
                ]
            ));

            $form->addField(new Field(
                'file',
                ['name' => 'mir_checklist', 'value' => $entity->mir_checklist],
                [
                    'label' => 'reports.columns.mir_checklist',
                    'picker' => false,
                    'size' => 1024 * 100 * 100,
                    'types' => 'pdf,doc,docx,jpeg,png'
                ]
            ));

            $form->getValidator()
                ->required('mir_doc')
                ->required('mir_checklist')
            ;

            $layout[] = ["mir_doc"];
            $layout[] = ["mir_checklist"];

            $form->setLayout($layout);
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        return (new Response())->setBody(
            $this->render('uploadDocument', [
                'form' => $form,
                'back' => $request->getUrl()->linkTo(
                    $this->session->get($this->moduleName . '.index', $this->module->getSlug())
                )
            ])
        );
    }

    public function postUploadDocument(Request $request): Response
    {
        try {
            $reportId = (int)$request->getUrl()->getSegment(2);
            $entity = $this->service->read($request->getUrl()->getSegment(2));

            if (!$this->service->isMIR() && $entity->status == ReportStatus::Approved->value) {
                throw new CRUDNotFoundException('crud.update.notallowed');
            }

            $this->service->partialUpdate($reportId, [
                'mir_doc' => (int)$request->getPost('mir_doc'),
                'mir_checklist' => (int)$request->getPost('mir_checklist'),
            ]);
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        return (new Response(303))->withHeader(
            'Location',
            $request->getUrl()->linkTo(
                $this->session->get($this->moduleName . '.index', $this->module->getSlug())
            )
        );
    }

    public function getWorkplaces(Request $request, Uri $url): Response
    {
        $entities = $this->service->getWorkplaces((int)$request->getUrl()->getSegment(2));
        // dd($entities->toArray());


        $table = new Table();
        $table->setAttr('x-data-paging', false);
        $table->setAttr('x-data-filters', []);
        $table->setAttr('x-data-search', false);
        $table->addClass('basic selectable compact table-read');

        $table->addColumn(new TableColumn('workplace_no'));
        $table->addColumn(new TableColumn('nkpd'));
        $table->addColumn(new TableColumn('name'));
        $table->addColumn(new TableColumn('status'));
        $table->addColumn(new TableColumn('start_date'));
        $table->addColumn(new TableColumn('end_date'));
        $table->addColumn(new TableColumn('addEmployee'));


        foreach ($entities as $v) {
            $table->addRow(
                (new TableRow($v))
                    ->setAttr('id', $v->workplace)
                    ->setData($v)
            );
        }

        $table
            ->getColumn('start_date')
            ->setMap(function (mixed $v): HTML {
                return $v ?
                    new HTML('<i class="ui clock icon"></i> ' . date('d.m.Y', strtotime($v) ?: 0)) :
                    new HTML('');
            });

        $table
            ->getColumn('end_date')
            ->setMap(function (mixed $v): HTML {
                return $v ?
                    new HTML('<i class="ui clock icon"></i> ' . date('d.m.Y', strtotime($v) ?: 0)) :
                    new HTML('');
            });

        $table->getColumn('status')
            ->setMap(function (mixed $v, stdClass $row): HTML {
                $status = '<span class="ui ' . $row->status_color . ' label">' . $v . '</span>';
                return new HTML($status);
            });

        $table->getColumn('name')
            ->setMap(function (mixed $v, stdClass $row) use ($url): HTML {
                $term =   $row->end_date ? ' (Напуснал)' : ' ';

                return new HTML('<a href="' . $url('employees/read/' . $row->employee) . '">' . $v . $term . '</a>');
            });

        $table->getColumn('addEmployee')
            ->setMap(function (mixed $v, stdClass $row) use ($url): HTML {

                if ($row->statusNumb !== 1 && $row->locked !== 1) {
                    return new HTML(
                        '<a class="ui green mini button" href="' . $url('employees/create/' . $row->report . '/' . $row->workplace) . '">
                <i class="user plus icon"></i>
                Добави служител на място № ' . $row->workplace_no . '
            </a>'
                    );
                }
                return new HTML('');
            });

        return (new Response())->setBody(
            $this->render(
                'workplaces',
                [
                    'module'     => $this->module,
                    'table'      => $table,
                    'icon'       => 'people carry',
                    'breadcrumb' => 'reports.workplaces.title',
                    'created'    => $this->session->get('success') === $this->moduleName . '.messages.created',
                    'updated'    => $this->session->get('success') === $this->moduleName . '.messages.update',
                    'back' => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }
}
