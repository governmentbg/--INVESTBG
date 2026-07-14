<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 * @var ?string $error
 * @var ?string $certerror
 * @var bool $remember
 * @var array<string,bool> $providers
 * @var string $name
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::login/master'); ?>

<h4 class="ui large teal header"><i class="lock icon"></i> <?= $this->e($intl($config('APPNAME'))) ?></h4>
<div class="ui teal segment">
    <form class="ui form" method="post">
        <div class="ui negative message <?= $error ? 'show' : 'hide' ?>">
            <div class="header"><?= $this->e($intl($error)) ?></div>
        </div>
        <div class="ui styled fluid accordion">
            <?php if (isset($providers['certificate'])) : ?>
                <div class="title"><i class="certificate icon"></i> <?= $this->e($intl('tfa.certificates')) ?></div>
                <div class="content">
                    <?php if ($certerror) : ?>
                        <div class="ui error message show">
                            <?= $this->e($intl('common.login.nocertificate')) ?></div>
                    <?php else : ?>
                        <p><?= $this->e($intl('tfa.certificates_description')) ?></p>
                        <a href="<?= $url->linkTo('cert') ?>" class="ui icon labeled green button">
                            <i class="ui certificate icon"></i> <?= $this->e($intl('tfa.certificate')) ?></a>
                    <?php endif ?>
                </div>
            <?php endif ?>
            <?php if (isset($providers['totp']) || isset($providers['recovery'])) : ?>
                <?php if (isset($providers['totp'])) : ?>
                    <div class="title"><i class="mobile icon"></i> <?= $this->e($intl('tfa.apps')) ?></div>
                <?php else : ?>
                    <div class="title"><i class="lock icon"></i> <?= $this->e($intl('tfa.recovery')) ?></div>
                <?php endif ?>
                <div class="content">
                    <div class="field">
                        <div class="ui left icon input">
                            <i class="tablet icon"></i>
                            <input type="text" name="totp" autofocus
                                placeholder="<?= $this->e($intl('common.login.entercode')) ?>" />
                        </div>
                    </div>
                    <?php if ($remember) : ?>
                    <div class="field lefted">
                        <div class="ui checkbox">
                            <input type="checkbox" name="remember" value="1" />
                            <label><?= $this->e($intl('common.login.rememberdevice')) ?></label>
                        </div>
                    </div>
                    <div class="field">
                        <div class="ui left icon input">
                            <i class="computer icon"></i>
                            <input type="text" name="name"
                                placeholder="<?= $this->e($intl('common.login.devicename')) ?>"
                                value="<?= $this->e($name) ?>" disabled="disabled" />
                        </div>
                    </div>
                    <?php endif ?>
                    <div class="ui divider"></div>
                    <button type="submit" class="ui labeled icon teal submit button login-submit-button">
                        <i class="sign in icon"></i>
                        <?= $this->e($intl('common.login.login')) ?>
                    </button>
                </div>
            <?php endif ?>
        </div>
    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
body > .grid .segment { padding-bottom:1.4rem !important; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$('[name=remember]').on('change', function () {
    $(this).closest('.field').next().find('input').prop('disabled', !this.checked);
});
$('.ui.accordion').accordion({
    onOpening : function (item) {
        window.sessionStorage.setItem("tfa", (this.index() - 1) / 2);
    }
});
var acc = window.sessionStorage.getItem("tfa");
$('.ui.accordion').accordion("open", acc !== null ? parseInt(acc, 10) : 0);
setTimeout(function () {
    window.location.href = window.location.href.replace('tfa', '');
}, parseInt(JSON.parse('<?= (int)$config('SESSION_TIMEOUT') ?>'), 10) * 1000);
</script>
