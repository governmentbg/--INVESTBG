<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 * @var \vakata\http\Uri $url
 * @var callable (array<string>): string $nbsp
 */
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'select_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$id = $field->getAttr('id');
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
$field->addClass('ui fluid dropdown search');
?>
<?php if (strlen($field->getOption('label', ''))) : ?>
    <label>
        <?= $this->e($intl($field->getOption('label'))) ?>
        <?php if ($field->getOption('tooltip')) : ?>
            <i class="ui help icon"
                data-tooltip="<?= $this->e($intl($field->getOption('tooltip'))) ?>"
                data-inverted=""></i>
        <?php endif ?>
    </label>
<?php endif ?>
<select
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
>
    <?php foreach ($field->getOption('values', []) as $k => $v) : ?>
        <option
            value="<?= $this->e($k) ?>"
            <?php if ($k == $field->getValue()) : ?>
                selected="selected"
            <?php endif ?>
        >
            <?= preg_replace_callback(
                '([ ]{2,})',
                $nbsp,
                $this->e($v)
            ) ?>
        </option>
    <?php endforeach ?>
</select>
<?php if ($field->getAttr('value')) : ?>
<script nonce="<?= $this->e($cspNonce) ?>">
$(function () {
    var value = JSON.parse('<?= json_encode($field->getValue()) ?>');
    value = Array.isArray(value) ? value : [ value ];
    $('#<?= $this->e($id) ?>').empty().append('<option value=""> --- </option>');
    $.get(JSON.parse('<?= json_encode($url($field->getOption('url'))) ?>') + '&id=' + value.join(','))
        .done(function (data) {
            $.each(data.results, function (i, v) {
                $('#<?= $this->e($id) ?>').append(
                    $('<option>')
                        .attr('selected', 'selected')
                        .attr('value', v.value)
                        .text(v.name)
                        .prop('selected', true)
                );
            });
            $('#<?= $this->e($id) ?>').dropdown({
                apiSettings : {
                    url: (JSON.parse('<?= json_encode($url($field->getOption('url'))) ?>'))
                        .replace("%7Bquery%7D", "{query}")
                },
                saveRemoteData: false,
                forceSelection : false
            });
            $('#<?= $this->e($id) ?>').change();
        })
        .fail(function () {
            window.location.reload();
        })
});
</script>
<?php else : ?>
<script nonce="<?= $this->e($cspNonce) ?>">
$('#<?= $this->e($id) ?>').dropdown({
    apiSettings : {
        url: (JSON.parse('<?= json_encode($url($field->getOption('url'))) ?>'))
            .replace("%7Bquery%7D", "{query}")
    },
    saveRemoteData: false,
    forceSelection : false
});
</script>
<?php endif ?>
<?php
/*
$form
    ->addField(
        new Field(
            'ajax',
            [ 'name' => 'test', 'value' => '1' ],
            [ 'url' => 'users/json?field=name&l=100&q={query}', 'label' => $module . '.columns.test', 'values' => [] ]
        )
    );
*/
