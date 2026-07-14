<?php

/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 */

?>
<?php
$href = (string)$field->getOption('href');
$text = (string)($field->getOption('text'));
?>
<a class="ui  button green" href="<?= $this->e($href) ?>">
    <i class="file pdf icon"></i> <?= $this->e($text) ?>
</a>