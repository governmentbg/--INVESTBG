<?php

/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var \vakata\http\Request $req
 */
?>
<h3><?= $this->e($intl('maintenancereports.total_refund_amount')) ?></h3>
<p><?= $this->e(number_format($field->getValue(), 2)) ?></p>
