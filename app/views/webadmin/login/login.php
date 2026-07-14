<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var array<string,string> $links
 * @var ?string $error
 * @var ?string $token
 * @var array<string,\vakata\authentication\AuthenticationInterface> $auth
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
// phpcs:disable Generic.Files.LineLength
?>
<?php $this->layout('webadmin::login/master'); ?>

<!--<h4 class="ui large green header"><i class="lock icon"></i> --><?php //= $this->e($intl($config('APPNAME'))) ?><!--</h4>-->

<?php if ($config('MIDDLEWARE_MAINTENANCE')) : ?>
<div class="ui warning icon message">
    <i class="configure icon"></i>
    <div class="content">
        <div class="header"><?= $this->e($intl('maintenance.mode')) ?></div>
        <p><?= $this->e($intl('maintenance.mode.descr')) ?></p>
    </div>
</div>
<?php endif ?>

<?php if (count($auth) > 1) : ?>
<div class="ui compact large icon top attached tabular menu">
    <?php
    foreach ($auth as $method) {
        if ($method instanceof \vakata\authentication\password\Password) {
            echo '<a class="item"><i class="large user icon"></i></a>';
        } elseif ($method instanceof \vakata\authentication\mail\SMTP) {
            echo '<a class="item"><i class="large mail icon"></i></a>';
        } elseif ($method instanceof \vakata\authentication\ldap\LDAP) {
            echo '<a class="item"><i class="large address card icon"></i></a>';
        } elseif ($method instanceof \vakata\authentication\oauth\AppleID) {
            echo '<a class="item"><i class="large apple icon"></i></a>';
        } elseif ($method instanceof \vakata\authentication\oauth\Facebook) {
            echo '<a class="item"><i class="large facebook icon"></i></a>';
        } elseif ($method instanceof \vakata\authentication\oauth\Google) {
            echo '<a class="item"><i class="large google icon"></i></a>';
        } elseif ($method instanceof \vakata\authentication\oauth\Microsoft) {
            echo '<a class="item"><i class="large windows icon"></i></a>';
        } elseif ($method instanceof \vakata\authentication\oauth\AzureAD) {
            echo '<a class="item"><i class="large windows icon"></i></a>';
        } elseif ($method instanceof \vakata\authentication\oauth\Github) {
            echo '<a class="item"><i class="large github icon"></i></a>';
        } elseif ($method instanceof \vakata\authentication\oauth\Linkedin) {
            echo '<a class="item"><i class="large linkedin icon"></i></a>';
        } elseif ($method instanceof \vakata\authentication\oauth\OAuth) {
            echo '<a class="item"><i class="large user check icon"></i></a>';
        } elseif ($method instanceof \vakata\authentication\saml\EAuth) {
            echo '<a class="item"><i class="large user check icon"></i></a>';
        } elseif (
            $method instanceof \vakata\authentication\certificate\Certificate ||
            $method instanceof \vakata\authentication\certificate\CertificateAdvanced
        ) {
            echo '<a class="item"><i class="large microchip icon"></i></a>';
        }
    }
    ?>
