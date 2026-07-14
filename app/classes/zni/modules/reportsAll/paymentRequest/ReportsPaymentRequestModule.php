<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\paymentRequest;

use vakata\config\Config;
use vakata\intl\Intl;
use vakata\views\Views;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use zni\modules\reportsAll\base\BaseModule;

/**
 *  @extends BaseModule<ReportsPaymentRequestService>
 */
class ReportsPaymentRequestModule extends BaseModule
{
    public function __construct(
        ReportsPaymentRequestService $service,
        Config $config,
        Views $views,
        protected Intl $intl,
        string $slug
    ) {
        parent::__construct(
            $service,
            $views,
            $config,
            'paymentRequest',
            $slug,
            'file contract',
            'olive',
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
                'select',
                [ 'name'    => 'reporting_year' ],
                [
                   'label'   => $this->getName() . '.columns.reporting_year',
                   'values' => ['' => 'contractsreports.columns.pick'] + $yearsOptions,
                   'translate' => 1
                ]
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

        return $form
           ->populate($data)
           ->setLayout([
               ['reporting_year', 'kid[]', 'region', 'municipality', 'city'],
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
                return isset($reportingPeriods[(int)$row->period_reporting])
                    ? ($reportingPeriods[(int)$row->period_reporting]['name'] ?? "") : "";
            });

        $table->getColumn('date_application')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->date_application) ?: null);
            });

        return $table;
    }
}
