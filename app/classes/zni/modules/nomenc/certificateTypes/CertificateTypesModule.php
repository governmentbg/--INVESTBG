<?php

declare(strict_types=1);

namespace zni\modules\nomenc\certificateTypes;

use schema\NomCertificateTypesEntity;
use vakata\di\DIContainer;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDModule<NomCertificateTypesEntity, CRUDService<NomCertificateTypesEntity>>
 */
class CertificateTypesModule extends CRUDModule
{
    public const string NAME = 'certificateTypes';

    public function __construct(DIContainer $container, string $slug = '')
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'certificate',
            'orange',
            'nomenc',
            'nom_certificate_types',
            CRUDController::class,
            CRUDService::class
        );
    }
}
