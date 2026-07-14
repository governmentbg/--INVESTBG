<?php

declare(strict_types=1);

namespace zni\modules\sampleDocuments;

use base\components\files\Files;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column;
use schema\SampleDocumentsEntity;
use vakata\di\DIContainer;
use webadmin\components\html\Button;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDService;
use zni\permission\PermissionService;

/**
 * @extends CRUDModule<\schema\SampleDocumentsEntity,CRUDService<\schema\SampleDocumentsEntity>>
 */
class SampleDocumentsModule extends CRUDModule
{
    public const string NAME = 'sample_documents';
    protected Files $files;
    public function __construct(
        DIContainer $container,
        protected PermissionService $permisionService,
        Files $files,
        string $slug = '',
    ) {
        $this->files = $files;
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'copy',
            'red',
            'contracts',
            'sample_documents',
            CRUDController::class,
            CRUDService::class
        );
    }

    public function canRead(): bool
    {
        return true;
    }

    public function canCreate(): bool
    {
        return !$this->permisionService->isINV();
    }

    public function canUpdate(): bool
    {
        return !$this->permisionService->isINV();
    }
    public function canDelete(): bool
    {
        return !$this->permisionService->isINV();
    }

    public function listingCallback(Table $table): Table
    {

        $table = parent::listingCallback($table);
        $table->removeColumn('file');

        $table->addColumn(
            (new TableColumn('download'))

                ->setMap(function (mixed $value, SampleDocumentsEntity $entity): HTML {
                    $file = $entity->uploads->file();

                    return new HTML(
                        '<a href="' . $this->files->toLink($file) . '">
                    <i class="arrow alternate circle down icon"></i> Свали файла
                </a>'
                    );
                })
        );

        return $table;
    }

    public function formCallback(Form $form): Form
    {
        $form = parent::formCallback($form);
        $form->getField('file')->setType('file')->setOption('picker', false);

        return $form;
    }
}
