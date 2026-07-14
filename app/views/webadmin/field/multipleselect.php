<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 * @var callable (array<string>): string $nbsp
 */
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'mselect_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
if (!$field->hasAttr('value')) {
    $field->setValue([]);
}
if (!is_array($field->getValue())) {
    $temp = json_decode($field->getValue(), true);
    if (!$temp || !is_array($temp)) {
        $temp = [];
    }
    $field->setValue($temp);
}
$id = $field->getAttr('id');
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
$field->addClass('ui fluid dropdown multiple tags search');
$field->setAttr('multiple', 'multiple');
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
<?php if ($field->getOption('textOnly')) : ?>
    <?php
    $value = [];
    foreach ($field->getOption('optgroups', []) as $label => $values) {
        foreach ($values as $k => $v) {
            if (in_array($k, $field->getValue())) {
                $value[] = preg_replace_callback(
                    '([ ]{2,})',
                    $nbsp,
                    $this->e($field->getOption('translate', false) ? $intl($v) : $v)
                );
            }
        }
    }
    foreach ($field->getOption('values', []) as $k => $v) {
        if (in_array($k, $field->getValue())) {
            $value[] = preg_replace_callback(
                '([ ]{2,})',
                $nbsp,
                $this->e($field->getOption('translate', false) ? $intl($v) : $v)
            );
        }
    }
    ?>
    <div>
        <?= implode(', ', $value) ?>
    </div>
<?php else : ?>
    <select
        <?=
            $this->insert(
                'webadmin::field/attrs',
                [
                    'attrs' => $field->getAttrs(),
                    'skip' => ['data-validate'],
                    'translate' => ['placeholder', 'title']
                ]
            )
        ?>
    >
        <option value="">
            <?php
            if ($field->hasAttr('placeholder')) {
                echo $this->e($intl($field->getAttr('placeholder')));
            }
            ?>
        </option>
        <?php foreach ($field->getOption('optgroups', []) as $label => $values) : ?>
            <optgroup label="<?= $this->e($label) ?>">
            <?php foreach ($values as $k => $v) : ?>
                <option
                    value="<?= $this->e($k) ?>"
                    <?php if (in_array($k, $field->getValue())) : ?>
                        selected="selected"
                    <?php endif ?>
                    >
                    <?= preg_replace_callback(
                        '([ ]{2,})',
                        $nbsp,
                        $this->e($field->getOption('translate', false) ? $intl($v) : $v)
                    ) ?>
                </option>
            <?php endforeach ?>
            </optgroup>
        <?php endforeach ?>
        <?php foreach ($field->getOption('values', []) as $k => $v) : ?>
            <option
                value="<?= $this->e($k) ?>"
                <?php if (in_array($k, $field->getValue())) : ?>
                    selected="selected"
                <?php endif ?>
            >
                <?= preg_replace_callback(
                    '([ ]{2,})',
                    $nbsp,
                    $this->e($field->getOption('translate', false) ? $intl($v) : $v)
                ) ?>
            </option>
        <?php endforeach ?>
    </select>
    <?php if (!$field->getOption('noJS')) : ?>
        <script nonce="<?= $this->e($cspNonce) ?>">
        $('#<?= $this->e($id) ?>').dropdown({ allowAdditions : false });
        // reset order
        var f = function (e) { e.stopImmediatePropagation(); };
        $('#<?= $this->e($id) ?>').on('change', f);
        $('#<?= $this->e($id) ?>').dropdown('set exactly', []);
        <?php foreach ($field->getValue() as $v) : ?>
        $('#<?= $this->e($id) ?>').dropdown('set selected', JSON.parse('<?= json_encode($v) ?>'));
        <?php endforeach ?>
        $('#<?= $this->e($id) ?>').off('change', f);
        <?php if ($disabled) : ?>
        $('#<?= $this->e($id) ?>').parent().addClass('disabled');
        <?php endif ?>
        </script>
    <?php endif ?>
<?php endif ?>
