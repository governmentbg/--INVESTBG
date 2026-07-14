<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var string $cspNonce
 */
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'datetime_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
if ($field->hasAttr('value')) {
    if (
        $field->getValue() === '0000-00-00' ||
        $field->getValue() === '0000-00-00 00:00:00' ||
        strtotime($field->getValue()) === false
    ) {
        $field->setValue('');
    } else {
        $field->setValue(date('d.m.Y H:i', strtotime($field->getValue())));
    }
}
$options = $field->getOptions();
$options['mode'] = 'datetime';
$options['time'] = [
    'style' => 'input',
    'seconds' => false
];
if (!$field->getOption('textOnly') && $field->getOption('style') === 'inline') {
    echo $this->insert('webadmin::field/hidden', [ 'field' => $field->setType('hidden') ]);
} else {
    echo $this->insert('webadmin::field/text', [
        'field' => $field
            ->setType('text')
            ->setAttr('autocomplete', 'off')
    ]);
}
?>
<?php
if (
    !$field->getOption('textOnly') &&
    !$field->hasAttr('disabled') &&
    !$field->hasAttr('readonly') &&
    !$field->getOption('noJS')
) :
    ?>
<script nonce="<?= $this->e($cspNonce) ?>">
$("#<?= $this->e($field->getAttr('id')) ?>").dtpckr(
    JSON.parse(
        '<?=json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT)?>'
    )
);
</script>
<?php endif ?>
