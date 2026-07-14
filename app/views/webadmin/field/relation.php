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
    $field->setAttr('id', 'relation_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$id = $field->getAttr('id');
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
$field->addClass('ui fluid clearable selection dropdown search');
$value = $field->getValue();
$picker = $field->getOption('picker', true);
/** @var array<string,\webadmin\modules\VisualModuleInterface> $m */
$ms = $field->getOption('modules', []);
/** @var ?\webadmin\modules\VisualModuleInterface $m */
$m = $field->getOption('module', array_values($ms)[0] ?? null);
$u = $field->getOption('url');
$a = $field->getOption('ajax');
if ($m) {
    if (!$u) {
        $u = $m->getSlug();
    }
    if (!$a) {
        $a = $m->getSlug() . '/json?l=100';
    }
}
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
<div id="modal_<?= $this->e($field->getAttr('id')) ?>" class="ui fullscreen modal"></div>
<input
    id="<?= $this->e($field->getAttr('id')) ?>_reset"
    type="hidden"
    value=""
    <?php
    if ($field->hasAttr('name')) {
        echo ' name="' . $this->e($field->getAttr('name')) . '" ';
    }
    ?>
    />
<?php if ($picker && !$disabled) : ?>
<div class="ui action input">
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
<?php if ($picker && !$disabled) : ?>
<button class="ui small orange labeled icon button" id="button_<?= $this->e($field->getAttr('id')) ?>">
    <i class="check icon"></i> <?= $this->e($intl('fields.module.pick')) ?>
</button>
</div>
<?php endif ?>
<script nonce="<?= $this->e($cspNonce) ?>">
$(function () {
    var multiple = $('#<?= $this->e($id) ?>').attr('multiple');
    var value = JSON.parse('<?= json_encode($field->getValue()) ?>');
    var vPromise = [{ results: [] }];
    var lPromise = [{ results: [] }];
    var ajaxUrl = JSON.parse('<?= json_encode($url($a)) ?>');

    value = Array.isArray(value) ? value : (value ? [ value ] : []);
    if (value && value.length) {
        value = Array.isArray(value) ? value : [ value ];
        if (!multiple) {
            value = value.slice(0,1);
        }
        vPromise = $.get(ajaxUrl + (ajaxUrl.indexOf('?') !== -1 ? '&' : '?') + 'id=' + value.join(','));
    }
    //lPromise = $.get(ajaxUrl);

    $.when(vPromise, lPromise).done(function (v, l) {
        var o = $('#<?= $this->e($id) ?>');
        var r = $('#<?= $this->e($id) ?>_reset');
        o.empty();
        $.each(v[0].results, function (i, vv) {
            o.append(
                $('<option>')
                    .attr('value', vv.value)
                    .text(vv.name)
            );
        });
        $.each(l[0].results, function (i, vv) {
            if (!o.children('[value="' + vv.value + '"]').length) {
                o.append(
                    $('<option>')
                        .attr('value', vv.value)
                        .text(vv.name)
                );
            }
        });
        o.on('change', function () {
            o.prop('disabled', o.val().length == 0);
            r.prop('disabled', o.val().length > 0);
        });
        o.dropdown({
            apiSettings : {
                url: ajaxUrl + (ajaxUrl.indexOf('?') !== -1 ? '&' : '?') + 'q={query}',
                cache : false
            },
            saveRemoteData : false,
            forceSelection : false
        });
        o.dropdown('queryRemote', '', function() {});
        <?php if ($disabled) : ?>
            o.parent().addClass('disabled');
        <?php endif ?>
        if (value) {
            var f = function (e) { e.stopImmediatePropagation(); };
            o.on('change', f);
            o.dropdown('set exactly', []);
            $.each(value, function (i, v) {
                o.dropdown('set selected', v);
            });
            o.off('change', f);
        }
        o.prop('disabled', o.val().length == 0);
        r.prop('disabled', o.val().length > 0);
    });
    <?php if ($picker && !$disabled) : ?>
    $('#button_<?= $this->e($id) ?>').click(function (e) {
        e.preventDefault();
        $('#modal_<?= $this->e($id) ?>')
            .html('<iframe class="module-field-iframe" src="" width="100%" height="80vh"></iframe>')
            .find('iframe')
                .off('load')
                .on('load', function () {
                    var iframe = this.contentWindow;
                    if (iframe.selectedPromise) {
                        iframe.selectedPromise.then(function (vv) {
                            $.get(
                                ajaxUrl + (ajaxUrl.indexOf('?') !== -1 ? '&' : '?') + 'id=' +
                                (multiple && Array.isArray(vv.id) ? vv.id.join(',') : vv.id)
                            )
                                .done(function (data) {
                                    var o = $('#<?= $this->e($id) ?>');
                                    if (!multiple) {
                                        o.dropdown('clear');
                                    }
                                    var s = [];
                                    $.each(data.results, function (i, v) {
                                        if (!o.children('[value="' + v.value + '"]').length) {
                                            o.append(
                                                $('<option>')
                                                    .attr('value', v.value)
                                                    .text(v.name)
                                            );
                                        }
                                        s.push(v.value);
                                    });
                                    o.dropdown('refresh');
                                    setTimeout(function () { 
                                        o.dropdown('set selected', s);
                                        o.change();
                                    }, 100);
                                })
                                .fail(function () {
                                    window.location.reload();
                                });
                            $('#modal_<?= $this->e($id) ?>').modal('hide');
                        });
                    } else {
                        $('#modal_<?= $this->e($id) ?>').modal('hide');
                    }
                    var val = !multiple ?
                        [$('#<?= $this->e($id) ?>').val()] :
                        $('#<?= $this->e($id) ?>').val();
                    if (val && val.length) {
                        iframe.$('.table-read > tbody > tr').each(function () {
                            if (val.indexOf(iframe.$(this).data('id').toString()) !== -1) {
                                $(this).addClass('positive').find('td').eq(-1).empty();
                            }
                        });
                    }
                })
                .attr('src', "<?= $this->e($url($u)) ?>")
                .end()
            .modal('show');
    });
    <?php endif ?>
});
</script>

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
