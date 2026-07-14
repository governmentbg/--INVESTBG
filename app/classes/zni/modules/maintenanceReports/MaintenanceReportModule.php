<?php

declare(strict_types=1);

namespace zni\modules\maintenanceReports;

use schema\MaintenanceReportsEntity;
use vakata\config\Config;
use vakata\di\DIContainer;
use vakata\http\Request;
use vakata\http\Uri;
use vakata\intl\Intl;
use webadmin\components\html\Button;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDModule;
use zni\enums\MaintenanceReportStatus;
use zni\enums\ReportStatus;

/**
 * @extends CRUDModule<\schema\MaintenanceReportsEntity,MaintenanceReportService>
 */
class MaintenanceReportModule extends CRUDModule
{
    public const string NAME = 'maintenanceReports';
    public function __construct(
        DIContainer $container,
        protected Config $config,
        protected Intl $intl,
        string $slug = ''
    ) {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'chart bar',
            'green',
            'contracts',
            'maintenance_reports',
            namespace\MaintenanceReportController::class,
            namespace\MaintenanceReportService::class,
            __DIR__ . '/views'
        );
    }

    public function canCreate(): bool
    {
        return $this->getService()->isInv();
    }

    public function canUpdate(): bool
    {
        $uri = $this->container->instance(Uri::class);
        $id = $uri->getSegment(2);

        if (!$id) {
            return true;
        }

        $entity = $this->getService()->read($id);

        return $this->getService()->canUpdate($entity);
    }

    public function canRead(): bool
    {
        return true;
    }
    public function canDelete(): bool
    {
        return false;
    }
    public function hasHistory(): bool
    {
        return true;
    }

    public function listingCallback(Table $table): Table
    {
        $table = parent::listingCallback($table);
        $url = $this->container->instance(Uri::class);

        $table->removeColumn('dme_prev_year');
        $table->removeColumn('sme_prev_year');
        $table->removeColumn('report_nra_prev_year');
        $table->removeColumn('document_annual_reporting_statistics');
        $table->removeColumn('other');
        $table->removeColumn('report_comment');
        $table->removeColumn('persons_comments');
        $table->removeColumn('correction_attempt');
        $table->removeColumn('last_sync');
        $table->removeColumn('pdf_sign');

        $table->addColumn(
            (new TableColumn('ID'))
                ->setMap(function (mixed $value, MaintenanceReportsEntity $row): HTML {
                    return new HTML("" . $row->mr);
                })
        );

        $table->getColumn('contract')
            ->setMap(function (mixed $v, MaintenanceReportsEntity $row) use ($url): HTML {
                return new HTML('<a href="' . $url('contracts/read/' . $row->contract) . '">' . 'Договор '
                            . $row->contracts->contract_number . '</a>');
            });

        $table->getColumn('status')
            ->setMap(function (mixed $value, MaintenanceReportsEntity $row): HTML {
                $status = ReportStatus::tryFrom((int)$row->status);

                return new HTML($status ? $status->label() : '');
            });
        $table
            ->getColumn('correction_date')
            ->setMap(function (mixed $v) {
                return $v ?
                     date('d.m.Y', strtotime($v) ?: 0) :
                     '';
            });

        $table
            ->getColumn('date_from')
            ->setMap(function (mixed $v) {
                return $v ?
                     date('d.m.Y', strtotime($v) ?: 0) :
                     '';
            });

        $table
            ->getColumn('date_to')
            ->setMap(function (mixed $v) {
                return $v ?
                     date('d.m.Y', strtotime($v) ?: 0) :
                     '';
            });

        foreach ($table->getRows() as $v) {
            $row = $v->getData();
            $v->getOperation('history')?->show();

            if (!$this->getService()->canUpdate($v->getData())) {
                $v->getOperation('update')?->hide();
            }

            //add person
            if (
                in_array($row->status, [
                    MaintenanceReportStatus::Draft->value,
                    MaintenanceReportStatus::ReturnedForCorrection->value
                ]) && $this->getService()->isInv()
            ) {
                $v->addOperation((new Button('addPerson'))
                    ->setLabel($this->name . '.operations.addPerson')
                    ->setIcon('child')
                    ->setClass('skip mini green icon button')
                    ->setAttr(
                        'href',
                        $url('employees/create/' . $row->contracts->reports[0]->report . '/0/' . $row->mr)
                    ));
            }
        }

        $table->setOrder(['ID', 'contract', 'date_from', 'date_to', 'status']);
        return $table;
    }

    public function formCallback(Form $form): Form
    {
        $service = $this->getService();
        /** @var Request $req */
        $req = $this->container->get(Request::class);

        $contracts = $service->getContracts();

        $form->getField('contract')
            ->setType('select')
            ->setAttr('data-redraw', '1')
            ->setOption('values', $contracts);

        $contractId = (int) $form->getField('contract')->getValue('');
        if ($contractId == 0) {
            $contractId = (int) array_key_first($contracts);
        }

        if ($form->getContext('type') === 'create' && !$service->hasApproveReport($contractId)) {
            $form->addField(
                (new Field('custom'))
                    ->setName('missing_empl_reports')
                    ->setOption('view', 'maintenanceReports::missing_empl_reports')
            );
            $form->setLayout([
                ['contract'],
                ['missing_empl_reports']
            ]);
            return $form;
        }
        $entity = $form->getContext('entity');
        if ($req->getUrl()->getSegment(1) == 'history') {
            $entity = $service->read($req->getUrl()->getSegment(2));
        }

        $disableComments = $req->getUrl()->getSegment(1) == 'history'
                || (!is_null($entity)
                && ($entity->status == MaintenanceReportStatus::ReturnedForCorrection->value
                && ($entity->correction_date ?? "") > date('Y-m-d')
                    && $entity->correction_attempt <= $this->config->getInt('CORRECTION_ATTEMPT')));

        $hideComments = (!is_null($entity)
            && ($entity->status == MaintenanceReportStatus::UnderReview->value
                || $entity->status == MaintenanceReportStatus::ReturnedForCorrection->value)
            ) || ($req->getUrl()->getSegment(1) == 'history')
              || ($req->getUrl()->getSegment(1) == 'read');

        $form->removeField('persons_comments');
        $form->removeField('correction_date');
        $form->removeField('correction_attempt');

        if ($form->getContext('type') == 'create') {
            $personData = $service->getPersonsByContract($contractId);
        } else {
            $personData = $service->getPersonsByMaintenanceReport($entity->mr);
        }
        $kids = $service->getNomKidsByContract($contractId);

        $totalAmountUnsuportedPositions = $service->totalAmountUnsuportedPositions($personData['positions']);
        $form->addField(
            (new Field('custom'))
                ->setName('total_refund_amount')
                ->setValue($totalAmountUnsuportedPositions)
                ->setOption('view', 'maintenanceReports::total_refund_amount')
        );

        $form->addField(
            (new Field('custom'))
                ->setName('persons_view')
                ->setValue([
                    'kids' => $kids,
                    'persons' => $personData['positions'],
                    'hideComments' => $hideComments && $service->isMIR(),
                    'disableComments' => $disableComments
                ])
                ->setOption('view', 'maintenanceReports::empl_person')
        );

        $form->getField('date_from')
            ->setType('date');
        $form->getField('date_to')
            ->setType('date');

        $periodMaintenance = $service->getPeriod($contractId);
        if ($periodMaintenance) {
            $form->getField('date_from')
                ->setValue($periodMaintenance['date_from']);
        }

        $form->getField('report_comment')->setType('textarea');

        $form->getField('status')
            ->setType('select')
            ->disable()
            ->setOption('values', MaintenanceReportStatus::options());

        $maxSize = $this->config->getInt('MAX_FILE_SIZE');
        $allowFileTypes = 'pdf,jpg,png,xlsx,xls';

        $form->getField('dme_prev_year')
                ->setType('file')
                ->setOption('picker', false)
                ->setOption('types', $allowFileTypes)
                ->setOption('size', $maxSize);
        $form->getField('sme_prev_year')
                ->setType('file')
                ->setOption('picker', false)
                ->setOption('types', 'xlsx,xls')
                ->setOption('size', $maxSize);
        $form->getField('report_nra_prev_year')
                ->setType('file')
                ->setOption('picker', false)
                ->setOption('types', $allowFileTypes)
                ->setOption('size', $maxSize);
        $form->getField('document_annual_reporting_statistics')
                ->setType('file')
                ->setOption('picker', false)
                ->setOption('types', $allowFileTypes)
                ->setOption('size', $maxSize);
        $form->getField('other')
                ->setType('files')
                ->setOption('picker', false)
                ->setOption('types', $allowFileTypes)
                ->setOption('size', $maxSize);

        if ($disableComments) {
            $form->getField('report_comment')->disable();
        }

        if (!$hideComments) {
            $form->removeField('report_comment');
        }

        $showPdfSignDocument = $entity && $entity->status == MaintenanceReportStatus::Draft->value;

        if ($showPdfSignDocument) {
            $downloadUrl = $req->getUrl()->linkTo(
                $this->slug . '/downloadReport/' . $entity->mr
            );

            $form->addField(
                (new Field('custom'))
                    ->setName('download_pdf')
                    ->setOption('view', 'maintenanceReports::downloadButtonPDF')
                    ->setOption('href', $downloadUrl)
                    ->setOption('text', 'Изтегли PDF ')
            );

            $form->getField('pdf_sign')
                ->setType('file')
                ->setOption('picker', false);
        } else {
            $form->getField('pdf_sign')->hide();
        }

        //TODO: for production
        // if($form->hasValidator()) {
        //     $validator = $form->getValidator();
        //     $validator
        //         ->required('dme_prev_year', $this->intl->get('field.required'))
        //         ->required('sme_prev_year', $this->intl->get('field.required'))
        //         ->required('report_nra_prev_year', $this->intl->get('field.required'))
        //         ->required('document_annual_reporting_statistics', $this->intl->get('field.required'));
        //     $form->setValidator($validator);
        // }

        if (
            !is_null($entity) && !in_array($entity->status, [
            MaintenanceReportStatus::Draft->value,
            MaintenanceReportStatus::ReturnedForCorrection->value])
        ) {
            foreach (
                [
                'contract',
                'date_from',
                'date_to',
                'dme_prev_year',
                'sme_prev_year',
                'report_nra_prev_year',
                'document_annual_reporting_statistics',
                'other'] as $field
            ) {
                $form->getField($field)->disable();
            }
            $form->getValidator()
                ->remove('contract');
        }

        $layout = [
            ['contract'],
            ['date_from', 'date_to', 'status'],
            ['total_refund_amount'],
            ['persons_view'],
            ['dme_prev_year'],
            ['sme_prev_year'],
            ['report_nra_prev_year'],
            ['document_annual_reporting_statistics'],
            ['other']
        ];

        if ($showPdfSignDocument) {
            $layout[] = 'Декларация';
            $layout[] = ['download_pdf', 'pdf_sign'];
        } else {
            $layout[] = ['pdf_sign'];
        }

        $layout[] = ['report_comment'];

        $form->setLayout($layout);

        if ($entity) {
            $form->populate($service->toArray($entity));
        }
        $form->populate($form->getContext('data', []));

        return $form;
    }
}
