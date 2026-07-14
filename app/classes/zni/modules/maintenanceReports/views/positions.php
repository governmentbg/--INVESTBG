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
            $this->e($intl([$breadcrumb, 'maintenancereports.breadcrumb.positions']))
    ]);
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
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
                <?php foreach ($entities as $p) : ?>
                <tr>
                    <td><?= $this->e($p['job_code']) ?></td>
                    <td><?= implode('<br/>', $p['kid_names']) ?></td>
                    <td><?= $this->e($p['nkpd'] . ' ' . $p['nkpd_name']) ?></td>
                    <td><?= $this->e($p['region_name']) ?></td>
                    <td><?= $this->e($p['municipality_name']) ?></td>
                    <td><?= $this->e($p['city_name']) ?></td>
                    <td><?= $this->e($p['person_count']) ?></td>
                    <td>
                        <a href="<?= $url('emplPersons/create/' . (int)$p['job']) . '/' . (int)$url->getSegment(2) ?>" 
                            class="ui skip mini green icon button" 
                            title="<?= $this->e($intl('maintenancereports.operations.addperson')) ?>">
                            <i class="child icon"></i>
                        </a>
                        <a href="<?= $url('emplPersons?empl_positions.empl_position=' . (int)$p['empl_position']) ?>" 
                            class="ui skip mini blue icon button" 
                            title="<?= $this->e($intl('maintenancereports.operations.persons')) ?>">
                            <i class="list alternate outline icon"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
