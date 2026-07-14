<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \webadmin\components\html\Table $table
 * @var string $cspNonce
 * @var \webadmin\modules\VisualModuleInterface $module
 * @var string $created
 * @var string $updated
 * @var array $filters
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
    <h3 class="ui left floated <?= $this->e($module->getColor()) ?> header">
    <i class="<?= $this->e($module->getIcon()) ?> icon"></i>
    <span class="content"><?= $this->e($intl($module->getName() . '.title')) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form main-form validate-form no-ls" method="get"
        data-redraw="<?= $this->e($url($module->getSlug())) ?>">
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
        <div class="ui center aligned blue secondary segment">
            <button class="ui blue icon labeled submit button">
                <i class="search icon"></i> <?= $this->e($intl('common.search_submit')) ?>
            </button>
            <a class="ui grey icon labeled submit button"
                href="<?= $this->e($url($module->getSlug())); ?>">
                <i class="<?= $module->getIcon() ?> icon"></i> <?= $this->e($intl('common.reset')) ?>
            </a>
            <?php if (count($table->getRows())) : ?>
            <a class="ui green icon labeled submit button"
                href="<?= $this->e($url($module->getSlug() . '/export', $req->getQuery())); ?>">
                <i class="download icon"></i> <?= $this->e($intl('common.export')) ?>
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?= $this->insert('webadmin::table', [ 'table' => $table ]) ?>

<div class="ui segment">&nbsp;</div>