<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var string $back
 * @var \vakata\http\Uri $url
 * @var \schema\UsersEntity $user
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
    <h3 class="ui left floated header impersonate-header">
        <i class="lock icon"></i>
        <span class="content"><?= $this->e($intl('users.impersonate.title')) ?></span>
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
        <p class="impersonate-descr">
            <?= $this->e($intl('users.impersonate.description')) ?><br />
            <strong><?= $this->e($user->name) ?></strong>
        </p>
        <input name="user" type="hidden" value="1" />
        <div class="ui section divider"></div>
        <div class="ui center aligned red secondary segment">
            <button class="ui red icon labeled submit button">
                <i class="user icon"></i> <?= $this->e($intl('users.impersonate.continue')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
        </div>
    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.impersonate-header { padding:0.5rem !important; }
.impersonate-descr { text-align:center; }
</style>
