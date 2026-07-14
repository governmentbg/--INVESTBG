<?php

declare(strict_types=1);

namespace zni\modules\nomenc\reportingPeriods;

use schema\NomReportingPeriodsEntity;
use vakata\di\DIContainer;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDService;

/**
 * @extends CRUDModule<NomReportingPeriodsEntity, CRUDService<NomReportingPeriodsEntity>>
 */
class ReportingPeriodsModule extends CRUDModule
{
    public const string NAME = 'reportingPeriods';

    public function __construct(DIContainer $container, string $slug = '')
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'calendar alternate outline',
            'blue',
            'nomenc',
            'nom_reporting_periods',
            CRUDController::class,
            CRUDService::class
        );
    }
}
