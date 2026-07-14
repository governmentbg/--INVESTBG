<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 */
?>
<?php
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'files_' . md5($field->getName('') . microtime() . rand(0, 100)));
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
    <pre><?= ($this->e($field->getValue(''))) ?></pre>
<?php else : ?>
<textarea
    <?=
        $this->insert(
            'webadmin::field/attrs',
            [
                'attrs' => $field->getAttrs(),
                'skip' => ['data-validate', 'value'],
                'translate' => ['placeholder', 'title']
            ]
        )
    ?>
><?= $this->e($field->getValue('')) ?></textarea>
<?php endif ?>
<style nonce="<?= $this->e($cspNonce) ?>">
#<?= $this->e($field->getAttr('id')) ?> {
    white-space: pre;
    font-family: monospace, monospace;
    max-height: none;
    min-height: 24rem;
}
</style>
