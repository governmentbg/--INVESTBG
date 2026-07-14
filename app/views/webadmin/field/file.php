<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\di\DIContainer $app
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 * @var string $cspNonce
 * @var \vakata\http\Uri $url
 */
?>
<?php if (strlen($field->getOption('label', ''))) : ?>
    <label>
        <?php if ($field->getOption('tooltip')) : ?>
            <span 
                data-tooltip="<?= $this->e($intl($field->getOption('tooltip'))) ?>"
                data-inverted="">
                <i class="question circle icon"></i>
            </span>
        <?php endif ?>
        <?= $this->e($intl($field->getOption('label'))) ?>
    </label>
<?php endif ?>
<?php
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'file_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
$file = null;
$v = '';
if ($field->hasAttr('value')) {
    try {
        $v = $field->getValue();
        $temp = $v instanceof \vakata\files\File ? $v : $app->get('file')->get($v);
        $file = [
            'id'       => $temp->id(),
            'hash'     => $temp->hash(),
            'thumb'    => $app->get('file')->toLink($temp, [ 'w' => 128, 'h' => 128 ]),
            'url'      => $app->get('file')->toLink($temp),
            'html'     => $temp->name(),
            'settings' => $temp->settings()
        ];
        $v = $temp->id();
    } catch (\Exception) {
        $v = '';
    }
}
$temp = $field->getOptions();
unset($temp['form']);
unset($temp['label']);
$config = array_merge([
    'images'    => false,
    'picker'    => $url('uploads'),
    'multiple'  => false,
    'value'     => $file,
    'url'       => $url($config('UPLOAD_URL')),
    'settings'  => $field->hasOption('form') ? $field->getAttr('id') . '_form' : false,
    'chunksize' => '250kb',
    'disabled'  => $disabled,
    'browse'    => [ 'html' => $this->e($intl('fields.files.upload')) ],
    'pick'    => [ 'html' => $this->e($intl('fields.files.pick')) ]
], $temp);
?>
<input
    type="hidden"
    id="<?= $this->e($field->getAttr('id')) ?>"
    data-plupload='<?=json_encode(
        $config,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT
    )?>'
    name="<?= $this->e($field->getAttr('name')); ?>"
    value="<?= $this->e($v) ?>"
    <?= $disabled ? ' disabled="disabled" ' : '' ?>
    />
<?php if ($field->hasOption('form')) : ?>
<div class="ui modal" id="<?= $this->e($field->getAttr('id') . '_form') ?>">
    <i class="close icon"></i>
    <div class="ui form padded-form">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.form.wait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <h3 class="dividing header"><?= $this->e($intl('common.fields.file.settings')) ?></h3>
        <?= $this->insert('webadmin::form', [ 'form' => $field->getOption('form') ]) ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned green secondary segment">
            <button class="ui green icon labeled submit button save-button">
                <i class="save icon"></i> <?= $this->e($intl('common.form.save')) ?>
            </button>
            <a class="ui basic button close-button" href="#"><?= $this->e($intl('common.form.cancel')) ?></a>
        </div>
    </div>
</div>
<?php endif ?>
<script nonce="<?= $this->e($cspNonce) ?>">
$('#<?= $this->e($field->getAttr("id")) ?>').plupload();
</script>
