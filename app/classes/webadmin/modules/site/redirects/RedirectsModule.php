<?php

declare(strict_types=1);

namespace webadmin\modules\site\redirects;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDServiceInterface;
use webadmin\modules\common\crud\CRUDModule;
use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;

/**
 * @extends CRUDModule<\schema\RedirectsEntity,RedirectsService>
 */
class RedirectsModule extends CRUDModule implements APIProviderInterface
{
    public const string NAME = 'redirects';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'random',
            'green',
            'cms',
            'redirects',
            CRUDController::class,
            namespace\RedirectsService::class
        );
    }
    public function listingCallback(Table $table): Table
    {
        $table = parent::listingCallback($table);
        $table
            ->removeColumn('redirect')
            ->removeColumn('site')
            ->getColumn('rtype')
                ->setMap(function (mixed $v) {
                    $types = [
                        'none' => 'remove',
                        'temporary' => 'check circle outline',
                        'permanent' => 'check circle'
                    ];
                    return new HTML(
                        '<i class="ui ' . $types[$v] . ' icon"></i>'
                    );
                });
        foreach ($table->getRows() as $v) {
            $v->removeOperation('read');
        }
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $form = parent::formCallback($form);
        $form->removeField('redirect');
        $form->removeField('site');
        $form->getField('rtype')
            ->setType('select')
            ->setOption('values', [
                'none' => 'none',
                'temporary' => 'temporary',
                'permanent' => 'permanent'
            ]);
        return $form;
    }
}
