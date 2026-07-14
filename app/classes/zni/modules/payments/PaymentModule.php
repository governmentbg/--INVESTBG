<?php

declare(strict_types=1);

namespace zni\modules\payments;

use schema\PaymentsEntity;
use vakata\di\DIContainer;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;

/**
* @extends CRUDModule<\schema\PaymentsEntity,PaymentService>
*/
class PaymentModule extends CRUDModule
{
    public const string NAME = 'payments';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'euro sign',
            'green',
            'contracts',
            'payments',
            CRUDController::class,
            namespace\PaymentService::class,
        );
    }
    public function canRead(): bool
    {
        return false;
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

        $table->getColumn('contract')
            ->setMap(function (mixed $v, PaymentsEntity $row) {
                $contracts = $row->contracts;
                return $contracts->contract . ' / '
                    . date('d.m.Y', strtotime($contracts->contract_date ?? "") ?: null)
                    . ' / ' . $contracts->companies->company_name;
            });

        $table->getColumn('payment_date')
            ->setMap(function (mixed $v, PaymentsEntity $row) {
                return date('d.m.Y', strtotime($row->payment_date) ?: null);
            });

        foreach ($table->getRows() as $v) {
            $row = $v->getData();
            $v->getOperation('history')?->show();
        }

        $table->setOrder(['contract', 'payment_date', 'amount']);
        return $table;
    }

    public function formCallback(Form $form): Form
    {
        $form = parent::formCallback($form);

        $form->getField('contract')
            ->setType('select')
            ->setOption('values', ['' => 'payments.columns.pick'] + $this->getService()->getContracts())
            ->setOption('translate', 1);

        return $form;
    }
}
