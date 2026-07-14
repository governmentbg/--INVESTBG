<?php

declare(strict_types=1);

namespace webadmin\modules\administration\uploads;

use DateTime;
use schema\UploadsEntity;
use vakata\database\schema\Entity;
use webadmin\components\html\Button;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDServiceInterface;
use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;
use webadmin\modules\PermissionsModuleInterface;

/**
 * @extends CRUDModule<\schema\UploadsEntity,UploadsService>
 */
class UploadsModule extends CRUDModule implements PermissionsModuleInterface, APIProviderInterface
{
    public const string NAME = 'uploads';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'file',
            'yellow',
            'other',
            'uploads',
            namespace\UploadsController::class,
            namespace\UploadsService::class,
            __DIR__ . '/views'
        );
    }
    public function inMenu(): bool
    {
        return false;
    }
    public function onDashboard(): bool
    {
        return false;
    }
    public function permissions(): array
    {
        return [ 'uploads/master' ];
    }
    public function canCreate(): bool
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
        $user = $service->getUser();
        $filters = [
            [ 'search' => '?users.usr=' . $user['id'], 'name' => $user['name'] ]
        ];
        foreach ($service->getCollections() as $k => $v) {
            $filters[] = [ 'search' => '?collections.collection=' . $k, 'name' => $v ];
        }
        $table->setAttr('x-data-filters', $filters);
        $table->getOperation('thumb')->show()->setClass('left floated purple icon button thumb-button');
        $table->removeOperation('import');
        $table->removeOperation('export');
        $table
            ->removeColumn('id')
            ->removeColumn('hash')
            ->removeColumn('location')
            ->removeColumn('data')
            ->removeColumn('settings')
            ->removeOperation('create');
        $table
            ->getColumn('name')
            ->setQuickFilter(null)
            ->setMap(function (string $v, UploadsEntity $row) use ($service) {
                $i = preg_match('(\.(jpg|jpeg|png|bmp)$)i', $v);
                try {
                    $u = $service->getFileLink((string)($row->id));
                    $r = $service->getFileLink((string)($row->id), [ 'w' => 280 ]);
                    return new HTML('<div class="thumb" data-url="' . htmlspecialchars($u) . '">' .
                        ($i ?
                            '<img src="' . htmlspecialchars($r) . '" />' :
                            ''
                        ) .
                    '</div>' . strip_tags($v));
                } catch (\Exception) {
                    return $v;
                }
            });
        $table
            ->getColumn('bytesize')
                ->addClass('right aligned')
                ->setMap(function (mixed $v) {
                    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
                    $factor = (int)floor((strlen((string)$v) - 1) / 3);
                    return sprintf("%.3f", $v / pow(1024, $factor)) . ' ' . ($size[$factor] ?? '');
                });
        $table
            ->getColumn('uploaded')
                ->addClass('center aligned')
                ->setMap(function (mixed $v) {
                    return (($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v)) ?
                        $temp->format('d.m.Y H:i:s') : '');
                });
        $collections = $service->getCollections();
        $table
            ->addColumn(
                (new TableColumn('collections.collection'))
                    ->setMap(function (mixed $k, UploadsEntity $row) use ($collections) {
                        $tags = [];
                        foreach ($row->collections as $collection) {
                            /** @psalm-suppress PossiblyNullPropertyFetch */
                            if (isset($collections[$collection->collection])) {
                                $tags[] = '<span class="ui horizontal label">' .
                                    $collections[$collection->collection] . '</span>';
                            }
                        }
                        return new HTML(implode('', $tags));
                    })
                    ->setSortable(false)
                    ->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "multipleselect",
                                [ 'name' => 'collections.collection[]' ],
                                [ 'label' => $this->name . '.filters.collection', 'values' => $collections ]
                            ))
                    )
            );
        $table
            ->addColumn(
                (new TableColumn('users.usr'))
                    ->setSortable(false)
                    ->setMap(function (mixed $k, UploadsEntity $row) {
                        /** @phpstan-ignore-next-line */
                        return ($row->users[0] ?? null)?->name ?? '-';
                    })
            );
        foreach ($table->getRows() as $v) {
            $operations = $v->getOperations();
            $operations['download'] = (new Button("download"))
                ->setLabel($this->name . '.operations.download')
                ->setIcon('download')
                ->setClass('skip blank mini purple icon button')
                ->setAttr('href', $this->slug . '/download/' . $v->getAttr('id'));
            $temp = [
                'download' => $operations['download'],
                'update' => $operations['update']
            ];
            $v->setOperations($temp);
        }
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $service = $this->getService();
        $form->removeField('id');
        $form->removeField('data');
        $form->removeField('location');
        $form->getField('settings')->setType('textarea');
        if ($service->isMaster()) {
            $form->setLayout([
                ['name'],
                ['bytesize', 'uploaded', 'hash'],
                ['settings']
            ]);
        } else {
            $form->removeField('settings');
            $form->setLayout([
                ['name'],
                ['bytesize', 'uploaded', 'hash']
            ]);
        }
        if ($form->getContext('type') === 'update') {
            if ($service->isMaster()) {
                $form->addField(
                    new Field(
                        'file',
                        ['name' => 'temp'],
                        ['label' => 'uploads.newfile', 'picker' => false, 'multipart' => ['temp' => 1]]
                    )
                );
                $form->addField(
                    new Field(
                        'multipleselect',
                        ['name' => 'colls[]'],
                        [
                            'label' => 'uploads.collections',
                            'values' => $service->getCollections()
                        ]
                    )
                );
                $form->setLayout([
                    ['name'],
                    ['bytesize', 'uploaded', 'hash'],
                    ['settings'],
                    ['temp'],
                    ['colls[]']
                ]);
            } else {
                $form->removeField('settings');
                $form->disable();
                $form->addField(
                    new Field(
                        'multipleselect',
                        ['name' => 'colls[]'],
                        [
                            'label' => 'uploads.collections',
                            'values' => $service->getCollections(true)
                        ]
                    )
                );
                $form->setLayout([
                    ['name'],
                    ['bytesize', 'uploaded', 'hash'],
                    ['colls[]']
                ]);
            }
            $entity = $form->getContext('entity', null);
            if ($entity) {
                $form->populate($service->toArray($entity));
            }
            $form->populate($form->getContext('data', []));
        }
        return $form;
    }
}
