<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 * @var callable (array<string>): string $nbsp
 */
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'select_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$id = $field->getAttr('id');
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
$field->addClass('ui fluid dropdown search');
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
    $value = null;
    foreach ($field->getOption('optgroups', []) as $label => $values) {
        $value = $values[$field->getValue()] ?? null;
    }
    if ($value === null) {
        $value = $field->getOption('values', [])[$field->getValue()] ?? '';
    }
    ?>
    <div>
        <?= preg_replace_callback(
            '([ ]{2,})',
            $nbsp,
            $this->e($field->getOption('translate', false) ? $intl($value) : $value)
        ) ?>
    </div>
<?php else : ?>
    <select
        <?=
            $this->insert(
                'webadmin::field/attrs',
                [
                    'attrs' => $field->getAttrs(),
                    'skip' => ['data-validate', 'value', 'type' ],
                    'translate' => ['placeholder', 'title']
                ]
            )
        ?>
    >
        <?php foreach ($field->getOption('optgroups', []) as $label => $values) : ?>
            <optgroup label="<?= $this->e($label) ?>">
            <?php foreach ($values as $k => $v) : ?>
                <option
                    value="<?= $this->e($k) ?>"
                    <?php if ($k == $field->getValue()) : ?>
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
                <?php if ($k == $field->getValue()) : ?>
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
        $('#<?= $this->e($id) ?>').dropdown({ forceSelection : false });
        <?php if ($disabled) : ?>
            $('#<?= $this->e($id) ?>').parent().addClass('disabled');
        <?php endif ?>
        </script>
    <?php endif ?>
<?php endif ?>
