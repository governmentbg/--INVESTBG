<?php

declare(strict_types=1);

namespace zni\modules\insurableIncomes;

use DateTime;
use schema\InsurableIncomeEntity;
use vakata\database\DB;
use vakata\di\DIContainer;
use vakata\intl\Intl;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use zni\enums\LaborCategory;

/**
* @extends CRUDModule<\schema\InsurableIncomeEntity,InsurableIncomesService>
*/
class InsurableIncomesModule extends CRUDModule
{
    public const string NAME = 'insurableIncomes';
    public function __construct(
        DIContainer $container,
        protected Intl $intl,
        string $slug = ''
    ) {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'wallet',
            'red',
            'contracts',
            'insurable_income',
            CRUDController::class,
            namespace\InsurableIncomesService::class,
        );
    }

    public function hasHistory(): bool
    {
        return true;
    }
    public function canCreate(): bool
    {
        return $this->getService()->canCreate();
    }

    public function canUpdate(): bool
    {
        return $this->getService()->canUpdate();
    }

    public function canDelete(): bool
    {
        return false;
    }

    public function listingCallback(Table $table): Table
    {
        $table = parent::listingCallback($table);
        $service = $this->getService();

        $table->getColumn('category')
            ->setFilter(
                (new Form())
                    ->addField(
                        new Field(
                            'select',
                            ['name' => 'category'],
                            [
                                'label'  => $this->name . '.filters.category',
                                'values' => $service->laborCategoryOptions()
                            ]
                        )
                    )
            )
            ->setMap(
                function (mixed $value, InsurableIncomeEntity $entity): HTML {
                    $status = LaborCategory::tryFrom((int)$entity->category);

                    $text = $status ? $status->label() : '';
                    return new HTML($text);
                }
            );

        $table->getColumn('from_date')
                ->setMap(function (mixed $v) {
                    return new HTML(
                        '<i class="ui clock icon"></i> ' .
                        (($temp = DateTime::createFromFormat('Y-m-d', $v)) ?
                            $temp->format('d.m.Y') : ''
                        )
                    );
                });

        $table->getColumn('to_date')
                ->setMap(function (mixed $v) {
                    return new HTML(
                        '<i class="ui clock icon"></i> ' .
                        (($temp = DateTime::createFromFormat('Y-m-d', $v)) ?
                            $temp->format('d.m.Y') : ''
                        )
                    );
                });

        $table->setOrder(['from_date', 'to_date', 'category', 'max_income', 'percent_insurance']);

        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $form = parent::formCallback($form);
        $service = $this->getService();
        $entity = $form->getContext('entity', null);

        $form->getField('category')
            ->setType('select')
            ->setOption('values', $service->laborCategoryOptions());

        $db = $this->container->instance(DB::class);
        //todo: unique from_date, to_date, category
        // if ($form->hasValidator()) {
        //     $validator = $form->getValidator();
        //     $validator->required('year')
        //         ->callback(function ($value, $data) use($db, $entity) {
        //             $q = $db->table('insurable_income')
        //                 ->filter('year', $data['year']);
        //             if(!is_null($entity)) {
        //                 $q->filter('ii', $entity->ii);
        //             }
        //             $res = $q->count();

        //         return $res == 0;
        //     }, $this->intl->get('insurableincomes.validations.unique'), 'unique');
        //     $form->setValidator($validator);
        // }

        $form->setLayout([
            ['from_date', 'to_date', 'category'],
            ['max_income', 'percent_insurance']
        ]);

        return $form;
    }
}
