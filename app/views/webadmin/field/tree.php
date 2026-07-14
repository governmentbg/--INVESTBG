<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 * @var callable (array<string>): string $nbsp
 */
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'tree_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$id = $field->getAttr('id');
if (!$field->hasAttr('value') && is_string($field->getValue()) && ($temp = json_decode($field->getValue(), true))) {
    $field->setValue($temp);
}
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
$temp = $field->getOptions();
unset($temp['values']);
unset($temp['multiple']);
$config = array_merge([
    'core' => [
        'worker' => false,
        'data' => $field->getOption('values'),
        'multiple' => !!$field->getOption('multiple')
    ]
], $temp);
$ids = [];
foreach ($field->getOption('values') as $v) {
    $ids[] = $v['id'];
}
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
<select
    class="hidden-tree"
    size="10"
    <?php
    if ($field->getOption('multiple')) {
        echo ' multiple="multiple" ';
    }
    echo ' id="' . $this->e($id) . '_select" ';
    if ($field->hasAttr('name')) {
        echo ' name="' . $this->e($field->getAttr('name')) . ($field->getOption('multiple') ? '[]' : '') . '" ';
    }
    if ($disabled) {
        echo ' disabled="disabled" ';
    }
    ?>
>
    <?php foreach ($field->getOption('values') as $k => $v) : ?>
        <option
            value="<?= $this->e($v['id']) ?>"
            <?php
            if (
                ($field->getOption('multiple') && in_array($v['id'], $field->getValue([]))) ||
                $v['id'] == $field->getValue('')
            ) :
                ?>
                selected="selected"
            <?php endif ?>
        ><?= $this->e($v['text']) ?></option>
    <?php endforeach ?>
</select>
<div id="<?= $this->e($id) ?>"></div>
<script nonce="<?= $this->e($cspNonce) ?>">
$('#<?= $this->e($id) ?>')
    .on('ready.jstree', function (e, data) {
        var value = $('#<?= $this->e($id) ?>_select').val();
        //data.instance.open_all();
        data.instance.deselect_all();
        data.instance.select_node(value);
        <?php if ($disabled) : ?>
        data.instance.disable_node(<?php echo json_encode($ids); ?>);
        <?php endif ?>
    })
    .on('changed.jstree', function (e, data) {
        $('#<?= $this->e($id) ?>_select').val(
            data.selected.length === 0 ? [] : (data.selected.length === 1 ? data.selected[0] : data.selected)
        );
    })
    .jstree(<?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
</script>
