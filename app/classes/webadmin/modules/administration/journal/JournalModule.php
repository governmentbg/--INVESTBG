<?php

declare(strict_types=1);

namespace webadmin\modules\administration\journal;

use DateTime;
use schema\LogSystemEntity;
use vakata\collection\Collection;
use vakata\database\schema\Entity;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDModuleInterface;
use webadmin\modules\common\crud\CRUDServiceInterface;
use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;

/**
 * @extends CRUDModule<\schema\LogSystemEntity,JournalService>
 */
class JournalModule extends CRUDModule implements APIProviderInterface
{
    public const string NAME = 'journal';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'search',
            'teal',
            'administration',
            'log_system',
            CRUDController::class,
            namespace\JournalService::class
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
        return true;
    }
    public function listingCallback(Table $table): Table
    {
        $service = $this->getService();
        $table
            ->removeColumn('id')
            ->removeColumn('context')
            ->removeColumn('module_id')
            ->removeOperation('create');
        $table
            ->getColumn('created')
                ->addClass('left aligned')
                ->setMap(function (mixed $v) {
                    return new HTML(
                        '<i class="ui clock icon"></i> ' .
                        (($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v)) ?
                            $temp->format('d.m.Y H:i:s') : '')
                    );
                });
        $users = $service->users();
        $table
            ->getColumn('usr')
                ->setQuickFilter('usr')
                ->setMap(function (mixed $v, LogSystemEntity $row) use ($users) {
                    if (!$v) {
                        return '';
                    }
                    $user = $users[$row->usr] ?? '';
                    return $user ? new HTML('<i class="ui user icon"></i> ' . $user) : '';
                })
                ->setFilter(
                    (new \webadmin\components\html\Form())
                        ->addField(
                            new \webadmin\components\html\Field(
                                'select',
                                [ 'name' => 'usr' ],
                                [
                                    'label' => 'log.columns.usr',
                                    'values' => ['' => ''] + $users
                                ]
                            )
                        )
                );
        $table
            ->getColumn('lvl')
                ->addClass('center aligned')
                ->setMap(function (mixed $v) {
                    switch ($v) {
                        case 'alert':
                        case 'critical':
                        case 'error':
                        case 'emergency':
                            $v = '' .
                                '<div class="ui medium red horizontal label">' .
                                    '<i class="ui exclamation icon"></i>'  . $v .
                                '</div>';
                            break;
                        case 'warning':
                            $v = '' .
                            '<div class="ui orange horizontal label">' .
                                '<i class="ui warning icon"></i>'  . $v .
                            '</div>';
                            break;
                        case 'notice':
                            $v = '' .
                                '<div class="ui teal horizontal label">' .
                                    '<i class="ui warning icon"></i>'  . $v .
                                '</div>';
                            break;
                        case 'info':
                        case 'debug':
                            $v = '' .
                                '<div class="ui olive horizontal label">' .
                                    '<i class="ui pencil icon"></i>'  . $v .
                                '</div>';
                            break;
                        default:
                            $v = '';
                            break;
                    }
                    return new HTML($v);
                });
        $table
            ->getColumn('message')
                ->setMap(function (mixed $v) {
                    $msg = (string)$v;
                    $msg = trim($msg);
                    if (strlen($msg) > 50) {
                        $msg = mb_substr($msg, 0, 47) . ' &hellip;';
                    }
                    return new HTML($msg);
                });
        $modules = $service->modules();
        $table
            ->getColumn('module')
                ->setMap(function (mixed $v) use ($modules): HTML {
                    return new HTML($modules[(string)$v]['name'] ?? '');
                });
        foreach ($table->getRows() as $v) {
            if ($v->getData()->lvl === 'warning') {
                $v->addClass('warning');
            }
            if (in_array($v->getData()->lvl, ['error', 'alert', 'critical', 'emergency'])) {
                $v->addClass('error');
            }
        }
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $service = $this->getService();
        $layout = [
            [ 'created', 'lvl', 'usr' ],
            [ 'message' ],
            [ 'context' ],
            [ 'module', 'module_id' ]
        ];
        $form
            ->removeField('id');
        $form->getField('message')->setType('textarea');
        $form->getField('context')->setType('textarea');
        $form->getField('usr')->setType('select')->setOption(
            'values',
            $service->users()
        );
        $form->getField('lvl')->setType('select')->setOption(
            'values',
            [
                'emergency' => 'emergency',
                'alert' => 'alert',
                'critical' => 'critical',
                'error' => 'error',
                'warning' => 'warning',
                'notice' => 'notice',
                'info' => 'info',
                'debug' => 'debug'
            ]
        );
        $form->setLayout($layout);
        if ($form->getContext('type') === 'read') {
            $entity = $form->getContext('entity');
            $module = (string)($entity?->module);
            if ($module && $m = $service->modules()[$module]) {
                $form->getField('module')->setType('hidden');
                $form->getField('module_id')
                    ->setType('module')
                    ->setOption('url', $m['slug'])
                    ->setOption('id', $m['pk']);
            }
        }
        return $form;
    }
}
