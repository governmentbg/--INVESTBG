<?php

/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 * @var string $breadcrumb
 * @var string $back
 * @var string $title
 * @var string $name
 * @var array $pkey
 * @var \webadmin\components\html\Form $form
 * @var \schema\MaintenanceReportsEntity $entity
 * @var bool $isInv
 * @var bool $isMir
 * @var bool $isInvAdmin
 * @var string $moduleSlug
 * @var int $correctionAttempt
 */
?>
<?php
$this->layout(
    'webadmin::main',
    [
        'breadcrumb' => '<i class="' . $this->e($icon ?? 'pencil') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.update'])) .
            '<i class="right angle icon divider"></i> ' .
            $this->e($name)
    ]
)
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
<h3 class="ui left floated orange header">
    <i class="<?= $this->e($icon ?? 'pencil') ?> icon"></i>
    <span class="content"><?= $this->e($intl([$title, 'crud.titles.update'])) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form validate-form main-form" method="post"
        data-redraw="<?= $this->e($url($moduleSlug . '/redraw/' . implode('|', $pkey))) ?>">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.pleasewait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <?= $this->insert('webadmin::form', [ 'form' => $form ]) ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned orange secondary segment action-buttons">
            <?php if ($isInv) : ?>
                <?php if ($entity->status == zni\enums\MaintenanceReportStatus::Draft->value) : ?>
                <button class="ui orange icon labeled submit button" name="save">
                    <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
                </button>
                    <?php if ($isInvAdmin) : ?>
                <button class="ui green icon labeled submit button" name="submit_report">
                    <i class="check icon"></i> <?= $this->e($intl('maintenancereports.buttons.submit_report')) ?>
                </button>
                    <?php endif; ?>
                    <?= $this->insert('maintenanceReports::check_button') ?>
                <?php endif; ?>

                <?php if (
                    $isInvAdmin
                    && $entity->status == zni\enums\MaintenanceReportStatus::ReturnedForCorrection->value
                    && $entity->correction_attempt < $correctionAttempt
                    && ($entity->correction_date ?? "") >= date('Y-m-d')
) : ?>
                <button class="ui green icon labeled submit button" name="submit_report">
                    <i class="check icon"></i> <?= $this->e($intl('maintenancereports.buttons.submit_report')) ?>
                </button>
                <?php endif; ?>

            <?php endif; ?>

            <?php if ($isMir) : ?>
                <?php if ($entity->status == zni\enums\MaintenanceReportStatus::Submitted->value) : ?>
                <button class="ui red icon labeled submit button" name="under_review">
                    <i class="tasks icon"></i> <?= $this->e($intl('maintenancereports.buttons.submit_under_review')) ?>
                </button>
                <?php endif; ?>

                <?php if ($entity->status == zni\enums\MaintenanceReportStatus::UnderReview->value) : ?>
                <button class="ui green icon labeled submit button" name="approve">
                    <i class="check icon"></i> <?= $this->e($intl('maintenancereports.buttons.approve')) ?>
                </button>
                <button class="ui red icon labeled submit button" name="reject">
                    <i class="times icon"></i> <?= $this->e($intl('maintenancereports.buttons.reject')) ?>
                </button>
                <button class="ui yellow icon labeled submit button" name="for_correction">
                    <i class="undo icon"></i> <?= $this->e($intl('maintenancereports.buttons.for_correction')) ?>
                </button>
                    <?= $this->insert('maintenanceReports::check_button') ?>
                <?php endif; ?>

                <?php if (
                $entity->status == zni\enums\MaintenanceReportStatus::ReturnedForCorrection->value
                    && (($entity->correction_date ?? "") < date('Y-m-d')
                    || $entity->correction_attempt >= $correctionAttempt)
) : ?>
                <button class="ui red icon labeled submit button" name="reject">
                    <i class="times icon"></i> <?= $this->e($intl('maintenancereports.buttons.reject')) ?>
                </button>
                <?php endif; ?>

            <?php endif; ?>

            <div id="loading" class="hide">
                <i class="large blue sync loading icon"></i> 
                <?= $intl('maintenancereports.columns.last_sync') ?> 
                <?= $this->e(date('d.m.Y', strtotime($entity->last_sync ?? "") ?: 0)) ?>
            </div>
            
            
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
        </div>
    </form>
    <?= $this->section('content') ?>
</div>
<script nonce="<?= $this->e($cspNonce) ?>">

$('.check-employees').click(function() {
    $('.action-buttons button').hide();
    $('#loading').removeClass('hide');
    $.ajax({
        type : "GET",
        url : '<?= $url('maintenanceReports/check/' . $entity->mr) ?>',
    })
    .done(function (data) {
        if (!data.error) {
           window.location.reload(); 
        } else {
            alert(data.message)
        }
        $('.action-buttons button').show();
        $('#loading').addClass('hide');
    });
})

if (window.parent && window.parent !== window.self) {
    var selectedPromise = {
        cbks : [],
        then : function (cb) { this.cbks.push(cb); },
        when : function (value) {}
    };
    $('body').addClass('no-menu');
    $('.main-form').append('<input type="hidden" value="1" name="redirect_to_id" />');
}
</script>
