<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var ?string $error
 * @var bool $sent
 * @var bool $change
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::login/master'); ?>

<h4 class="ui large teal header"><i class="lock icon"></i> <?= $this->e($intl($config('APPNAME'))) ?></h4>
<div class="ui teal segment">
    <form class="ui form" method="post">
        <?php if ($error) : ?>
        <div class="ui negative message"><div class="header"><?= $this->e($intl($error)) ?></div></div>
        <?php endif; ?>
        <?php if ($sent) : ?>
        <div class="ui positive message">
            <div class="header"><?= $this->e($intl('common.login.register_sent')) ?></div>
        </div>
        <?php else : ?>
            <?php if ($change) : ?>
                <div class="field">
                    <label><?= $this->e($intl('common.login.newpassword')) ?></label>
                    <div class="ui right icon input">
                        <input type="password" autocomplete="new-password" name="password1" />
                        <i class="eye slash link icon password-reveal"></i>
                    </div>
                </div>
                <div class="field">
                    <label><?= $this->e($intl('common.login.repeatpassword')) ?></label>
                    <div class="ui right icon input">
                        <input type="password" autocomplete="new-password" name="password2" />
                        <i class="eye slash link icon password-reveal"></i>
                    </div>
                </div>
                <div class="ui divider"></div>
                <button type="submit" class="ui labeled icon teal submit button login-submit-button">
                    <i class="sign in icon"></i>
                    <?= $this->e($intl('common.login.login')) ?>
                </button>
            <?php else : ?>
                <div class="ui info message">
                    <div class="header"><?= $this->e($intl('common.login.register_text')) ?></div>
                </div>
                <div class="field">
                    <div class="ui left icon input">
                        <i class="mail icon"></i>
                        <input type="text" name="mail" autofocus autocomplete="email"
                            placeholder="<?= $this->e($intl('common.login.register_mail')) ?>" />
                    </div>
                </div>
                <div class="field">
                    <div class="ui left icon input">
                        <i class="user icon"></i>
                        <input type="text" name="name" autocomplete="name"
                            placeholder="<?= $this->e($intl('common.login.register_name')) ?>" />
                    </div>
                </div>
                <div class="ui divider"></div>
                <button type="submit" class="ui labeled icon teal submit button login-submit-button">
                    <i class="sign in icon"></i>
                    <?= $this->e($intl('common.login.register_send')) ?>
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
body > .grid .segment { padding-bottom:1.4rem !important; }
::-ms-reveal { display:none }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
if ($('.password-reveal').length) {
    $('.password-reveal')
        .css({ 'right':'0', 'left' : 'auto' })
        .on('click', function (e) {
            e.preventDefault();
            $(this).toggleClass('slash')
                .prev().attr('type', $(this).prev().attr("type") === "password" ? "text" : "password");
        })
        .prev().css('paddingRight', '32px')
}
</script>
