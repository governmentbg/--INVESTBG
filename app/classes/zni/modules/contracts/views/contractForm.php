<?php

use webadmin\components\html\Form;
use webadmin\components\html\Field;
use zni\modules\contracts\ContractsService;

return function (
    array $contract,
    ?array $report,
    array $workplaces,
    array $kid,
    callable $intl,
    ContractsService $service
) {

    $form   = new Form();
    $layout = [];

    $please = ['' => $intl('please.select')];

    /*
    |--------------------------------------------------------------------------
    | CONTRACT
    |--------------------------------------------------------------------------
    */

    $layout[] = 'Договор';

    $form->addField(
        (new Field('select', [
            'name'  => 'contract_type',
            'value' => $contract['contract_type'] ?? null,
        ], []))
            ->setOption('values', $please + (array)$service->getContractTypes())
            ->setAttr('readonly', true)
    );

    $form->addField(
        (new Field('select', [
            'name'  => 'company',
            'value' => $contract['company'] ?? null,
        ], ['label' => 'contracts.columns.company']))
            ->setOption('values', $please + (array)$service->getCompanies())
            ->setAttr('readonly', true)
    );

    $form->addField(
        (new Field('select', [
            'name'  => 'company_type',
            'value' => $contract['company_type'] ?? null,
        ], ['label' => 'contracts.columns.company_type']))
            ->setOption('values', $please + (array)$service->getCompanyTypes())
            ->setAttr('readonly', true)
    );

    $form->addField((new Field('date', [
        'name'  => 'date_application',
        'value' => $contract['date_application'] ?? null,
    ], ['label' => 'contracts.columns.date_application']))->setAttr('readonly', true));

    $form->addField(
        (new Field('select', [
            'name'  => 'sector',
            'value' => $contract['sector'] ?? null,
        ], ['label' => 'contracts.columns.sector']))
            ->setOption('values', $please + (array)$service->getSectors())
            ->setAttr('readonly', true)
    );

    $selectedSector = (int)($contract['sector'] ?? 0);
    $form->addField(
        (new Field('select', [
            'name'  => 'sector_activity',
            'value' => $contract['sector_activity'] ?? null,
        ], ['label' => 'contracts.columns.sector_activity']))
            ->setOption('values', $please + (array)$service->getSectorActivities($selectedSector))
            ->setAttr('readonly', true)
    );

    $form->addField((new Field('date', [
        'name'  => 'cert_date',
        'value' => $contract['cert_date'] ?? null,
    ], ['label' => 'contracts.columns.cert_date']))->setAttr('readonly', true));

    $form->addField((new Field('date', [
        'name'  => 'cert_expire',
        'value' => $contract['cert_expire'] ?? null,
    ], ['label' => 'contracts.columns.cert_expire']))->setAttr('readonly', true));

    $form->addField((new Field('text', [
        'name'  => 'cert_number',
        'value' => $contract['cert_number'] ?? null,
    ], ['label' => 'contracts.columns.cert_number']))->setAttr('readonly', true));

    $form->addField(
        (new Field('select', [
            'name'  => 'cert_type',
            'value' => $contract['cert_type'] ?? null,
        ], ['label' => 'contracts.columns.cert_type']))
            ->setOption('values', $please + (array)$service->getCertificateTypes())
            ->setAttr('readonly', true)
    );

    $form->addField(
        (new Field(
            'multipleselect',
            ['name' => 'kid[]', 'value' => $kid,],
            [
                'label' => '.columns.kid',
                'values' => ['' => $intl('please.select.kid')] + $service->getKid()
            ]
        ))->setAttr('readonly', true)
    );

    $form->addField((new Field('text', [
        'name'  => 'contract_number',
        // 'value' => $contract['contract_number'] ?? null,
    ], ['label' => 'contracts.columns.contract_number'])));

    $form->addField((new Field('date', [
        'name'  => 'contract_date',
        // 'value' => $contract['contract_date'] ?? null,
    ], ['label' => 'contracts.columns.contract_date'])));

    $form->addField((new Field('date', [
        'name'  => 'contract_term',
        // 'value' => $contract['contract_term'] ?? null,
    ], ['label' => 'contracts.columns.contract_term'])));

    $form->addField(
        (new Field('select', [
            'name'  => 'period_reporting',
            // 'value' => $contract['period_reporting'] ?? null,
        ], ['label' => 'contracts.columns.period_reporting']))
            ->setOption('values', $please + (array)$service->getReportingPeriod())
        // ->setAttr('readonly', true)
    );

    $form->addField(
        (new Field('select', [
            'name'  => 'currency',
            'value' => $contract['currency'] ?? null,
        ], ['label' => 'contracts.columns.currency']))
            ->setOption('values', (array)$service->currencies())
            ->setAttr('readonly', true)
    );

    $form->addField((new Field('number', [
        'name'  => 'period_value',
        'value' => $contract['period_value'] ?? null,
        'step'  => '0.01',
    ], ['label' => 'contracts.columns.period_value']))->setAttr('readonly', true));

    $form->addField((new Field('number', [
        'name'  => 'invest_amount',
        'value' => $contract['invest_amount'] ?? null,
        'step'  => '0.01',
    ], ['label' => 'contracts.columns.invest_amount']))->setAttr('readonly', true));

    $form->addField((new Field('number', [
        'name'  => 'number_persons',
        'value' => $contract['number_persons'] ?? null,
    ], ['label' => 'contracts.columns.number_persons']))->setAttr('readonly', true));

    $form->addField((new Field('date', [
        'name'  => 'period_maintenance_start',
        'value' => $contract['period_maintenance_start'] ?? null,
    ], ['label' => 'contracts.columns.period_maintenance_start']))->setAttr('readonly', true));

    $form->addField((new Field('date', [
        'name'  => 'period_maintenance_end',
        'value' => $contract['period_maintenance_end'] ?? null,
    ], ['label' => 'contracts.columns.period_maintenance_end']))->setAttr('readonly', true));

    $form->addField((new Field('date', [
        'name'  => 'period_invest_start',
        'value' => $contract['period_invest_start'] ?? null,
    ], ['label' => 'contracts.columns.period_invest_start']))->setAttr('readonly', true));

    $form->addField((new Field('date', [
        'name'  => 'period_invest_end',
        'value' => $contract['period_invest_end'] ?? null,
    ], ['label' => 'contracts.columns.period_invest_end']))->setAttr('readonly', true));

    $layout[] = ['contract_type'];
    $layout[] = 'text.company';
    $layout[] = ['company', 'company_type'];
    $layout[] = 'text.data.contract';
    $layout[] = ['date_application', 'sector', 'sector_activity'];
    $layout[] = ['kid[]'];
    $layout[] = ['cert_date', 'cert_expire', 'cert_number', 'cert_type'];
    $layout[] = ['contract_number', 'contract_date', 'contract_term'];
    $layout[] = ['period_reporting:2', 'currency:2', 'period_value:3', 'invest_amount:5', 'number_persons:4'];
    $layout[] = ['period_maintenance_start', 'period_maintenance_end', 'period_invest_start', 'period_invest_end'];

    /*
    |--------------------------------------------------------------------------
    | REPORT
    |--------------------------------------------------------------------------
    */

    if ($report) {
        $layout[] = 'Отчет';

        $form->addField((new Field('text', [
            'name'  => 'report_number',
            'value' => $report['report_number'] + 1,
        ], ['label' => 'report.number']))->setAttr('readonly', true));

        $form->addField((new Field('text', [
            'name'  => 'workplaces',
            'value' => $report['workplaces'],
        ], ['label' => 'report.workplaces']))->setAttr('readonly', true));

        $form->addField((new Field('date', [
            'name'  => 'report_date_from',
        ], ['label' => 'report.start_date'])));

        $form->addField((new Field('date', [
            'name'  => 'report_date_to',
        ], ['label' => 'report.end_date'])));


        $form->addField(new Field(
            'number',
            ['name' => 'percent_second'],
            ['label' => $intl('percent_second')]
        ));

        $form->addField(new Field(
            'number',
            ['name' => 'percent_third'],
            ['label' => $intl('percent_third')]
        ));

        $form->getField('percent_second')
            ->setType('number')
            ->setOption('suffix', '%');
        $form->getField('percent_third')
            ->setType('number')
            ->setOption('suffix', '%');

        $layout[] = [
            'report_number',
            'workplaces',
            'report_date_from',
            'report_date_to',
            'percent_second',
            'percent_third'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | WORKPLACES
    |--------------------------------------------------------------------------
    */

    foreach ($workplaces as $wIndex => $workplace) {
        $workplaceKey = "workplaces[{$wIndex}]";
        $layout[] = 'Работни места и служители';
        $layout[] = 'acc:Работно място ' . ((int)$wIndex + 1);

        $form->addField((new Field('text', [
            'name'  => "{$workplaceKey}[workplace]",
            'value' => $workplace['workplace_no'] ?? null,
        ], ['label' => 'workplace.id']))->setAttr('readonly', true));


        $getNkpd = $service->getNKPD();
        $form->addField((new Field('select', [
            'name'  => "{$workplaceKey}[position_id]",
            'value' => $workplace['position_id'] ?? null,
        ], ['label' => 'workplace.position_id', 'values' => $getNkpd]))->setAttr('readonly', true));

        // $form->addField((new Field('text', [
        //     'name'  => "{$workplaceKey}[address]",
        //     'value' => $workplace['address'] ?? null,
        // ], ['label' => 'workplace.address']))->setAttr('readonly', true));

        $layout[] = [
            "{$workplaceKey}[workplace]",
            "{$workplaceKey}[position_id]"
        ];

        // $layout[] = [
        //     "{$workplaceKey}[address]"
        // ];

        /*
        |----------------------------------------------------------------------
        | EMPLOYEES
        |----------------------------------------------------------------------
        */

        foreach (($workplace['employees'] ?? []) as $eIndex => $employee) {
            $employeeKey = "{$workplaceKey}[employees][{$eIndex}]";

            $layout[] = 'Служител ' . ($eIndex + 1);

            $form->addField((new Field('text', [
                'name'  => "{$employeeKey}[employee]",
                'value' => $employee['employee'] ?? $employee['employee_id'] ?? null,
            ], ['label' => 'employee.id']))->setAttr('readonly', true));

            $form->addField((new Field('text', [
                'name'  => "{$employeeKey}[name]",
                'value' => $employee['name'] ?? null,
            ], ['label' => 'employee.name']))->setAttr('readonly', true));



            $form->addField(
                (new Field('select', [
                    'name'  => "{$employeeKey}[identifirer_type]",
                    'value' => $employee['identifirer_type'] ?? '',
                ], ['label' => $intl('person.identifirer_type')]))
                    ->setOption('values', [
                        ''  => $intl('please.select.identifier'),
                        '1' => $intl('egn'),
                        '2' => $intl('lnch'),
                        '3' => $intl('no.identifier')
                    ])
                    ->setAttr('readonly', true)
            );

            $form->addField((new Field('text', [
                'name'  => "{$employeeKey}[identifirer]",
                'value' => $employee['identifirer'] ?? null,
            ], ['label' => $intl('person.identifier')]))->setAttr('readonly', true));


            $layout[] = [
                "{$employeeKey}[employee]",
                "{$employeeKey}[name]",
                "{$employeeKey}[identifirer_type]",
                "{$employeeKey}[identifirer]"
            ];


            // $salary = $employee['salary'] ?? null;

            // if ($salary) {
            //     $layout[] = 'Заплата';

            //     $form->addField((new Field('number', [
            //         'name'  => "{$employeeKey}[salary][salary]",
            //         'value' => $salary['salary'] ?? $salary['amount'] ?? null,
            //         'step'  => '0.01',
            //     ], ['label' => 'salary.amount']))->setAttr('readonly', true));

            //     $form->addField((new Field('number', [
            //         'name'  => "{$employeeKey}[salary][insurance]",
            //         'value' => $salary['insurance'] ?? null,
            //         'step'  => '0.01',
            //     ], ['label' => 'salary.insurance']))->setAttr('readonly', true));

            //     $form->addField((new Field('number', [
            //         'name'  => "{$employeeKey}[salary][months]",
            //         'value' => $salary['months'] ?? null,
            //     ], ['label' => 'salary.months']))->setAttr('readonly', true));

            //     $layout[] = [
            //         "{$employeeKey}[salary][salary]",
            //         "{$employeeKey}[salary][insurance]",
            //         "{$employeeKey}[salary][months]"
            //     ];
            // }
        }
    }

    $validator = $form->getValidator();
    $validator->required('contract_date');
    $validator->required('contract_term');
    $validator->required('contract_number');
    $validator->required('report_date_from');
    $validator->required('report_date_to');
    $validator->required('period_reporting');
    $validator->required('percent_second');
    $validator->required('percent_third');

    $form->setValidator($validator);

    $form->setLayout($layout);

    return $form;
};
