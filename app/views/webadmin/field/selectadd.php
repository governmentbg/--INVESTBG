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
<?php elseif ($field->getOption('noJS')) : ?>
<div class="ui 
    <?php
    if ($field->hasAttr('disabled')) {
        echo 'disabled ';
    }
    if ($field->hasOption('prefix') || $field->hasOption('suffix')) {
        echo 'right labeled ';
    }
    ?> 
    input"
>
    <?php if ($field->hasOption('prefix')) : ?>
        <div class="ui label"><?= $this->e($intl($field->getOption('prefix'))) ?></div>
    <?php endif ?>
    <input
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
        list="<?= $this->e($id) ?>_list"
        />
    <?php if ($field->hasOption('suffix')) : ?>
        <div class="ui label"><?= $this->e($intl($field->getOption('suffix'))) ?></div>
    <?php endif ?>
    <datalist id="<?= $this->e($id) ?>_list">
        <?php foreach ($field->getOption('optgroups', []) as $label => $values) : ?>
            <?php foreach ($values as $k => $v) : ?>
                <option value="<?= $this->e($k) ?>">
                    <?= preg_replace_callback(
                        '([ ]{2,})',
                        $nbsp,
                        $this->e($field->getOption('translate', false) ? $intl($v) : $v)
                    ) ?>
                </option>
            <?php endforeach ?>
        <?php endforeach ?>
        <?php foreach ($field->getOption('values', []) as $k => $v) : ?>
            <option value="<?= $this->e($k) ?>">
                <?= preg_replace_callback(
                    '([ ]{2,})',
                    $nbsp,
                    $this->e($field->getOption('translate', false) ? $intl($v) : $v)
                ) ?>
            </option>
        <?php endforeach ?>
    </datalist>
</div>
<?php else : ?>
    <?php $found = false; ?>
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
        <?php foreach ($field->getOption('optgroups', []) as $label => $values) : ?>
            <optgroup label="<?= $this->e($label) ?>">
            <?php foreach ($values as $k => $v) : ?>
                <option
                    value="<?= $this->e($k) ?>"
                    <?php if ($k == $field->getValue()) : ?>
                        selected="selected"
                        <?php $found = true; ?>
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
                    <?php $found = true; ?>
                <?php endif ?>
            >
                <?= preg_replace_callback(
                    '([ ]{2,})',
                    $nbsp,
                    $this->e($field->getOption('translate', false) ? $intl($v) : $v)
                ) ?>
            </option>
        <?php endforeach ?>
        <?php if (!$found) : ?>
        <option value="<?= $this->e($field->getValue()) ?>" selected="selected">
            <?= preg_replace_callback(
                '([ ]{2,})',
                $nbsp,
                $this->e($field->getOption('translate', false) ? $intl($field->getValue()) : $field->getValue())
            ) ?>
        </option>
        <?php endif ?>
    </select>
    <script nonce="<?= $this->e($cspNonce) ?>">
    $('#<?= $this->e($id) ?>').dropdown({ allowAdditions: true, hideAdditions: false, forceSelection : false, message: {
        addResult     : 'Добави <b>{term}</b>' } });
    <?php if ($disabled) : ?>
        $('#<?= $this->e($id) ?>').parent().addClass('disabled');
    <?php endif ?>
    </script>
<?php endif ?>
