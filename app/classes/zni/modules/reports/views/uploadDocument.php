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
 *  * @var callable (string): mixed $config
 * @var \webadmin\components\html\Form $form
 */
?>
<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>

<div class="ui clearing basic segment title-segment">
    <h3 class="ui left floated header createSub-header">
        <i class="lock icon"></i>
        <span class="content"><?= $this->e($intl('reports.upload_mir_document')) ?></span>
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
        <?= $this->insert('webadmin::form', [ 'form' => $form ]) ?>
      
        <div class="ui section divider"></div>
        <div class="ui center aligned orange secondary segment">
             <button class="ui orange icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
        </div>
    </form>
</div>
