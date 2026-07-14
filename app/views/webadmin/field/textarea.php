<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
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
<?php if ($field->getOption('textOnly')) : ?>
    <div><?= nl2br($this->e($field->getValue(''))) ?></div>
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
