<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 */
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'checkboxes_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
if ($field->hasAttr('value') && is_string($field->getValue()) && ($temp = json_decode($field->getValue(), true))) {
    $field->setValue($temp);
}
if (!is_array($field->getValue())) {
    $field->setValue([]);
}
$keys = [
    1 => 'one','two','three','four','five','six','seven','eight','nine','ten',
    'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen'
];
?>
<?php if ($field->hasAttr('data-serialize')) : ?>
<div data-serialize="<?= $this->e($field->getAttr('data-serialize')) ?>">
<?php endif ?>
<?php if ($field->getOption('grid') && !$field->getOption('nolabel') && strlen($field->getOption('label', ''))) : ?>
    <label id="<?= $this->e($field->getAttr('id')) ?>_label">
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
<div class="<?= $field->getOption('inline') ? 'inline' : 'grouped' ?> fields"
    id="<?= $this->e($field->getAttr('id')) ?>">
    <?php
    if (
        !$field->getOption('grid') &&
        !$field->getOption('nolabel') &&
        strlen($field->getOption('label', ''))
    ) :
        ?>
        <label id="<?= $this->e($field->getAttr('id')) ?>_label">
            <?php if ($field->getOption('tooltip')) : ?>
                <span 
                    data-tooltip="<?= $this->e($intl($field->getOption('tooltip'))) ?>"
                    data-inverted="">
                    <i class="question circle icon"></i>
                </span>
            <?php endif ?>
            <?= $this->e($intl($this->e($field->getOption('label')))) ?>
        </label>
    <?php endif ?>
    <?php if (!$field->getOption('noReset')) : ?>
        <input
            type="hidden"
            value=""
            id="<?= $field->getAttr('id') ?>_reset"
            <?php
            if ($field->hasAttr('name')) {
                echo ' name="' . $this->e($field->getAttr('name')) . '" ';
            }
            ?>
            />
    <?php endif ?>
    <?php if ($field->getOption('grid')) : ?>
        <div class="ui <?= $keys[(int)$field->getOption('grid')] ?> column grid checkboxes-field-grid">
            <div class="row">
    <?php endif ?>
    <?php foreach ($field->getOption('values') as $k => $v) : ?>
        <?php $temp = md5($this->e($field->getName()) . $this->e($k) . rand(0, 100)); ?>
        <?php if ($field->getOption('grid')) : ?>
            <div class="column">
        <?php endif ?>
        <div class="field">
            <div class="ui checkbox">
                <input type="checkbox"
                    <?php
                    if (in_array($k, $field->getValue([]))) {
                        echo ' checked="checked" ';
                    }
                    if ($field->getOption('translate') || $field->getOption('translateValues')) {
                        $v = $intl($v);
                    }
                    if ($field->hasAttr('disabled') || $field->hasAttr('readonly')) {
                        echo ' disabled="disabled" ';
                    }
                    if ($field->hasAttr('name')) {
                        echo ' name="' . $this->e($field->getAttr('name')) . '[]" ';
                    }
                    if ($field->hasAttr('data-redraw')) {
                        echo ' data-redraw="' . $this->e($field->getAttr('data-redraw')) . '" ';
                    }
                    ?>
                    value="<?= $this->e($k) ?>"
                    id="<?= $this->e($temp) ?>"
                >
                <label for="<?= $this->e($temp) ?>"><?= $this->e($v) ?></label>
            </div>
        </div>
        <?php if ($field->getOption('grid')) : ?>
            </div>
        <?php endif ?>
    <?php endforeach; ?>
    <?php if ($field->getOption('grid')) : ?>
            </div>
        </div>
    <?php endif ?>
</div>
<?php if (!$field->getOption('noJS')) : ?>
<script nonce="<?= $this->e($cspNonce) ?>">
$("#<?= $this->e($field->getAttr('id')) ?> .checkbox").checkbox();
$("#<?= $this->e($field->getAttr('id')) ?>_label")
    .css({ 'cursor' : 'pointer', 'position' : 'relative', 'zIndex' : 0 })
    .click(function (e) {
        var checks = $("#<?= $this->e($field->getAttr('id')) ?> :checkbox");
        checks.prop('checked', checks.not(':checked').length ? true : false);
    });
$("#<?= $this->e($field->getAttr('id')) ?> :checkbox").on('change', function () {
    $('#<?= $this->e($field->getAttr('id')) ?>_reset')
        .prop('disabled', $("#<?= $this->e($field->getAttr('id')) ?> :checked").length > 0);
});
$('#<?= $this->e($field->getAttr('id')) ?>_reset')
    .prop('disabled', $("#<?= $this->e($field->getAttr('id')) ?> :checked").length > 0);
</script>
<?php endif ?>
<?php if ($field->hasAttr('data-serialize')) : ?>
</div>
<?php endif ?>
