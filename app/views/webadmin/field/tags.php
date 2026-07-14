<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 * @var callable (array<string>): string $nbsp
 */
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'tags_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
if ($field->hasAttr('value') && is_string($field->getValue()) && ($temp = json_decode($field->getValue(), true))) {
    $field->setValue($temp);
}
if (!is_array($field->getValue())) {
    $field->setValue([]);
}
$values = $field->getOption('values', []);
if (is_array($field->getValue())) {
    foreach ($field->getValue() as $v) {
        if (!isset($values[$v])) {
            $values[$v] = $v;
        }
    }
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
    foreach ($values as $k => $v) {
        if (in_array($k, $field->getValue())) {
            $value[] = preg_replace_callback(
                '([ ]{2,})',
                $nbsp,
                $this->e($v)
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
                    $this->e($v)
                ) ?>
            </option>
        <?php endforeach ?>
    </select>
    <?php if (!$field->getOption('noJS')) : ?>
        <script nonce="<?= $this->e($cspNonce) ?>">
        $('#<?= $this->e($id) ?>').dropdown({ allowAdditions : true, forceSelection : false });
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

        $('#<?= $this->e($id) ?>').parent().find('input.search').on('keydown', function (e) {
            if (e.keyCode === 9 || e.keyCode === 13) {
                e.preventDefault();
                $(this).focus();
            }
        });
        </script>
    <?php endif ?>
<?php endif ?>
