<?php

/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var string $cspNonce
 */
if ($field->getOption('view')) {
    echo $this->insert(
        $field->getOption('view'),
        [
            'field' => $field
        ]
    );
}
