<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 */
$field->setType('password');
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
if (!$disabled && $field->hasOption('values')) {
    $field->setAttr('list', 'input_' . md5($field->getName('') . microtime() . rand(0, 100)) . '_list');
}
$passID = 'password_' . md5($field->getName('') . microtime() . rand(0, 100));
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
<div class="ui 
    <?php
    if ($field->hasAttr('disabled')) {
        echo 'disabled ';
    }
    if ($field->hasOption('prefix') || $field->hasOption('suffix')) {
        echo 'right labeled ';
    }
    ?> 
    icon
    input"
>
    <?php if ($field->hasOption('prefix')) : ?>
        <div class="ui label"><?= $this->e($intl($field->getOption('prefix'))) ?></div>
    <?php endif ?>
    <input
        <?=
            $this->insert(
                'webadmin::field/attrs',
                [
                    'attrs' => $field->getAttrs(),
                    'skip' => ['data-validate'],
                    'translate' => ['placeholder', 'title']
                ]
            )
            ?>
        />
        <i class="circular eye slash link icon" id="<?= $this->e($passID) ?>"></i>
    <?php if ($field->hasOption('suffix')) : ?>
        <div class="ui label"><?= $this->e($intl($field->getOption('suffix'))) ?></div>
    <?php endif ?>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
::-ms-reveal { display:none }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$('#<?= $this->e($passID) ?>').on('click', function (e) {
    e.preventDefault();
    $(this).toggleClass('slash').prev().attr('type', $(this).prev().attr("type") === "password" ? "text" : "password");
});
</script>
