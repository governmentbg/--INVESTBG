<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\contractBanks;

use vakata\config\Config;
use vakata\intl\Intl;
use vakata\views\Views;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use zni\modules\reportsAll\base\BaseModule;

/**
 *  @extends BaseModule<ReportsContractBanksService>
 */
class ReportsContractBanksModule extends BaseModule
{
    public function __construct(
        ReportsContractBanksService $service,
        Config $config,
        Views $views,
        protected Intl $intl,
        string $slug
    ) {
        parent::__construct(
            $service,
            $views,
            $config,
            'contractBanks',
            $slug,
            'file contract',
            'purple',
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
                [ 'name'    => 'from_date' ],
                [ 'label'   => $this->getName() . '.columns.from_date' ]
            ))
        );
        $form->addField(
            (new Field(
                'date',
                [ 'name'    => 'to_date' ],
                [ 'label'   => $this->getName() . '.columns.to_date' ]
            ))
        );

        $form->addField(
            (new Field(
                'text',
                [ 'name'    => 'bank' ],
                [ 'label'   => $this->getName() . '.columns.bank' ]
            ))
        );

        return $form
           ->populate($data)
           ->setLayout([
               ['from_date', 'to_date', 'bank'],
           ]);
    }

    protected function getTable(array $data): Table
    {
        $table = parent::getTable($data);
        $table->removeClass('overflowing stuck');

        $table->getColumn('from_date')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->from_date) ?: null);
            });
        $table->getColumn('to_date')
            ->setMap(function (string $value, \stdClass $row) {
                return date('d.m.Y', strtotime($row->to_date) ?: null);
            });

        return $table;
    }
}
