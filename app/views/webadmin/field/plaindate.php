<?php

/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 */
if ($field->hasAttr('value')) {
    if (
        $field->getValue() === '0000-00-00' ||
        $field->getValue() === '0000-00-00 00:00:00' ||
        strtotime($field->getValue()) === false
    ) {
        $field->setValue('');
    } else {
        $field->setValue(date('Y-m-d', strtotime($field->getValue())));
    }
}
echo $this->insert('webadmin::field/text', [ 'field' => $field->setType('date') ]);
