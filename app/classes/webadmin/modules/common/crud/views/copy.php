<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \webadmin\components\html\Form $form
 * @var \webadmin\components\html\Field $mainField
 * @var string $cspNonce
 * @var string $breadcrumb
 * @var string $back
 * @var string $title
 * @var string $name
 * @var array $pkey
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php
$this->layout(
    'webadmin::main',
    [
        'breadcrumb' => '<i class="' . $this->e($icon ?? 'copy') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.copy'])) .
            '<i class="right angle icon divider"></i> ' .
            $this->e($name)
    ]
);
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
<h3 class="ui left floated purple header">
    <i class="<?= $this->e($icon ?? 'copy') ?> icon"></i>
    <span class="content"><?= $this->e($intl([$title, 'crud.titles.copy'])) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form validate-form main-form" method="post">
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
        <div class="ui center aligned teal secondary segment">
            <button class="ui teal icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
        </div>
    </form>
    <?= $this->section('content') ?>
</div>
