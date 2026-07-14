<?php

/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var string $cspNonce
 */

?>
<div class="ui red message">
    <?= $this->e((string)$field->getValue()) ?>
</div>


