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
 */
?>

<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
    <h3 class="ui left floated header impersonate-header">
        <i class="envelope icon"></i>
        <span class="content"><?= $this->e($intl('empl_report.sendReport.title')) ?></span>
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
        <p class="empl_report-descr">
            <?= $this->e($intl('empl_report.sendReport.info')) ?><br />

            <strong>
                <?= $this->insert('webadmin::form', ['form' => $form]) ?>
     
            </strong>

        </p>
        <div class="ui center aligned secondary segment">
            <button class="ui green icon labeled submit button">
                <i class="check icon"></i> <?= $this->e($intl('empl_report.sendReport.button')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
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