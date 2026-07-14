<?php

declare(strict_types=1);

namespace webadmin\modules\administration\maildb;

use DateTime;
use webadmin\components\html\Button;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDServiceInterface;
use vakata\di\DIContainer;

/**
 * @extends CRUDModule<\schema\MailsEntity,MailDBService>
 */
class MailDBModule extends CRUDModule
{
    public const string NAME = 'maildb';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'mail',
            'teal',
            'settings',
            'mails',
            namespace\MailDBController::class,
            namespace\MailDBService::class
        );
    }
    public function canCreate(): bool
    {
        return false;
    }
    public function canUpdate(): bool
    {
        return false;
    }
    public function canDelete(): bool
    {
        return false;
    }
    public function canRead(): bool
    {
        return false;
    }
    public function listingCallback(Table $table): Table
    {
        $service = $this->getService();
        $table->removeOperation('create');
        $table
            ->removeColumn('mail')
            ->removeColumn('started')
            ->removeColumn('content');
        $table
            ->getColumn('added')
                ->addClass('center aligned')
                ->setMap(function (mixed $v) {
                    return ($v && ($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v)) ?
                        $temp->format('d.m.Y H:i:s') : '');
                });
        $table
            ->getColumn('finished')
                ->addClass('center aligned')
                ->setMap(function (mixed $v) {
                    return ($v && ($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v)) ?
                        $temp->format('d.m.Y H:i:s') : '');
                });
        if (!$service->hasQueue()) {
            $table
                ->removeColumn('finished')
                ->removeColumn('priority')
                ->removeColumn('result');
        }
        foreach ($table->getRows() as $v) {
            $operations = [];
            $operations['download'] = (new Button("download"))
                ->setLabel(self::NAME . '.operations.download')
                ->setIcon('mail')
                ->setClass('skip blank mini blue icon button')
                ->setAttr('href', $this->slug . '/download/' . $v->getAttr('id'));
            $v->setOperations($operations);
        }
        return $table;
    }
}
