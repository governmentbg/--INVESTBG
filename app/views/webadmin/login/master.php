<?php

/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
$this->layout('webadmin::master'); ?>

<div class="ui grid fullheight">
    <div class="sixteen wide mobile sixteen wide tablet eight wide computer column">
        <div class="ui column grid">
            <div class="two wide column computer only"></div>
            <div class="sixteen wide mobile sixteen wide tablet twelve wide computer column logo">
                <a href="<?= $this->e($config('PUBLIC_URL')) ?>">
                    <img src="<?= $asset('assets/zni-logo.svg') ?>" class="ui middle aligned image"/>
                    <h2 class="ui large middle aligned header logo-title">
                        <?= $this->e($intl('login.logo_title')) ?>
                    </h2>
                </a>
                <p></p>
                <div class="ui styled fluid accordion">
                    <div class="title">
                        <i class="dropdown icon"></i>
                        <?= $this->e($intl('login.accordion.one')) ?>
                    </div>
                    <div class="content">
                        <p><?= $this->e($intl('login.description.about_system')) ?></p>
                        <p><?= $this->e($intl('login.description.about_system_second')) ?></p>
                    </div>

                    <div class="title">
                        <i class="dropdown icon"></i>
                        <?= $this->e($intl('login.accordion.two')) ?>
                    </div>
                    <div class="content">
                        <p>
                            <?php if ($config('INVESTITOR_GUIDE_LINK')) : ?>
                            <a href="<?= $this->e($config('INVESTITOR_GUIDE_LINK')) ?>" target="_blank">
                                <i class="file pdf icon"></i> <?= $intl('login.investor_guide') ?>
                            </a><br>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <?php if ($config('VIDEO_GUIDE_' . $i)) : ?> 
                                <a href="<?= $this->e($config('VIDEO_GUIDE_' . $i)) ?>" target="_blank">
                                    <i class="video icon"></i> <?= $intl('login.video_' . $i) ?>
                                </a><br>
                                <?php endif; ?>   
                            <?php endfor; ?>
                        </p>
                    </div>

                    <div class="title">
                        <i class="dropdown icon"></i>
                        <?= $this->e($intl('login.accordion.three')) ?>
                    </div>
                    <div class="content">
                        <?= $intl('login.accordion.three_description') ?>
                    </div>
                </div>
                <h4 class="ui large header"><?= $this->e($intl('login_title')) ?></h4>
                <?= $this->section('content') ?>
            </div>
        </div>
    </div>
    <div class="eight wide column login-bgr computer only"></div>
</div>

<style nonce="<?= $this->e($cspNonce) ?>">
    body { background: #fff; margin: 0; }
    .ui.grid.fullheight { height: 100vh; }
    .ui.grid { margin: 0; }
    .login-bgr {
        background: url('./assets/login_bg.png') no-repeat top left;
        background-size: cover;
    }
    .logo { margin-top: 60px; }
    .logo-title{ display: inline-block;}
    .ui.segment { border: none !important; box-shadow: none !important; }
    .ui.large.header { text-transform: uppercase; }
    .ui.button.login-submit-button {
        color: #0E4E2E;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.01563rem;
        text-transform: uppercase;
        background-color: #26CF7B;
        border-color: transparent;
    }
    .ui.button.login-submit-button:hover { color: #0B4851; background-color: #21CAE1; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
    $('.ui.accordion').accordion();
</script>