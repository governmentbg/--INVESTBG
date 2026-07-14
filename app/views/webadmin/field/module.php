<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 * @var \vakata\http\Uri $url
 */
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'map_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
if ($field->hasAttr('value') && !is_array($field->getValue()) && $field->getOption('multiple')) {
    $temp = json_decode((string)$field->getValue(), true);
    if ($temp !== null) {
        if (!is_array($temp)) {
            $temp = [$temp];
        }
        $field->setValue($temp);
    }
}
$id = $field->getAttr('id');
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
$options = $field->getOptions();
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
<?php if ($field->getOption('multiple')) : ?>
    <input
        id="<?= $this->e($field->getAttr('id')) ?>"
        type="hidden"
        <?php
        if ($field->hasAttr('name')) {
            echo ' name="' . $this->e($field->getAttr('name')) . '" ';
        }
        ?>
        value=""
        <?= $disabled ? ' disabled="disabled" ' : '' ?> />
    <div id="<?= $field->getAttr('id') ?>_inputs" class="hide">
        <?php if ($field->hasAttr('value') && is_array($field->getValue())) : ?>
            <?php foreach (array_filter($field->getValue()) as $v) : ?>
                <input
                    type="hidden"
                    <?php
                    if ($field->hasAttr('name')) {
                        echo ' name="' . $this->e($field->getAttr('name')) . '[]" ';
                    }
                    ?>
                    value="<?= $this->e($v) ?>"
                    <?= $disabled ? ' disabled="disabled" ' : '' ?>
                    />
            <?php endforeach ?>
        <?php endif ?>
    </div>
<?php else : ?>
    <input
        id="<?= $this->e($field->getAttr('id')) ?>"
        type="hidden"
        <?php if ($field->hasAttr('name')) {
            echo ' name="' . $this->e($field->getAttr('name')) . '" ';
        }
        ?>
        value="<?= $this->e($field->getValue('')) ?>"
        <?= $disabled ? ' disabled="disabled" ' : '' ?> />
<?php endif ?>

<div id="modal_<?= $this->e($field->getAttr('id')) ?>" class="ui fullscreen modal"></div>
<div id="table_<?= $this->e($field->getAttr('id')) ?>"></div>
<?php if (!$disabled) : ?>
    <p class="module-field-operations">
        <?php if ($field->getOption('createUrl')) : ?>
        <button class="ui small green labeled icon button" id="button_add_<?= $this->e($field->getAttr('id')) ?>">
            <i class="plus icon"></i> <?= $this->e($intl('fields.module.create')) ?>
        </button>
        <?php endif ?>
        <button class="ui small orange labeled icon button" id="button_<?= $this->e($field->getAttr('id')) ?>">
            <i class="check icon"></i> <?= $this->e($intl('fields.module.pick')) ?>
        </button>
    </p>
<?php endif ?>

