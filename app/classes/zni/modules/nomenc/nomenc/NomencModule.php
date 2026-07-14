<?php

declare(strict_types=1);

namespace zni\modules\nomenc\nomenc;

use vakata\di\DIContainer;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use schema\NomCertificateTypesEntity;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDController;

/**
 * @extends CRUDModule<\schema\NomCertificateTypesEntity, \zni\modules\nomenc\nomenc\NomencService>
 */
class NomencModule extends CRUDModule
{
    public const string NAME = 'nomencModule';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'folder open outline',
            'blue',
            'nom',
            'nom_certificate_types',
            CRUDController::class,
            \zni\modules\nomenc\nomenc\NomencService::class
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

    public function listingDefaults(): array
    {
        return [];
        // return [ 'o' => 'created', 'd' => 1 ];
    }

    public function listingCallback(Table $table): Table
    {
        $service = $this->getService();


        return $table;
    }

    public function formCallback(Form $form): Form
    {
        $service = $this->getService();

        return $form;
    }
}
