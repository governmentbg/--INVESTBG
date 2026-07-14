<?php

/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var string $back
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 * @var \webadmin\components\html\Form $form
 * @var \schema\ReportsEntity $report
 */
?>

<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
    <h3 class="ui left floated header impersonate-header green">
        <i class="check icon"></i>
        <span class="content"><?= $this->e($intl('empl_report.submitReportMir.title')) ?></span>
    </h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form validate-form" method="post">

        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.pleasewait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <p class="empl_report-submitreportmir-descr">
            <strong>
                <?= $this->insert('webadmin::form', ['form' => $form]) ?>
            </strong>
        </p>
        <div class="ui center aligned secondary segment">

            <button
                type="submit"
                name="action"
                value=3
                class="ui green icon labeled submit button ">
                <i class="check icon"></i>
                <?= $this->e($intl('empl_report.approved.button')) ?>
            </button>

            <button
                type="submit"
                name="action"
                value=4
                class="ui red icon labeled submit button ">
                <i class="times icon"></i>
                <?= $this->e($intl('empl_report.rejected.button')) ?>
            </button>

            <button
                type="submit"
                name="action"
                value=5
                class="ui orange icon labeled submit button ">
                <i class="undo icon"></i>
                <?= $this->e($intl('empl_report.correction.button')) ?>
            </button>

            <a class="ui basic button" href="<?= $this->e($back) ?>">
                <?= $this->e($intl('common.cancel')) ?>
            </a>

        </div>

    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
    .impersonate-header {
        padding: 0.5rem !important;
    }

    .impersonate-descr {
        text-align: center;
    }
</style>