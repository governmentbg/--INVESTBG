<?php

declare(strict_types=1);

namespace webadmin\modules\administration\authentication;

use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDServiceInterface;
use vakata\di\DIContainer;

/**
 * @extends CRUDModule<\schema\AuthenticationEntity,AuthenticationService>
 */
class AuthenticationModule extends CRUDModule
{
    public const string NAME = 'authentication';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'lock',
            'red',
            'settings',
            'authentication',
            CRUDController::class,
            namespace\AuthenticationService::class
        );
    }
    public function canCreate(): bool
    {
        return false;
    }
    public function canDelete(): bool
    {
        return false;
    }
    public function listingCallback(Table $table): Table
    {
        $table->setOperations([]);
        $table
            ->removeColumn('authentication')
            ->removeColumn('settings')
            ->removeColumn('position')
            ->getColumn('disabled')
                ->setSortable(false)
                ->setMap(function (mixed $v) {
                    return $v ?
                        '' :
                        new HTML(
                            '<i class="ui check icon"></i>'
                        );
                });
        $table
            ->getColumn('authenticator')
            ->setSortable(false);
        $table
            ->getColumn('conditions')
                ->setSortable(false)
                ->setMap(function (mixed $v) {
                    return $v && strlen($v) ?
                        new HTML(
                            '<i class="ui check icon"></i>'
                        ) :
                        '';
                });
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $form->removeField('authentication');
        $form->getField('authenticator')->disable();
        $form->getField('conditions')->setType('textarea');
        $form->getField('position')->setType('number');
        $form->getField('settings')->setType('textarea')->setAttr('rows', 12);
        $form->getField('disabled')
            ->setType('select')
            ->setOption('values', [0 => 'yes', 'no'])
            ->setOption('translate', true);
        $form->setLayout([
            ['authenticator', 'disabled', 'position'],
            ['settings'],
            ['conditions']
        ]);
        return $form;
    }
}