</div>
<?php endif ?>
<div class="ui <?= count($auth) > 1 ? 'bottom attached' : '' ?> segment">
    <?php foreach ($auth as $method) : ?>
        <?php
        if (
            ($method instanceof \vakata\authentication\password\Password) ||
            ($method instanceof \vakata\authentication\ldap\LDAP) ||
            ($method instanceof \vakata\authentication\mail\SMTP)
        ) :
            ?>
            <form class="ui form" method="post">
                <?php if ($error) : ?>
                <div class="ui negative message"><div class="header"><?= $this->e($intl($error)) ?></div></div>
                <?php endif; ?>
                <?php if (!$token) : ?>
                <div class="field">
                    <div class="ui left icon input">
                        <i class="user icon"></i>
                        <input type="text" name="username" autocomplete="username" autofocus
                            placeholder="<?= $this->e($intl('common.login.username')) ?>" />
                    </div>
                </div>
                <div class="field">
                    <div class="ui left icon input">
                        <i class="lock icon"></i>
                        <input type="password" name="password" autocomplete="current-password"
                            placeholder="<?= $this->e($intl('common.login.password')) ?>" />
                        <i class="eye slash link icon password-reveal"></i>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (isset($change) && $change && $method instanceof \vakata\authentication\password\Password) : ?>
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
                <?php endif; ?>
                <!--<div class="ui divider"></div>-->
                <button type="submit" class="ui labeled green icon submit button login-submit-button">
                    <i class="sign in icon"></i>
                    <?= $this->e($intl('common.login.login')) ?>
                </button>
                <?php if (count($links)) : ?>
                <p class="login-links">
                    <small>
                        <?php $first = true; foreach ($links as $link => $name) : ?>
                            <?php
                            if ($first) {
                                $first = false;
                            } else {
                                echo ' <span class="bullet">&bull;</span> ';
                            }
                            ?>
                            <a href="<?= $this->e($url($link)) ?>"><?= $this->e($intl($name)) ?></a>
                        <?php endforeach ?>
                    </small>
                </p>
                <?php endif ?>
            </form>
        <?php endif ?>
        <?php if ($method instanceof \vakata\authentication\oauth\OAuth) : ?>
            <form class="ui form" method="get" action="<?= $this->e($method->getCallbackURL()) ?>">
                <?php if ($error) : ?>
                <div class="ui negative message"><div class="header"><?= $this->e($intl($error)) ?></div></div>
                <?php endif; ?>
                <div class="ui info message">
                    <?=
                    $this->e(
                        $intl([
                            'common.login.oauth.' . strtolower(basename(str_replace('\\', '/', get_class($method)))),
                            'common.login.oauth'
                        ])
                    )
                    ?>
                </div>
                <!--<div class="ui divider"></div>-->
                <button type="submit" class="ui labeled orange icon submit button login-submit-button">
                    <i class="sign in icon"></i>
                    <?= $this->e($intl('common.login.kep')) ?>
                </button>
            </form>
        <?php endif ?>
        <?php if ($method instanceof \vakata\authentication\saml\EAuth) : ?>
            <form class="ui form" method="post" action="<?= $this->e($method->serviceURL()) ?>">
                <input type="hidden" name="SAMLRequest" value="<?= $method->request() ?>" />
                <?php if ($error) : ?>
                <div class="ui negative message"><div class="header"><?= $this->e($intl($error)) ?></div></div>
                <?php endif; ?>
                <div class="ui divider"></div>
                <button type="submit" class="ui labeled orange icon submit button login-submit-button">
                    <i class="sign in icon"></i>
                    <?= $this->e($intl('common.login.login')) ?>
                </button>
            </form>
        <?php endif ?>
        <?php
        if (
            $method instanceof \vakata\authentication\certificate\Certificate ||
            $method instanceof \vakata\authentication\certificate\CertificateAdvanced
        ) : ?>
            <form class="ui form" method="get"
                action="<?= str_replace('http://', 'https://', $this->e($url->linkTo('', [], true))) ?>">
                <?php if ($error) : ?>
                    <div class="ui negative message"><div class="header"><?= $this->e($intl($error)) ?></div></div>
                <?php endif; ?>
                <div class="ui info message"><?= $this->e($intl('common.login.cert')) ?></div>
                <div class="ui divider"></div>
                <button type="submit" class="ui labeled orange icon submit button login-submit-button">
                    <i class="sign in icon"></i>
                    <?= $this->e($intl('common.login.login')) ?>
                </button>
            </form>
        <?php endif ?>
    <?php endforeach ?>
</div>

<style nonce="<?= $this->e($cspNonce) ?>">
/*body > .grid .segment { padding-bottom:1.4rem !important; }*/
::-ms-reveal { display:none }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$('.item').on('click', function () {
    var i = $(this).siblings().removeClass('active').end().addClass('active').index();
    window.localStorage.setItem('method_selected', i);
    $('form').hide().eq(i).show();
}).eq(parseInt(window.localStorage.getItem('method_selected'), 10) || 0).click();
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
