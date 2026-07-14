<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var array $pkey
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php
echo $this->insert('crud::update', $this->data() ?? []);
?>
<?php if ($pkey['grp'] == $config('GROUP_ADMINS')) : ?>
<style nonce="<?= $this->e($cspNonce) ?>">
.superadmin-column { display:block; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$('[name="name"]')
    .closest('.row')
        .nextAll().hide().end()
    .after(
        '<div class="ui one column row">'+
            '<div class="column">'+
                '<div class="ui info message column superadmin-column"><?= $intl("superadmin.rights") ?></div>'+
            '</div>'+
        '</div>'
    );
</script>
<?php endif ?>
