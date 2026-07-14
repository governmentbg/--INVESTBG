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
?>
<?php $this->layout('crud::index', $this->data() ?? []); ?>

<div class="plupload-temp">
<?php
echo $this->insert('webadmin::field/file', [
    'field' => new webadmin\components\html\Field(
        "file",
        ["name" => "temp"],
        ["picker" => false, "browse" => ["clss" => "ui green labeled icon button"]]
    )
]);
?>
</div>

<script nonce="<?= $this->e($cspNonce) ?>">
$('.row-operations').append($('.plupload-temp > .plupload-container'));
$('[name=temp]').on('changed.plupload', function (e, data) {
    window.location.href = "<?= $url('uploads/update/') ?>" + data.id;
});
if (window.parent && window.parent !== window.self) {
    $('.row-operations .plupload-container').hide();
}
if (window.parent && window.parent !== window.self && window.location.href.indexOf('add') !== -1) {
    $('.row-operations').children('.plupload-container').show();
}
</script>
