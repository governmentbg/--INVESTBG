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
 * @var bool $update
 * @var bool $delete
 * @var bool $history
 * @var \webadmin\components\html\Form $form
 */
?>
<?php
$this->layout(
    'webadmin::main',
    [
        'breadcrumb' => '<i class="' . $this->e($icon ?? 'eye') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.read'])) .
            '<i class="right angle icon divider"></i> ' .
            $this->e($name)
    ]
)
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
<h3 class="ui left floated teal header">
    <i class="<?= $this->e($icon ?? 'eye') ?> icon"></i>
    <span class="content"><?= $this->e($intl([$title, 'crud.titles.read'])) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form read-form main-form">
        <?= $this->insert('webadmin::form', [ 'form' => $form ]) ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned blue secondary segment">
            <a href="<?= $this->e($back) ?>" class="ui blue icon labeled submit button">
                <i class="left arrow icon"></i> <?= $this->e($intl('common.back')) ?>
            </a>
            <?php if ($update) : ?>
            <a href="<?= $this->e($url($url->getSegment(0) . '/update/' . $url->getSegment(2))) ?>"
                class="ui orange icon labeled button">
                <i class="pencil icon"></i> <?= $this->e($intl('crud.actions.update')) ?>
            </a>
            <?php endif ?>
            <?php if ($delete) : ?>
            <a href="<?= $this->e($url($url->getSegment(0) . '/delete/' . $url->getSegment(2))) ?>"
                class="ui red icon labeled button">
                <i class="trash icon"></i> <?= $this->e($intl('crud.actions.delete')) ?>
            </a>
            <?php endif ?>
            <?php if ($history) : ?>
            <a href="<?= $this->e($url($url->getSegment(0) . '/history/' . $url->getSegment(2))) ?>"
                class="ui grey icon labeled button">
                <i class="clock icon"></i> <?= $this->e($intl('crud.actions.history')) ?>
            </a>
            <?php endif ?>
        </div>
    </form>
    <?= $this->section('content') ?>
</div>
<script nonce="<?= $this->e($cspNonce) ?>">
$('.main-form').on('submit', function (e) { e.preventDefault(); })
</script>
