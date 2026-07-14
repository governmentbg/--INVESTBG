<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var callable (array<string>): string $nbsp
 */
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
if (!$disabled && $field->hasOption('values')) {
    $field->setAttr('list', 'input_' . md5($field->getName('') . microtime() . rand(0, 100)) . '_list');
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
<?php if ($field->getOption('textOnly')) : ?>
    <div><?= $this->e($field->getValue('')) ?></div>
<?php else : ?>
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
        />
    <?php if ($field->hasOption('suffix')) : ?>
        <div class="ui label"><?= $this->e($intl($field->getOption('suffix'))) ?></div>
    <?php endif ?>
    <?php if (!$disabled && $field->hasOption('values')) : ?>
        <datalist id="<?= $this->e($field->getAttr('list')) ?>">
            <?php foreach ($field->getOption('values', []) as $v) : ?>
                <?php
                    $v = preg_replace_callback(
                        '([ ]{2,})',
                        $nbsp,
                        $this->e($field->getOption('translate', false) ? $intl($v) : $v)
                    );
                ?>
                <option value="<?= $v ?>">
            <?php endforeach ?>
        </datalist>
    <?php endif ?>
</div>
<?php endif ?>
