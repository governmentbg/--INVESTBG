<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var \webadmin\modules\VisualModuleInterface $module
 * @var string $breadcrumb
 * @var string $back
 * @var string $title
 * @var string $name
 * @var \vakata\http\Uri $url
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 * @var array $entities
 * @var \schema\MaintenanceReportsEntity $entity
 */
?>
<?php $this->layout('webadmin::main', [
        'breadcrumb' => '<i class="' . $this->e($icon ?? 'pencil') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'maintenancereports.breadcrumb.check_persons']))
    ]);
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
     <h3 class="ui left floated header impersonate-header">
        <i class="envelope icon"></i>
        <span class="content"><?= $this->e($intl('maintenancereports.breadcrumb.check_persons')) ?></span>
    </h3>

    <a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
</div>
<?php $this->stop() ?>


<div class="ui segment">
    <div class="ui stackable two column row crud-header">
        <h3><?= $intl('maintenancereports.position_subtitle', [$entity->contract, $entity->mr]) ?></h3>
        <table class="ui celled table">
            <thead>
                <tr>
                    <th><?= $this->e($intl('emplpositions.columns.job_number')) ?></th>
                    <th><?= $this->e($intl('emplpositions.columns.kid')) ?></th>
                    <th><?= $this->e($intl('emplpositions.columns.nkpd')) ?></th>
                    <th><?= $this->e($intl('emplpositions.columns.region')) ?></th>
                    <th><?= $this->e($intl('emplpositions.columns.municipality')) ?></th>
                    <th><?= $this->e($intl('emplpositions.columns.city')) ?></th>
                    <th><?= $this->e($intl('emplpositions.columns.persons')) ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                
            </tbody>
        </table>
    </div>
</div>
