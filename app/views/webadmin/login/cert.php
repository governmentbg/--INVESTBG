<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var ?string $error
 * @var ?string $certificate
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::login/master'); ?>

<h4 class="ui large red header"><i class="warning icon"></i> <?= $this->e($intl($config('APPNAME'))) ?></h4>
<div class="ui red segment">
    <form class="ui form" method="post">
        <div class="ui negative message">
            <?php if ($error) : ?>
                <div class="header"><?= $this->e($intl($error, [ $certificate ])) ?></div>
            <?php endif ?>
            <p><?= $this->e($intl('common.login.certificate')) ?></p>
        </div>
    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
body > .grid .message { margin-top:0 !important; }
</style>
