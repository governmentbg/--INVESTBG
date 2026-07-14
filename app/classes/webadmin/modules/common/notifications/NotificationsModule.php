<?php

declare(strict_types=1);

namespace webadmin\modules\common\notifications;

use DateTime;
use schema\NotificationsEntity;
use vakata\database\schema\Entity;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use vakata\validation\Validator;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDServiceInterface;
use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;

/**
 * @extends CRUDModule<\schema\NotificationsEntity,NotificationsService>
 */
class NotificationsModule extends CRUDModule implements APIProviderInterface
{
    public const string NAME = 'notifications';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'chat',
            'blue',
            '',
            'notifications',
            namespace\NotificationsController::class,
            namespace\NotificationsService::class,
            __DIR__ . '/views'
        );
    }
    public function onDashboard(): bool
    {
        return false;
    }
    public function inMenu(): bool
    {
        return false;
    }
    public function canRead(): bool
    {
        return true;
    }
    public function canUpdate(): bool
    {
        return false;
    }
    public function canDelete(): bool
    {
        return false;
    }
    public function defaults(): array
    {
        return ['o' => 'sent', 'd' => 1];
    }
    public function listingCallback(Table $table): Table
    {
        $service = $this->getService();
        $recpt = $service->getAvailableRecipients();
        if (!count($recpt)) {
            $table->getOperation('create')->hide();
        }
        $table
            ->removeColumn('notification')
            ->removeColumn('thread')
            ->removeColumn('body')
            ->removeColumn('link')
            ->removeColumn('reply')
            ->removeColumn('mail');
        $table
            ->getColumn('sent')
                ->addClass('left aligned')
                ->setMap(function (mixed $v) {
                    return new HTML(
                        '<i class="ui clock icon"></i> ' .
                        ((($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v)) ?
                            $temp->format('d.m.Y H:i:s') : '')
                        )
                    );
                });
        $table
            ->getColumn('sender')
                ->addClass('left aligned')
                ->setMap(function (mixed $v, NotificationsEntity $data) use ($service) {
                    if ($v === $service->getUser()) {
                        return new HTML('<i class="ui share icon"></i> ' . $data->users?->name);
                    }
                    return $v ?
                        new HTML('<i class="ui user icon"></i> ' . $data->users?->name) :
                        new HTML('<i class="ui server icon"></i> <strong>*</strong>');
                });
        foreach ($table->getRows() as $v) {
            $operations = $v->getOperations(true);
            $temp = [];
            $temp['read'] = $operations['read']->show();
            $v->setOperations($temp);
            if ($v->getData()->sender === $service->getUser()) {
                $v->addClass('warning');
            } elseif (!$v->getData()->notification_recipients[0]->opened) {
                $v->addClass('positive');
            }
        }
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $service = $this->getService();
        $form->removeField('notification');
        $form->getField('body')->setType('textarea');
        $form->getField('thread')->setType('hidden');
        $form->addField(
            (new Field('files', ['name' => 'files'], ['label' => 'notifications.columns.files']))
        );
        $form->getField('reply')->setType('checkbox');
        $form->setLayout([
            [ 'body' ],
            [ 'files' ],
        ]);
        if ($form->getContext('type') === 'read') {
            $form->enable();
            $form->setValidator((new Validator())->required('body'));
            $form->getField('body')->setValue('');
        }
        if ($form->getContext('type') === 'create') {
            $recpt = $service->getAvailableRecipients();
            $form->getField('body')->setType('textarea');
            $form->getField('thread')->setType('hidden');
            $form->getField('files')->setType('files');
            $form->getField('reply')->setType('checkbox');
            $form->addField(
                new Field(
                    'multipleselect',
                    ['name' => 'recipients[]'],
                    ['label' => 'notifications.columns.recipient', 'values' => ['' => ''] + $recpt]
                )
            );
            $form->setLayout([
                [ 'recipients[]' ],
                [ 'title' ],
                [ 'body' ],
                [ 'files' ],
                [ 'reply' ],
            ]);
        }
        $form->populate($form->getContext('data') ?? []);
        if ($form->hasValidator()) {
            $validator = $form->getValidator();
            $form->setValidator($validator->remove('sent'));
        }
        return $form;
    }
}
