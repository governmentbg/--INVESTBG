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
    $field->setAttr('id', 'files_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
$files = [];
$val = [];
if ($field->getAttr('value')) {
    $vv = $field->getValue('');
    if (is_string($vv) && json_validate($vv)) {
        $vv = json_decode($vv, true);
    }
    if (is_string($vv) && !json_validate($vv)) {
        $vv = explode(',', $vv);
    }
    if (!is_array($vv)) {
        $vv = [];
    }
    foreach ($vv as $v) {
        try {
            $temp = $v instanceof \vakata\files\File ? $v : $app->get('file')->get($v);
            $files[] = [
                'id'       => $temp->id(),
                'hash'     => $temp->hash(),
                'thumb'    => $app->get('file')->toLink($temp, [ 'w' => 128, 'h' => 128 ]),
                'url'      => $app->get('file')->toLink($temp),
                'html'     => $temp->name(),
                'settings' => $temp->settings()
            ];
            $val[] = $temp->id();
        } catch (\Exception) {
        }
    }
}
$field->setValue(implode(',', $val));
$temp = $field->getOptions();
unset($temp['form']);
unset($temp['label']);
$config = array_merge([
    'images'    => true,
    'picker'    => $url('uploads?name[iends][]=.jpg&name[iends][]=.jpeg&name[iends][]=.png&thumbs='),
    'multiple'  => true,
    'url'       => $url($config('UPLOAD_URL')),
    'settings'  => $field->hasOption('form') ? $field->getAttr('id') . '_form' : false,
    'edit'      => $field->hasOption('editor') ? $field->getAttr('id') . '_edit' : false,
    'chunksize' => '250kb',
    'value'     => count($files) ? $files : null,
    'disabled'  => $disabled,
    'browse'    => [ 'html' => $this->e($intl('fields.files.upload')) ],
    'pick'    => [ 'html' => $this->e($intl('fields.files.pick')) ]
], $temp);
?>
<input
    type="hidden"
    id="<?= $this->e($field->getAttr('id')) ?>"
    data-plupload='<?= json_encode($config); ?>'
    name="<?= $this->e($field->getAttr('name')); ?>"
    value="<?= $field->getValue('') ?>"
    <?= $disabled ? ' disabled="disabled" ' : '' ?>
    />

<?php if ($field->hasOption('editor')) : ?>
<div class="ui modal" id="<?= $this->e($field->getAttr('id') . '_edit') ?>">
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
        <div>
            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs="
                id="<?= $this->e($field->getAttr('id') . '_edit_image') ?>" />
        </div>
        <div class="ui section divider"></div>
        <div class="ui center aligned green secondary segment">
            <button class="ui green icon labeled submit button save-button">
                <i class="save icon"></i> <?= $this->e($intl('common.form.save')) ?>
            </button>
            <a class="ui basic button close-button" href="#"><?= $this->e($intl('common.form.cancel')) ?></a>
        </div>
    </div>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
#<?= $this->e($field->getAttr('id') . '_edit') ?> .darkroom-toolbar { position:static; margin-bottom:10px; }
#<?= $this->e($field->getAttr('id') . '_edit') ?> .darkroom-toolbar::before { display:none; }
#<?= $this->e($field->getAttr('id') . '_edit') ?> .canvas-container { margin:0 auto; }
#<?= $this->e($field->getAttr('id') . '_edit') ?> .canvas-container { margin:0 auto; }
#<?= $this->e($field->getAttr('id') . '_edit') ?> .darkroom-image-container {
    border-radius:3px; background:#333; padding:10px;
}
#<?= $this->e($field->getAttr('id') . '_edit') ?> img { max-width:100%; display:none; }
</style>
<?php endif ?>

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
