<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\amountsPaidToInvestors;

use vakata\config\Config;
use vakata\intl\Intl;
use vakata\views\Views;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use zni\modules\reportsAll\base\BaseModule;

/**
 *  @extends BaseModule<ReportsAmountsPaidToInvestorsService>
 */
class ReportsAmountsPaidToInvestorsModule extends BaseModule
{
    public function __construct(
        ReportsAmountsPaidToInvestorsService $service,
        Config $config,
        Views $views,
        protected Intl $intl,
        string $slug
    ) {
        parent::__construct(
            $service,
            $views,
            $config,
            'amountsPaidToInvestors',
            $slug,
            'file contract',
            'red',
            'reports'
        );
    }

    protected function getForm(array $data = []): Form
    {
        $form = parent::getForm($data);

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
                [ 'name'    => 'company' ],
                [
                   'label' => $this->getName() . '.columns.company',
                   'values' => ['' => 'contractsreports.columns.pick'] + $this->service->nomenc('companies'),
                   'translate' => 1
                ]
            ))
        );

        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'start_date' ],
                [ 'label'   => $this->getName() . '.columns.start_date' ]
            ))
        );
        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'end_date' ],
                [ 'label'   => $this->getName() . '.columns.end_date' ]
            ))
        );

        return $form
           ->populate($data)
           ->setLayout([
               ['company', 'start_date', 'end_date'],
           ]);
    }

    protected function getTable(array $data): Table
    {
        $table = parent::getTable($data);
        $table->removeClass('overflowing stuck');

        $table->getColumn('contract_date')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->contract_date) ?: null);
            });
        $table->getColumn('contract_term')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->contract_term) ?: null);
            });
        $table->getColumn('payment_date')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->payment_date) ?: null);
            });

        return $table;
    }
}