<script nonce="<?= $this->e($cspNonce) ?>">
(function () {
    var multiple = parseInt('<?= $field->getOption("multiple") ? 1 : 0 ?>', 10);
    var value = !multiple ?
        [$('#<?= $this->e($field->getAttr("id")) ?>').val()] :
        $('#<?= $this->e($field->getAttr("id")) ?>_inputs input')
            .toArray()
            .map(function (v) { return v.value; }).filter(function (v) { return v !== ''; });
    value = value.filter(function (v) { return v !== ''; });
    if (multiple) {
        $('#<?= $this->e($field->getAttr("id")) ?>').prop('disabled', value.length > 0);
    }
    if (value.length) {
        // GET table from URL and display inside the table DIV removing the loader
        $.get('<?= $this->e($url($options["url"])) ?>', { '<?= $this->e($options["id"]) ?>' : value })
            .done(function (data) {
                var tbl = $(data).find('table').clone();
                tbl.find('tfoot').remove();
                tbl.find('.quick-filter').remove();
                tbl.find('thead .button').remove();
                tbl.find('th').each(function () { $(this).text($(this).find('a').text()); });
                tbl
                    .find('td:last-child')
                        .empty()
                        <?php if (!$disabled) : ?>
                        .append('<a href="#" class="ui mini red labeled icon button row-pick">'+
                            '<i class="ui remove icon"></i> <?= $this->e($intl('fields.module.remove')) ?></a>')
                        <?php endif ?>
                $("#table_<?= $this->e($field->getAttr('id')) ?>").empty().append(tbl);
            });
    } else {
        $("#table_<?= $this->e($field->getAttr('id')) ?>").empty();
    }
    $("#table_<?= $this->e($field->getAttr('id')) ?>").on('click', '.row-pick', function (e) {
        e.preventDefault();
        $(this).closest('tr').remove();
        if (!$('#table_<?= $this->e($id) ?> tbody > tr').length) {
            $('#table_<?= $this->e($id) ?> table').remove();
        }
        var tmp = [];
        $('#table_<?= $this->e($id) ?> tbody > tr').each(function () {
            tmp.push($(this).data('id'));
        });
        if (multiple) {
            $('#<?= $this->e($id) ?>_inputs').empty();
            tmp.forEach(function (v) {
                var i = $('#<?= $this->e($id) ?>').clone();
                i.prop('disabled', false);
                i.attr('name', i.attr('name') + '[]');
                i.val(v);
                i.removeAttr('id');
                $('#<?= $this->e($id) ?>_inputs').append(i);
            });
            $('#<?= $this->e($field->getAttr("id")) ?>').prop('disabled', tmp.length > 0);
        } else {
            $('#<?= $this->e($id) ?>').val(tmp.join(','));
        }
    });
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
                            if (!multiple) {
                                $('#<?= $this->e($id) ?>').val(vv.id).change();
                                $('#table_<?= $this->e($id) ?>')
                                    .html(
                                        '<table class="ui basic selectable compact table"><thead>' +
                                        vv.head.html() +
                                        '</thead><tbody></tbody></table>'
                                    );
                                $('#table_<?= $this->e($id) ?>').find('thead .button').remove();
                                $('#table_<?= $this->e($id) ?>').find('th')
                                    .each(function () { $(this).text($(this).find('a').text()); });
                                $('#table_<?= $this->e($id) ?> tbody').append(vv.html.clone());
                                $('#table_<?= $this->e($id) ?> tbody')
                                    .find('td:last-child')
                                        .empty()
                                        .append(
                                            '<a href="#" class="ui mini red labeled icon button row-pick">'+
                                            '<i class="ui remove icon"></i> '+
                                            '<?= $this->e($intl('fields.module.remove')) ?></a>'
                                        )
                                        .end().find('.quick-filter').remove();
                                $('#modal_<?= $this->e($id) ?>').modal('hide');
                            } else {
                                var i = $('#<?= $this->e($id) ?>').clone();
                                i.attr('name', i.attr('name') + '[]');
                                i.prop('disabled', false);
                                i.removeAttr('id');
                                i.val(vv.id);
                                $('#<?= $this->e($id) ?>_inputs').append(i);
                                $('#<?= $this->e($field->getAttr("id")) ?>').prop('disabled', true);
                                i.change();
                                if (!$('#table_<?= $this->e($id) ?> tbody').length) {
                                    $('#table_<?= $this->e($id) ?>').empty()
                                    .html(
                                        '<table class="ui basic selectable compact table"><thead>' +
                                        vv.head.html() +
                                        '</thead><tbody></tbody></table>'
                                    );
                                    $('#table_<?= $this->e($id) ?>').find('thead .button').remove();
                                    $('#table_<?= $this->e($id) ?>').find('th')
                                        .each(function () { $(this).text($(this).find('a').text()); });
                                }
                                $('#table_<?= $this->e($id) ?> tbody').append(vv.html.clone());
                                $('#table_<?= $this->e($id) ?> tbody')
                                    .find('td:last-child')
                                        .empty()
                                        .append(
                                            '<a href="#" class="ui mini red labeled icon button row-pick">'+
                                            '<i class="ui remove icon"></i> '+
                                            '<?= $this->e($intl('fields.module.remove')) ?></a>'
                                        )
                                        .end().find('.quick-filter').remove();
                                if (vv.hide) {
                                    $('#modal_<?= $this->e($id) ?>').modal('hide');
                                }
                            }
                        });
                    } else {
                        $('#modal_<?= $this->e($id) ?>').modal('hide');
                    }
                    var val = !multiple ?
                        [$('#<?= $this->e($id) ?>').val()] :
                        $('#<?= $this->e($id) ?>_inputs input')
                            .toArray().map(function (v) { return v.value; }).filter(function (v) { return v !== ''; });
                    if (val && val.length) {
                        iframe.$('.table-read > tbody > tr').each(function () {
                            if (val.indexOf(iframe.$(this).data('id').toString()) !== -1) {
                                $(this).addClass('positive').find('td').eq(-1).empty();
                            }
                        });
                    }
                })
                .attr('src', "<?= $this->e($url($options['url'])) ?>")
                .end()
            .modal('show');
    });
    <?php if ($field->getOption('createUrl')) : ?>
    $('#button_add_<?= $this->e($id) ?>').click(function (e) {
        e.preventDefault();
        $('#modal_<?= $this->e($id) ?>')
            .html('<iframe src="" width="100%" height="80vh" class="module-field-iframe" ></iframe>')
            .find('iframe')
                .off('load')
                .on('load', function () {
                    var iframe = this.contentWindow;
                    if (iframe.selectedPromise) {
                        iframe.selectedPromise.then(function (vv) {
                            if (!multiple) {
                                $('#<?= $this->e($id) ?>').val(vv.id);
                                $('#table_<?= $this->e($id) ?>')
                                    .html(
                                        '<table class="ui basic selectable compact table"><thead>' +
                                        vv.head.html() +
                                        '</thead><tbody></tbody></table>'
                                    );
                                $('#table_<?= $this->e($id) ?>').find('thead .button').remove();
                                $('#table_<?= $this->e($id) ?>').find('th')
                                    .each(function () { $(this).text($(this).find('a').text()); });
                                $('#table_<?= $this->e($id) ?> tbody').append(vv.html.clone());
                                $('#table_<?= $this->e($id) ?> tbody')
                                    .find('td:last-child')
                                        .empty()
                                        .append(
                                            '<a href="#" class="ui mini red labeled icon button row-pick">'+
                                            '<i class="ui remove icon"></i> '+
                                            '<?= $this->e($intl('fields.module.remove')) ?>'+
                                            '</a>'
                                        )
                                        .end();
                                $('#modal_<?= $this->e($id) ?>').modal('hide');
                            } else {
                                var i = $('#<?= $this->e($id) ?>').clone();
                                i.attr('name', i.attr('name') + '[]');
                                i.prop('disabled', false);
                                i.removeAttr('id');
                                i.val(vv.id);
                                $('#<?= $this->e($id) ?>_inputs').append(i);
                                $('#<?= $this->e($field->getAttr("id")) ?>').prop('disabled', true);
                                if (!$('#table_<?= $this->e($id) ?> tbody').length) {
                                    $('#table_<?= $this->e($id) ?>').empty()
                                        .html(
                                            '<table class="ui basic selectable compact table"><thead>'+
                                            vv.head.html()+
                                            '</thead><tbody></tbody></table>'
                                        );
                                    $('#table_<?= $this->e($id) ?>').find('thead .button').remove();
                                    $('#table_<?= $this->e($id) ?>').find('th')
                                        .each(function () { $(this).text($(this).find('a').text()); });
                                }
                                $('#table_<?= $this->e($id) ?> tbody').append(vv.html.clone());
                                $('#table_<?= $this->e($id) ?> tbody')
                                    .find('td:last-child')
                                        .empty()
                                        .append(
                                            '<a href="#" class="ui mini red labeled icon button row-pick">'+
                                            '<i class="ui remove icon"></i> '+
                                            '<?= $this->e($intl('fields.module.remove')) ?>'+
                                            '</a>'
                                        )
                                        .end();
                                $('#modal_<?= $this->e($id) ?>').modal('hide');
                            }
                        });
                    } else {
                        $('#modal_<?= $this->e($id) ?>').modal('hide');
                    }
                    var val = !multiple ?
                        [$('#<?= $this->e($id) ?>').val()] :
                        $('#<?= $this->e($id) ?>_inputs input')
                            .toArray().map(function (v) { return v.value; }).filter(function (v) { return v !== ''; });
                    if (val && val.length) {
                        iframe.$('.table-read > tbody > tr').each(function () {
                            if (val.indexOf(iframe.$(this).data('id').toString()) !== -1) {
                                $(this).addClass('positive').find('td').eq(-1).empty();
                            }
                        });
                    }
                })
                .attr('src', "<?= $this->e($url($options['createUrl'])) ?>")
                .end()
            .modal('show');
    });
    <?php endif ?>
}());
</script>
