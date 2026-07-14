<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 */
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'tree_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$id = $field->getAttr('id');
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
?>
<div class="<?= ($field->getOption('inline') ? 'inline' : 'grouped') ?> fields" id="<?= $this->e($id) ?>">
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
    <?php foreach ($field->getOption('values', []) as $k => $v) : ?>
        <?php $temp = md5($this->e($field->getName('')) . $this->e($k) . rand(0, 100)); ?>
        <div class="field">
            <div class="ui radio checkbox">
                <input type="radio"
                    <?php
                    if ($field->hasAttr('name')) {
                        echo ' name="' . $this->e($field->getAttr('name')) . '" ';
                    }
                    if ($disabled) {
                        echo ' disabled="disabled" ';
                    }
                    if ($k == $field->getValue()) {
                        echo ' checked="checked" ';
                    }
                    ?>
                    id="<?= $this->e($temp) ?>"
                    value="<?= $this->e($k) ?>"
                >
                <label for="<?= $this->e($temp) ?>"><?= $this->e($v); ?></label>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<script nonce="<?= $this->e($cspNonce) ?>">
$('#<?= $this->e($id) ?> .checkbox').checkbox();
</script>
