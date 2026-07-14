<?php

declare(strict_types=1);

namespace webadmin\modules\administration\log;

use DateTime;
use schema\LogEntity;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;

/**
 * @extends CRUDModule<\schema\LogEntity,LogService>
 */
class LogModule extends CRUDModule implements APIProviderInterface
{
    public const string NAME = 'log';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'book',
            'blue',
            'settings',
            'log',
            CRUDController::class,
            namespace\LogService::class
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
        $table->removeOperation('import');
        $table->removeOperation('export');
        $table
            ->removeColumn('id')
            ->removeColumn('request')
            ->removeColumn('response')
            ->removeColumn('context')
            ->removeColumn('usr')
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
            ->getColumn('usr_name')
                ->setQuickFilter('usr')
                ->setMap(function (mixed $v, LogEntity $row) use ($users) {
                    if (!$v) {
                        return '';
                    }
                    $user = $users[$row->usr] ?? null;
                    if ($user && $user['avatar_data']) {
                        return new HTML(
                            '<img class="ui avatar image" src="' . $user['avatar_data'] . '"> ' . $v
                        );
                    } else {
                        return new HTML('<i class="ui user icon"></i> ' . $v);
                    }
                })
                ->setFilter(
                    (new \webadmin\components\html\Form())
                        ->addField(
                            new \webadmin\components\html\Field(
                                'select',
                                [ 'name' => 'usr' ],
                                [
                                    'label' => 'log.columns.usr',
                                    'values' => array_map(function (array $v): string {
                                        return $v['name'];
                                    }, $users)
                                ]
                            )
                        )
                );
        $table
            ->getColumn('ip')
                ->setMap(function (mixed $v, LogEntity $row) {
                    $req = explode('User-Agent:', $row->request ?? '', 2);
                    $mob = false;
                    if (count($req) > 1) {
                        $req = explode("\n", $req[1], 2)[0];
                        // @codingStandardsIgnoreLine
                        if (preg_match('/Mobile|iP(hone|od|ad)|Android|BlackBerry|IEMobile|Kindle|NetFront|Silk-Accelerated|(hpw|web)OS|Fennec|Minimo|Opera M(obi|ini)|Blazer|Dolfin|Dolphin|Skyfire|Zune/', $req)) {
                            $mob = true;
                        }
                    }
                    return new HTML('<i class="ui ' . ($mob ? 'tablet' : 'computer') . ' icon"></i> ' . $v);
                });
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
                    $value = [];
                    if (strpos($msg, '[IDS]') !== false) {
                        $value[] = '<div class="ui red horizontal label">IDS</div>';
                    }
                    if (strpos($msg, '[POST]') !== false) {
                        $value[] = '<div class="ui orange horizontal label">POST</div>';
                    }
                    if (strpos($msg, '[DELETE]') !== false) {
                        $value[] = '<div class="ui orange horizontal label">DELETE</div>';
                    }
                    if (strpos($msg, '[PUT]') !== false) {
                        $value[] = '<div class="ui orange horizontal label">PUT</div>';
                    }
                    if (strpos($msg, '[OPTIONS]') !== false) {
                        $value[] = '<div class="ui green horizontal label">OPTIONS</div>';
                    }
                    if (strpos($msg, '[HEAD]') !== false) {
                        $value[] = '<div class="ui green horizontal label">HEAD</div>';
                    }
                    if (strpos($msg, '[GET]') !== false) {
                        $value[] = '<div class="ui green horizontal label">GET</div>';
                    }
                    if (strpos($msg, '[CSP]') !== false) {
                        $value[] = '<div class="ui yellow horizontal label">CSP</div>';
                    }
                    if (strpos($msg, '[ECT]') !== false) {
                        $value[] = '<div class="ui yellow horizontal label">ECT</div>';
                    }
                    if (strpos($msg, '[XSS]') !== false) {
                        $value[] = '<div class="ui yellow horizontal label">XSS</div>';
                    }
                    if (strpos($msg, '[CSRF]') !== false) {
                        $value[] = '<div class="ui purple horizontal label">CSRF</div>';
                    }
                    preg_replace_callback('(\[(\d+)\])', function (array $matches) use (&$value) {
                        $value[] = '' .
                            '<div class="ui blue horizontal label">' .
                                (int) $matches[1] .
                            '</div>';
                        return '';
                    }, $msg);
                    $matches = [];
                    if (preg_match('( (/[^ ]*))ui', $msg, $matches)) {
                        $value[] = '<div class="ui horizontal label">' . htmlspecialchars($matches[1]) . '</div>';
                    }
                    $msg = preg_replace('(\[[^\]]+\])', '', $msg) ?? '';
                    $msg = preg_replace('( (/[^ ]*))ui', '', $msg, 1) ?? '';
                    $msg = trim($msg);
                    if (strlen($msg) > 50) {
                        $msg = mb_substr($msg, 0, 47) . ' &hellip;';
                    }
                    $value[] = $msg;
                    return new HTML(implode(' ', $value));
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

        $layout = [
            [ 'created', 'lvl', 'usr_name', 'ip' ],
            [ 'message' ],
            [ 'context' ]
        ];
        $form
            ->removeField('id')
            ->removeField('usr');
        $form->getField('message')->setType('textarea');
        $form->getField('context')->setType('code');
        $form->getField('lvl')->setOption(
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
        if ($form->hasField('request')) {
            $form->getField('request')->setType('code');
            $layout[] = ['request'];
        }
        if ($form->hasField('response')) {
            $form->getField('response')->setType('code');
            $layout[] = ['response'];
        }
        return $form->setLayout($layout);
    }
}
