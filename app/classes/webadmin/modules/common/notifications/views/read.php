<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \schema\NotificationsEntity $entity
 * @var \webadmin\modules\common\notifications\NotificationsModule $module
 * @var string $cspNonce
 * @var string $breadcrumb
 * @var string $back
 * @var string $title
 * @var \webadmin\components\html\Form $form
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var callable (string): string $upload
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php
$this->layout(
    'webadmin::main',
    [
        'breadcrumb' => '<i class="' . $this->e($icon ?? 'eye') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.read'])) .
            '<i class="right angle icon divider"></i> ' . $entity->title
    ]
)
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
<h3 class="ui blue left floated blue header">
    <i class="<?= $this->e($icon ?? 'eye') ?> icon"></i>
    <span class="content"><?= $this->e($intl([$title, 'crud.titles.read'])) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui blue segment">
    <div class="ui comments notification-comments">
    <?php foreach ($module->getService()->toArray($entity)['parents'] ?? [] as $parent) : ?>
        <div class="comment">
            <a class="avatar">
                <?php if ($parent->users) : ?>
                    <?php if ($parent->users->avatar_data) : ?>
                        <img class="ui avatar image" src="<?=$this->e($parent->users->avatar_data)?>">
                    <?php else : ?>
                        <i class="big user icon"></i>
                    <?php endif ?>
                <?php else : ?>
                    <i class="big server icon"></i>
                <?php endif ?>
            </a>
            <div class="content">
                <a class="author"><?= $this->e($parent->users ? $parent->users->name : $config('APPNAME')) ?></a>
                <div class="metadata">
                    <?= strtotime($parent->sent) ? date('d.m.Y H:i:s', strtotime($parent->sent)) : '' ?>
                </div>
                <div class="text">
                    <p><strong><?= $this->e($parent->title) ?></strong></p>
                    <?= nl2br($this->e($parent->body)) ?>
                    <?php if (strlen($parent->link)) : ?>
                        <?php
                        $link = strpos($parent->link, '//') !== false ? $parent->link : $url($parent->link, [], true);
                        ?>
                        <br /><a href="?follow=1"><?= $this->e($link) ?></a>
                    <?php endif ?>
                    <?php
                    foreach ($parent->files() as $v) {
                        try {
                            echo '<br />';
                            echo '<br />';
                            echo '<a href="';
                            echo $this->e($upload($v->id()));
                            echo '">';
                            echo '<i class="file icon"></i> ' . $this->e($v->name());
                            echo '</a>';
                        } catch (\Exception) {
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="ui divider"></div>
        </div>
    <?php endforeach ?>
    </div>
</div>

<?php if ($entity->reply) : ?>
<div class="ui orange segment">
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
        <div class="ui center aligned orange secondary segment">
            <button class="ui orange icon labeled submit button">
                <i class="share icon"></i> <?= $this->e($intl('notifications.reply')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
        </div>
    </form>
</div>
<?php else : ?>
<div class="ui blue segment">
    <div class="ui center aligned blue secondary segment">
        <a href="<?= $this->e($back) ?>" class="ui blue icon labeled submit button">
            <i class="left arrow icon"></i> <?= $this->e($intl('common.back')) ?>
        </a>
    </div>
</div>
<?php endif ?>
<script nonce="<?= $this->e($cspNonce) ?>">
    $('[name=body]').val('');
</script>
<style nonce="<?= $this->e($cspNonce) ?>">
.notification-comments { max-width: none; }
</style>
