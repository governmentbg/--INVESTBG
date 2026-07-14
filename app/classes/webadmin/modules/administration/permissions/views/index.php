<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var \vakata\http\Uri $url
 * @var \webadmin\components\html\Form $form
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment">
    <h3 class="ui left floated header permissions-header">
        <i class="lock icon"></i>
        <span class="content"><?= $this->e($intl('permissions.title')) ?></span>
    </h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form validate-form" id="permissions_form" method="post">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.pleasewait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <br />
        <?= $this->insert('webadmin::form', [ 'form' => $form ]) ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned orange secondary segment">
            <button class="ui orange icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
        </div>
    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.permissions-header { padding:0.5rem !important; }
[name^="modules"] ~ label { font-weight:bold; }
.ui.form .inline.fields { margin-bottom:0; }
#permissions_form > .grid .grid { width: 100%; }
#permissions_form > .grid > .row:nth-child(1) { border-bottom:1px solid silver; font-weight:bold; }
#permissions_form > .grid > .row:nth-child(2n) { background:#efefef; }
</style>
