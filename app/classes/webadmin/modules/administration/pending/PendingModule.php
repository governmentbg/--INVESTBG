<?php

declare(strict_types=1);

namespace webadmin\modules\administration\pending;

use webadmin\components\html\Button;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDServiceInterface;
use vakata\di\DIContainer;

/**
 * @extends CRUDModule<\schema\UserPendingEntity,PendingService>
 */
class PendingModule extends CRUDModule
{
    public const string NAME = 'pending';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'user plus',
            'teal',
            'administration',
            'user_pending',
            namespace\PendingController::class,
            namespace\PendingService::class,
            __DIR__ . '/views'
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
    public function listingCallback(Table $table): Table
    {
        $service = $this->getService();
        $table->setOperations([]);
        $table
            ->removeColumn('usrpend');
        $table->getColumn('details')->setMap(function (string $v): string {
            $v = json_decode($v, true) ?? [];
            $v = [ $v['name'] ?? '', $v['mail'] ?? ''];
            return implode(' / ', array_filter($v));
        });
        foreach ($table->getRows() as $row) {
            if ($service->isUserAdmin()) {
                $row->addOperation(
                    (new Button("user"))
                        ->setLabel($this->name . '.operations.user')
                        ->setIcon('user plus')
                        ->setClass('mini orange icon button')
                        ->setAttr('href', $this->slug . '/user/' . $row->getAttr('id'))
                );
            }
            $operations = $row->getOperations();
            $operations = [
                'user' => $operations['user'] ?? null
            ];
            $row->setOperations(array_filter($operations));
        }
        return $table;
    }
}
