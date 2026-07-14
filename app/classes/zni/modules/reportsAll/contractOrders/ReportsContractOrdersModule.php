<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\contractOrders;

use vakata\config\Config;
use vakata\intl\Intl;
use vakata\views\Views;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use zni\modules\reportsAll\base\BaseModule;

/**
 *  @extends BaseModule<ReportsContractOrdersService>
 */
class ReportsContractOrdersModule extends BaseModule
{
    public function __construct(
        ReportsContractOrdersService $service,
        Config $config,
        Views $views,
        protected Intl $intl,
        string $slug
    ) {
        parent::__construct(
            $service,
            $views,
            $config,
            'contractOrders',
            $slug,
            'file contract',
            'yellow',
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
                'date',
                [ 'name'    => 'order_date' ],
                [ 'label'   => $this->getName() . '.columns.order_date' ]
            ))
        );
        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'return_date' ],
                [ 'label'   => $this->getName() . '.columns.return_date' ]
            ))
        );

        return $form
           ->populate($data)
           ->setLayout([
               ['order_date', 'return_date'],
           ]);
    }

    protected function getTable(array $data): Table
    {
        $table = parent::getTable($data);
        $table->removeClass('overflowing stuck');

        $table->getColumn('order_date')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->order_date) ?: null);
            });
        $table->getColumn('return_date')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->return_date) ?: null);
            });

        return $table;
    }
}
