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
        data-redraw="<?= $this->e($url($url->getSegment(0) . '/redraw/' . implode('|', $pkey))) ?>">
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
