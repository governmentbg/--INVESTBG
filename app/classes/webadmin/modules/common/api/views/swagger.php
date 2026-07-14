<?php
/**
 * @var \vakata\views\View $this
 * @var string $cspNonce
 * @var callable (string): string $asset
 * @var array $api
 * @var \vakata\http\Uri $url
 * @var \vakata\intl\Intl $intl
 */
?>
<?php $this->layout('webadmin::master'); ?>

<?php $this->start('head'); ?>
    <link rel="stylesheet" href="<?= $this->e($asset('assets/static/swagger-ui/swagger-ui.css')); ?>" />
    <script src="<?= $this->e($asset('assets/static/swagger-ui/swagger-ui-bundle.js')); ?>"></script>
    <script src="<?= $this->e($asset('assets/static/swagger-ui/swagger-ui-standalone-preset.js')); ?>"></script>
<?php $this->stop(); ?>

<div class="ui clearing basic segment title-segment">
    <a class="ui basic right floated button" href="<?= $this->e($url('')); ?>">
        <?= $this->e($intl('common.back')) ?>
    </a>
</div>
<div id="swagger-ui"></div>
<script nonce="<?= $this->e($cspNonce) ?>">
    window.onload = () => {
        window.ui = SwaggerUIBundle({
            dom_id: '#swagger-ui',
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            spec: <?= json_encode($api); ?>
        });
    };
</script>