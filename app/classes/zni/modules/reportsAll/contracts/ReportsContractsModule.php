<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\contracts;

use vakata\config\Config;
use vakata\intl\Intl;
use vakata\validation\Validator;
use vakata\views\Views;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use zni\modules\reportsAll\base\BaseModule;

/**
 *  @extends BaseModule<ReportsContractService>
 */
class ReportsContractsModule extends BaseModule
{
    public function __construct(
        ReportsContractService $service,
        Config $config,
        Views $views,
        protected Intl $intl,
        string $slug
    ) {
        parent::__construct(
            $service,
            $views,
            $config,
            'contractsReports',
            $slug,
            'file contract',
            'green',
            'reports'
        );
    }

    protected function getForm(array $data = []): Form
    {
        $form = parent::getForm($data);
        $intl = $this->intl;

        $yearsOptions = $this->service->yearsOptions(25);

        $form
           ->addField(
               (new Field(
                   'hidden',
                   [ 'name' => 'submit' ]
               ))
               ->setValue(1)
           );

        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'contract_date_from' ],
                [ 'label'   => $this->getName() . '.columns.contract_date_start' ]
            ))
        );
        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'contract_date_to' ],
                [ 'label'   => $this->getName() . '.columns.contract_date_end' ]
            ))
        );

        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'start_project_date' ],
                [ 'label'   => $this->getName() . '.columns.start_project_date' ]
            ))
        );
        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'to_project_date' ],
                [ 'label'   => $this->getName() . '.columns.to_project_date' ]
            ))
        );

        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'completion_date_from' ],
                [ 'label'   => $this->getName() . '.columns.completion_date_from' ]
            ))
        );
        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'completion_date_to' ],
                [ 'label'   => $this->getName() . '.columns.completion_date_to' ]
            ))
        );

        $form->addField(
            (new Field(
                'multipleselect',
                [ 'name'    => 'kid[]' ],
                [
                   'label'   => $this->getName() . '.columns.kid',
                   'values'  => ['' => 'contractsreports.columns.pick'] + $this->service->nomenc('kid'),
                   'translate' => 1
                ]
            ))
        );

        $form->addField(
            (new Field(
                'select',
                [ 'name'    => 'region', 'data-redraw' => 1 ],
                [
                   'label' => $this->getName() . '.columns.region',
                   'values' => ['' => 'contractsreports.columns.pick'] + $this->service->nomenc('region'),
                   'translate' => 1
                ]
            ))
        );
        $form->addField(
            (new Field(
                'select',
                [ 'name'    => 'municipality', 'data-redraw' => 1 ],
                [
                   'label' => $this->getName() . '.columns.municipality',
                   'values' => ['' => 'contractsreports.columns.pick'],
                   'translate' => 1
                ]
            ))->disable()
        );

        $form->addField(
            (new Field(
                'select',
                [ 'name'    => 'city' ],
                [
                   'label'   => $this->getName() . '.columns.city',
                   'values' => ['' => 'contractsreports.columns.pick'],
                   'translate' => 1
                ]
            ))->disable()
        );

        if (isset($data['region']) && (int) $data['region']) {
            $form->getField('municipality')
                ->setOption('values', ['' => $this->getName() . '.columns.pick']
                 + $this->service->nomenc('municipalities', (int) $data['region']))
                ->enable();
        }
        if (isset($data['municipality']) && (int) $data['municipality']) {
            $form->getField('city')
                ->setOption('values', ['' =>  $this->getName() . '.columns.pick']
                 + $this->service->nomenc('cities', (int) $data['municipality']))
                ->enable();
        }

        $form->addField(
            (new Field(
                'number',
                [ 'name'    => 'contract_amount_from' ],
                [ 'label'   => $this->getName() . '.columns.contract_amount_from' ]
            ))
        );
        $form->addField(
            (new Field(
                'number',
                [ 'name'    => 'contract_amount_to' ],
                [ 'label'   => $this->getName() . '.columns.contract_amount_to' ]
            ))
        );

        $form->addField(
            (new Field(
                'select',
                [ 'name'    => 'employment_reporting_period_from' ],
                [
                   'label'   => $this->getName() . '.columns.employment_reporting_period_from',
                   'values' => ['' => 'contractsreports.columns.pick'] + $yearsOptions,
                   'translate' => 1
                ]
            ))
        );
        $form->addField(
            (new Field(
                'select',
                [ 'name'    => 'employment_reporting_period_to' ],
                [
                   'label'   => $this->getName() . '.columns.employment_reporting_period_to',
                   'values' => ['' => 'contractsreports.columns.pick'] + $yearsOptions,
                   'translate' => 1
                ]
            ))
        );
        $form->addField(
            (new Field(
                'select',
                [ 'name'    => 'еmployment_maintenance_period_from' ],
                [
                   'label'   => $this->getName() . '.columns.еmployment_maintenance_period_from',
                   'values' => ['' => 'contractsreports.columns.pick'] + $yearsOptions,
                   'translate' => 1
                ]
            ))
        );
        $form->addField(
            (new Field(
                'select',
                [ 'name'    => 'еmployment_maintenance_period_to' ],
                [
                   'label'   => $this->getName() . '.columns.еmployment_maintenance_period_to',
                   'values' => ['' => 'contractsreports.columns.pick'] + $yearsOptions,
                   'translate' => 1
                ]
            ))
        );

        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'application_date_from' ],
                [ 'label'   => $this->getName() . '.columns.application_date_from' ]
            ))
        );
        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'application_date_to' ],
                [ 'label'   => $this->getName() . '.columns.application_date_to' ]
            ))
        );

        $validator = new Validator();
        $validator->required('employment_reporting_period_from', $intl('field.required'));
        $validator->required('employment_reporting_period_to', $intl('field.required'));
        // $validator->required('еmployment_maintenance_period_from', $intl('field.required'));
        // $validator->required('еmployment_maintenance_period_to', $intl('field.required'));

        return $form->setValidator($validator)
           ->populate($data)
           ->setLayout([
               ['contract_date_from', 'contract_date_to'],
               ['start_project_date', 'to_project_date', 'completion_date_from', 'completion_date_to'],
               ['kid[]'],
               ['region', 'municipality', 'city'],
               ['contract_amount_from', 'contract_amount_to'],
               [
                   'employment_reporting_period_from', 'employment_reporting_period_to',
                   'еmployment_maintenance_period_from', 'еmployment_maintenance_period_to'
               ],
               ['application_date_from','application_date_to']
           ]);
    }


    protected function getTable(array $data): Table
    {
        if (isset($data['kid'])) {
            $data['kid'] = array_filter($data['kid']);
        }
        $table = parent::getTable($data);

        /** @var array $reportingPeriods */
        $reportingPeriods = $this->service->nomenc('reporting_period');

        $table->getColumn('contract_date')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->contract_date) ?: null);
            });
        $table->getColumn('cert_expire')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->cert_expire) ?: null);
            });
        $table->getColumn('contract_term')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->contract_term) ?: null);
            });

        $table->getColumn('city')
            ->setMap(function (string $value, \stdClass $row) {
                return $row->region_name . ', ' . $row->municipality_name . ', ' . $row->city_name;
            });

        $table->getColumn('period_maintenance')
            ->setMap(
                function (string $value, \stdClass $row): string {
                    return date('d.m.Y', strtotime((string)$row->period_maintenance_start) ?: null) . ' - ' .
                       ($row->period_maintenance_end ?
                        date('d.m.Y', strtotime((string)$row->period_maintenance_end) ?: null)
                        : "");
                }
            );

        $table->getColumn('cert_expire')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->cert_date) ?: null) . ' - '
                    . date('d.m.Y', strtotime($row->cert_expire) ?: null);
            });

        $table->getColumn('period_reporting')
            ->setMap(function (string $value, \stdClass $row) use ($reportingPeriods) {
                return isset($reportingPeriods[$row->period_reporting])
                    ? ($reportingPeriods[$row->period_reporting]['name'] ?? "") : "";
            });

        $table->getColumn('date_application')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->date_application) ?: null);
            });

        return $table;
    }
}
