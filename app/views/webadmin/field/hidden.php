<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 */
?>
<?php if (!$field->getOption('textOnly')) : ?>
<input
    type="hidden"
    <?= $this->insert('webadmin::field/attrs', [ 'attrs' => $field->getAttrs(), 'skip' => [], 'translate' => [] ]) ?>
    />
<?php endif ?>
