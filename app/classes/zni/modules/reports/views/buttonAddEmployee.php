<?php

/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 */
?>
<?php
    $href = (string)$field->getOption('href');
    $text = (string)($field->getOption('text') ?: 'Добави служител');
?>
<a class="ui mini button green" href="<?= $this->e($href) ?>">
    <i class="plus icon"></i> <?= $this->e($text) ?>
</a>