<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \webadmin\components\html\Form $form
 * @var string $breadcrumb
 * @var string $back
 * @var string $title
 * @var array $pkey
 * @var string $cspNonce
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 * @var bool $canCreate
 * @var string $moduleName
 */
?>
<?php
$this->layout(
    'webadmin::main',
    [
        'breadcrumb' => '<i class="' . $this->e($icon ?? 'plus') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.create']))
    ]
)
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
<h3 class="ui left floated green header">
    <i class="<?= $this->e($icon ?? 'plus') ?> icon"></i>
    <span class="content"><?= $this->e($intl([$title, 'crud.titles.create'])) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form validate-form main-form" method="post"
        data-redraw="<?= $this->e($url($moduleName . '/redraw')) ?>">
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
        <?php //if($canCreate): ?>
        <div class="ui center aligned green secondary segment">
            <button class="ui green icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
            <button class="ui green icon labeled submit button" name="submit_report">
                <i class="check icon"></i> <?= $this->e($intl('maintenancereports.buttons.submit_report')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
        </div>
        <?php //endif; ?>
    </form>
    <?= $this->section('content') ?>
</div>
<script nonce="<?= $this->e($cspNonce) ?>">
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
