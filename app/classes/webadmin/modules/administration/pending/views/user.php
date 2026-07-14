<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \webadmin\components\html\Form $form
 * @var string $cspNonce
 * @var string $back
 * @var \vakata\http\Uri $url
 * @var \schema\UserPendingEntity $user
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>

<?php $this->stop() ?>

<div class="ui clearing basic segment">
    <h3 class="ui left floated teal header user-header">
        <i class="user plus icon"></i>
        <span class="content"><?= $this->e($intl('pending.user.title')) ?></span>
    </h3>
</div>
<div class="ui teal segment">
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
        <p class="user-descr">
            <?= $this->e($intl('pending.user.description')) ?><br />
            <strong><?= $this->e($user->name) ?></strong>
            <?php if (strlen($user->mail)) : ?>
                (<?= $this->e($user->mail) ?>)
            <?php endif ?>
            <br />
            <em><?= $this->e($user->provider) ?>: <?= $this->e($user->id) ?></em>
        </p>
        <input name="user" type="hidden" value="1" />
        <div class="ui section divider"></div>
        <div class="ui center aligned teal secondary segment">
            <button class="ui teal icon labeled submit button">
                <i class="user plus icon"></i> <?= $this->e($intl('users.impersonate.continue')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
        </div>
    </form>
</div>
<br /><br />
<div class="ui horizontal divider">или</div>
<div class="ui clearing basic segment">
    <h3 class="ui left floated orange header user-header">
        <i class="pencil icon"></i>
        <span class="content"><?= $this->e($intl('pending.user.title_append')) ?></span>
    </h3>
</div>
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
        <p class="user-descr">
            <?= $this->e($intl('pending.user.description2')) ?><br />
            <br />
            <em><?= $this->e($user->provider) ?>: <?= $this->e($user->id) ?></em>
        </p>
        <?= $this->insert('webadmin::form', [ 'form' => $form ]) ?>
        <input name="user_add" type="hidden" value="1" />
        <div class="ui section divider"></div>
        <div class="ui center aligned orange secondary segment">
            <button class="ui orange icon labeled submit button">
                <i class="pencil icon"></i> <?= $this->e($intl('users.impersonate.continue')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
        </div>
    </form>
</div>

<style nonce="<?= $this->e($cspNonce) ?>">
.user-header { padding:0.5rem !important; }
.user-descr { text-align:center; }
</style>
