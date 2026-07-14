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
 * @var \webadmin\components\html\Table $table
 * @var string $breadcrumb
 * @var string $icon
 */
?>

<?php $this->layout('webadmin::main', [
        'breadcrumb' => '<i class="' . $this->e($icon ?? 'plus') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.create']))
    ]);
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
    <h3 class="ui left floated header impersonate-header violet">
        <i class="<?= $icon ?> icon"></i>
        <span class="content"><?= $this->e($intl('reports.workplaces.title')) ?></span>
    </h3>

    <a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
</div>
<?php $this->stop() ?>

<?= $this->insert('webadmin::table', ['table' => $table]) ?>
