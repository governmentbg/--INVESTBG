<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var bool $writable
 * @var \webadmin\components\html\Form $form
 * @var string $cspNonce
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment">
    <h3 class="ui left floated header permissions-header">
        <i class="cog icon"></i>
        <span class="content"><?= $this->e($intl('config.title')) ?></span>
    </h3>
</div>
<?php $this->stop() ?>

<?php if (!$writable) : ?>
    <div class="ui warning centered message">
        <?= $this->e($intl('config.dbconfig_not_enabled')) ?>
    </div>
<?php endif ?>
<div class="ui segment config-segment">
    <form class="ui form validate-form" method="post" id="config-form">
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
        <?php if ($writable) : ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned orange secondary segment">
            <button class="ui orange icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
        </div>
        <?php endif ?>
    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
#config-form .checkbox { padding-top:0.7rem !important; font-weight:bold; }
#config-form .row:nth-child(4n-1),
#config-form .row:nth-child(4n) { background:#ebebeb; }
#config-form .row:nth-child(2n) { padding:0 0rem 1.4rem 2rem !important; }
#config-form .row:nth-child(2) { padding:0 !important; }
</style>
