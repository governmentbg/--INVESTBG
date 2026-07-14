<?php

/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 */
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
<?php
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'json_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
if (!is_null($field->getForm()) && $field->getForm()->getContext('type') == 'read') {
    $disabled = true;
}
/**
 * @var \webadmin\components\html\Form
 */
$form = $field->getOption('form');
$blank = clone $form;
if ($disabled) {
    $form->disable();
}
$value = $field->getValue('');
if (!is_array($value)) {
    if (!($value = json_decode($value, true)) || !is_array($value)) {
        $value = [];
    }
}
$config = array_merge([
    'min'       => null,
    'max'       => null,
    'new'       => 15
], $field->getOptions());
unset($config['form']);
$value = array_values($value);
if ((int)$config['max']) {
    $value = array_slice($value, 0, $config['max']);
}

$labels = [];
$layout = [];
if ($form->hasLayout()) {
    if (count($form->getLayoutArray()) > 1) {
        foreach ($form->getLayoutArray() as $key => $row) {
            if (is_array($row)) {
                foreach ($row as $item) {
                    $item = preg_split('(\:(?=\d+$))', $item) ?: [];
                    $name = $item[0];
                    $parts = explode('[', $name, 2);
                    $nname = $field->getName() . '[99999999]' . '[' . $parts[0] . ']' .
                        (isset($parts[1]) ? '[' . $parts[1] : '');
                    $text = $form->hasField($name) ? $form->getField($name)->getOption('label', '') : $name;
                    if (count($item) === 2) {
                        if ($form->hasField($name)) {
                            $layout[$key][] = $nname . ':' . $item[1];
                            $form->getField($name)->setName($nname);
                        } else {
                            $layout[$key][] = $intl($text) . ':' . $item[1];
                        }
                    } else {
                        if ($form->hasField($name)) {
                            $layout[$key][] = $nname;
                            $form->getField($name)->setName($nname);
                        } else {
                            $layout[$key][] = $text;
                        }
                    }
                }
            } else {
                $layout[$key] = $row;
            }
        }
    } else {
        foreach ($form->getLayoutArray()[0] as $item) {
            $item = preg_split('(\:(?=\d+$))', $item) ?: [];
            $name = $item[0];
            $parts = explode('[', $name, 2);
            $nname = $field->getName() . '[99999999]' . '[' . $parts[0] . ']' .
                (isset($parts[1]) ? '[' . $parts[1] : '');
            $text = $form->hasField($name) ? $form->getField($name)->getOption('label', '') : $name;
            if (count($item) === 2) {
                $labels[] = $intl($text) . ':' . $item[1];
                if ($form->hasField($name)) {
                    $layout[] = $nname . ':' . $item[1];
                    $form->getField($name)->setName($nname);
                } else {
                    $layout[] = $intl($text) . ':' . $item[1];
                }
            } else {
                $labels[] = $intl($text);
                if ($form->hasField($name)) {
                    $layout[] = $nname;
                    $form->getField($name)->setName($nname);
                } else {
                    $layout[] = $text;
                }
            }
        }
        $layout = [$layout];
    }
} else {
    foreach ($form->getFields() as $f) {
        if ($f->getType() !== 'hidden') {
            $labels[] = $intl($f->getOption('label', ''));
        }
        $parts = explode('[', $f->getName(), 2);
        $nname = $field->getName() . '[99999999]' . '[' . $parts[0] . ']' . (isset($parts[1]) ? '[' . $parts[1] : '');
        $f->setName($nname);
        $layout[] = $nname;
    }
    $layout = [$layout];
}
$form->setLayout($layout);

$create = $field->getOption('create', null);
$clayout = $layout;
if ($create) {
    $clayout = [];
    if ($create->hasLayout()) {
        if (count($create->getLayoutArray()) > 1) {
            foreach ($create->getLayoutArray() as $key => $row) {
                if (is_array($row)) {
                    foreach ($row as $item) {
                        $item = preg_split('(\:(?=\d+$))', $item) ?: [];
                        $name = $item[0];
                        $parts = explode('[', $name, 2);
                        $nname = $field->getName() . '[99999999]' . '[' . $parts[0] . ']' .
                            (isset($parts[1]) ? '[' . $parts[1] : '');
                        $text = $create->hasField($name) ? $create->getField($name)->getOption('label', '') : $name;
                        if (count($item) === 2) {
                            if ($create->hasField($name)) {
                                $clayout[$key][] = $nname . ':' . $item[1];
                                $create->getField($name)->setName($nname);
                            } else {
                                $clayout[$key][] = $intl($text) . ':' . $item[1];
                            }
                        } else {
                            if ($create->hasField($name)) {
                                $clayout[$key][] = $nname;
                                $create->getField($name)->setName($nname);
                            } else {
                                $clayout[$key][] = $text;
                            }
                        }
                    }
                } else {
                    $clayout[$key] = $row;
                }
            }
        } else {
            foreach ($create->getLayoutArray()[0] as $item) {
                $item = preg_split('(\:(?=\d+$))', $item) ?: [];
                $name = $item[0];
                $parts = explode('[', $name, 2);
                $nname = $field->getName() . '[99999999]' . '[' . $parts[0] . ']' .
                    (isset($parts[1]) ? '[' . $parts[1] : '');
                $text = $create->hasField($name) ? $create->getField($name)->getOption('label', '') : $name;
                if (count($item) === 2) {
                    if ($create->hasField($name)) {
                        $clayout[] = $nname . ':' . $item[1];
                        $create->getField($name)->setName($nname);
                    } else {
                        $clayout[] = $intl($text) . ':' . $item[1];
                    }
                } else {
                    if ($create->hasField($name)) {
                        $clayout[] = $nname;
                        $create->getField($name)->setName($nname);
                    } else {
                        $clayout[] = $text;
                    }
                }
            }
            $clayout = [$clayout];
        }
    } else {
        foreach ($create->getFields() as $f) {
            $parts = explode('[', $f->getName(), 2);
            $nname = $field->getName() . '[99999999]' . '[' . $parts[0] . ']' .
                (isset($parts[1]) ? '[' . $parts[1] : '');
            $f->setName($nname);
            $clayout[] = $nname;
        }
        $clayout = [$clayout];
    }
} else {
    $create = (clone $form)->enable();
}
$create->setLayout($clayout);
?>
<div class="ui json-form <?= $form->getAttr('class', '') ?> form" id="<?= $field->getAttr('id') ?>_form">
    <?php if (count($labels)) : ?>
        <div class="json-form-header">
            <?php
            if (!$disabled) {
                if ($field->getOption('reorder', false)) {
                    echo '<i class="ui vertical ellipsis icon json-reorder-row" ';
                    echo ' ></i>';
                }

                if ($field->getOption('delete', true) || $field->getOption('add', true)) {
                    echo '<button type="button" class="ui red icon button json-remove-row" ';
                    echo ' ><i class="remove icon"></i></button>';
                }
            }
            ?>
            <?= $this->insert(
                'webadmin::form',
                [
                    'form' => (new \webadmin\components\html\Form())
                        ->setAttr('class', $form->getAttr('class'))->setLayout([$labels])
                ]
            ) ?>
        </div>
    <?php endif ?>
    <?php if (!$disabled) : ?>
        <fieldset disabled="disabled" class="json-form-blanks hide">
            <?php
            for ($i = 0; $i < max((int)$config['max'], $config['new'] + (int)$config['min']); $i++) {
                $blank = (clone $create);
                foreach ($clayout as $key => $row) {
                    if (is_array($row)) {
                        foreach ($row as $kk => $vv) {
                            $clayout[$key][$kk] = preg_replace(
                                '(^' . preg_quote($field->getName()) . '\[n?\d+\])',
                                $field->getName() . '[n' . $i . ']',
                                $vv
                            );
                        }
                    }
                }
                foreach ($blank->getFields() as $f) {
                    $name = $f->getName();
                    $name = preg_replace(
                        '(^' . preg_quote($field->getName()) . '\[n?\d+\])',
                        $field->getName() . '[n' . $i . ']',
                        $name
                    );
                    $f->setName($name);
                }
                echo '<div class="json-form-row ' .
                    (count($labels) ? 'single-line' : 'ui raised segment') . '">';
                if ($field->getOption('reorder', false)) {
                    echo '<i class="ui vertical ellipsis icon json-reorder-row" ';
                    echo ' ></i>';
                }
                if ($field->getOption('delete', true)) {
                    echo '<button type="button" class="ui red icon button json-remove-row" ';
                    echo ' ><i class="remove icon"></i></button>';
                }

                echo $this->insert('webadmin::form', ['form' => $blank->setLayout($clayout)]);
                echo '</div>';
            }
            ?>
        </fieldset>
    <?php endif ?>
    <div class="json-form-rows <?= count($labels) ? 'no-labels' : ''; ?>">
        <?php
        foreach ($value as $k => $v) {
            foreach ($layout as $key => $row) {
                if (is_array($row)) {
                    foreach ($row as $kk => $vv) {
                        $layout[$key][$kk] = preg_replace(
                            '(^' . preg_quote($field->getName()) . '\[n?\d+\])',
                            $field->getName() . '[' . $k . ']',
                            $vv
                        );
                    }
                }
            }
            foreach ($form->getFields() as $f) {
                /**
                 * @var \webadmin\components\html\Field $f
                 */
                $name = $f->getName();
                $name = preg_replace(
                    '(^' . preg_quote($field->getName()) . '\[n?\d+\])',
                    $field->getName() . '[' . $k . ']',
                    $name
                ) ?? '';
                $part = explode(']', explode('][', $name, 2)[1], 2)[0];
                $f->setName($name)->setValue(((array)$v)[$part] ?? '');
            }
            echo '<div class="json-form-row ' .
                (count($labels) ? 'single-line' : 'ui raised segment') . '">';
            if (!$disabled) {
                if ($field->getOption('reorder', false)) {
                    echo '<i class="ui vertical ellipsis icon json-reorder-row" ';
                    echo ' ></i>';
                }

                if ($field->getOption('delete', true)) {
                    echo '<button type="button" class="ui red icon button json-remove-row">';
                    echo '<i class="remove icon"></i></button>';
                }
            }
            echo $this->insert('webadmin::form', ['form' => (clone $form)->setLayout($layout)]);
            echo '</div>';
        }
        ?>
    </div>
    <?php if (!$disabled && $field->getOption('add', true)) : ?>
        <div class="json-field-operations">
            <button type="button" class="ui green labeled icon button json-add-row">
                <i class="plus icon"></i> <?= $this->e($intl('json.field.add')) ?>
            </button>
        </div>
    <?php endif ?>
</div>
<input
    type="hidden"
    id="<?= $field->getAttr('id') ?>"
    name="<?= $this->e($field->getAttr('name')); ?>"
    data-json='<?= json_encode(
        $config,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT
    ); ?>'
    value='<?= json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
    disabled="disabled"
    <?= $disabled ? ' disabled="disabled" ' : '' ?> />
<style nonce="<?= $this->e($cspNonce) ?>">
    .json-form-row.dragged {
        box-shadow: 0 0 5px rgba(125, 125, 125, 0.75);
        position: relative;
    }

    .json-form .grid {
        margin: 0 !important;
    }

    .json-form-header {
        border-bottom: 1px solid silver;
        margin-bottom: 0px;
        font-weight: bold;
    }

    .json-form-rows .json-form-row.single-line:nth-child(2n+1) {
        background: #f0f0f0;
    }

    .json-form-rows.no-labels label {
        display: none !important;
    }

    .json-form-rows.no-labels .checkbox label {
        display: block !important;
    }

    .json-form .dropdown>.text {
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
    }

    .json-form .ui.selection.dropdown>.menu {
        width: auto;
    }

    .json-form .json-reorder-row {
        padding-left: 10px;
        margin-top: 1.7rem;
        float: left;
    }

    .json-form .raised .json-reorder-row {
        padding-left: 0px;
        margin-top: 7px;
        float: left;
    }

    .json-form .json-remove-row {
        margin-top: 1rem;
        margin-right: 10px;
        float: right;
    }

    .json-form .raised .json-remove-row {
        margin-top: 0;
        margin-right: 0;
        float: right;
    }

    .compact.json-form .json-form-row .json-reorder-row {
        margin-top: 1.3rem;
    }

    .compact.json-form .raised .json-reorder-row {
        padding-left: 0px;
        margin-top: 7px;
        float: left;
    }

    .compact.json-form .json-form-row .json-remove-row {
        margin-top: 0.6rem;
    }

    .compact.json-form .raised .json-remove-row {
        margin-top: 0;
        margin-right: 0;
        float: right;
    }

    .json-form .json-form-header .json-remove-row,
    .json-form .json-form-header .json-reorder-row {
        visibility: hidden;
        margin-top: 0;
        height: 1rem;
    }

    .json-form-row .dividing.header {
        margin-top: 0 !important;
    }
</style>
<?php if (!$disabled) : ?>
    <script nonce="<?= $this->e($cspNonce) ?>">
        $(function() {
            var elm = $('#<?= $this->e($field->getAttr("id")) ?>'),
                min = <?= isset($config['min']) ? (int)$config['min'] : 'null' ?>,
                max = <?= isset($config['max']) ? (int)$config['max'] : 'null' ?>;

            function collect() {
                var val = [];
                $('#<?= $this->e($field->getAttr("id")) ?>_form .json-form-rows .json-form-row').each(function() {
                    var tmp = {};
                    $(this).find(':input[name]').each(function() {
                        var name = $(this).attr('name').split('[');
                        if (name[0] !== "<?= $this->e($field->getAttr('name')); ?>") {
                            return true;
                        }
                        tmp[name[2].split(']')[0]] = $(this).val();
                    });
                    val.push(tmp);
                });
                $('#<?= $this->e($field->getAttr("id")) ?>').val(JSON.stringify(val));
            }

            function processMinMax() {
                if (min) {
                    var cur = $('#<?= $this->e($field->getAttr("id")) ?>_form .json-form-rows .json-form-row').length,
                        row;
                    while (cur < min) {
                        row = $('#<?= $this->e($field->getAttr("id")) ?>_form .json-form-blanks')
                            .children('.json-form-row').eq(0);
                        if (row) {
                            $('#<?= $this->e($field->getAttr("id")) ?>_form .json-form-rows').append(row);
                            if (!$('#<?= $this->e($field->getAttr("id")) ?>_form .json-form-blanks')
                                .children('.json-form-row').length
                            ) {
                                $(this).hide();
                            }
                        }
                        cur++;
                    }
                    if (max && cur >= max) {
                        $('#<?= $this->e($field->getAttr("id")) ?>_form .json-add-row').hide();
                    } else {
                        if ($('#<?= $this->e($field->getAttr("id")) ?>_form .json-form-blanks')
                            .children('.json-form-row').length
                        ) {
                            $('#<?= $this->e($field->getAttr("id")) ?>_form .json-add-row').show();
                        }
                    }
                }
            }
            $('#<?= $this->e($field->getAttr("id")) ?>_form')
                .on('click', '.json-remove-row', function(e) {
                    e.preventDefault();
                    $(this).closest('.json-form-row').remove();
                    processMinMax();
                    collect();
                })
                .on('click', '.json-add-row', function(e) {
                    e.preventDefault();
                    var row = $(this).parent().parent().find('.json-form-blanks').children('.json-form-row').eq(0);
                    if (row) {
                        $(this).parent().parent().find('.json-form-rows').append(row);
                        processMinMax();
                        collect();
                    }
                })
                .on('change', function() {
                    collect();
                })
                .closest('form').on('submit', function() {
                    collect();
                })
            processMinMax();

            // drag'n'drop
            var isdrg = 0,
                initx = false,
                inity = false,
                ofstx = false,
                ofsty = false,
                holdr = false,
                elmnt = false;
            container = $('#<?= $this->e($field->getAttr("id")) ?>_form')
            container
                .on('mousedown', '.ellipsis', function(e) {
                    elmnt = $(this).closest('.json-form-row');
                    try {
                        e.currentTarget.unselectable = "on";
                        e.currentTarget.onselectstart = function() {
                            return false;
                        };
                        if (e.currentTarget.style) {
                            e.currentTarget.style.MozUserSelect = "none";
                        }
                    } catch (err) {}
                    holdr = false;
                    initx = e.pageX;
                    inity = e.pageY;
                    elmnt = $(this).closest('.json-form-row');
                    var o = elmnt.offset();
                    ofstx = e.pageX - o.left;
                    ofsty = e.pageY - o.top;
                    isdrg = 1;
                    elmnt.addClass('dragged');
                });
            $('body')
                .on('mousemove', function(e) {
                    switch (isdrg) {
                        case 0:
                            return;
                        case 1:
                            if (Math.abs(e.pageX - initx) > 5 || Math.abs(e.pageY - inity)) {
                                isdrg = 2;
                            }
                            break;
                        case 2:
                            var targt = $(e.target).closest('.json-form-row'),
                                i, j;
                            if (targt.length &&
                                targt[0] !== elmnt[0] &&
                                targt.closest('#<?= $this->e($field->getAttr("id")) ?>_form').length
                            ) {
                                i = targt.index();
                                j = elmnt.index();
                                if (i != j) {
                                    targt[i > j ? 'after' : 'before'](elmnt);
                                }
                            }
                            break;
                    }
                })
                .on('mouseup', function() {
                    if (isdrg) {
                        if (isdrg == 2) {
                            collect();
                        }
                        elmnt.removeClass('dragged');
                        isdrg = 0;
                        initx = false;
                        inity = false;
                        elmnt = false;
                        holdr = false;
                    }
                });
        });
    </script>
<?php endif ?>

<?php
// $form->addField(
//     new Field(
//         'json',
//         [ 'name'=>'test', 'value' => [ [ 'hour' => '10:00', 'name' => 'test 1',
//                 'tags' => ['test','test'] ], [ 'hour' => '12:00', 'name' => 'test' ] ] ],
//         [
//             'label' => 'test',
//             'form' => (new Form())
//                 ->addField(new Field('text', ['name' => 'hour'], ['label'=>'test']))
//                 ->addField(new Field('text', ['name' => 'name'], ['label'=>'test']))
//                 ->addField(new Field('tags', ['name' => 'tags[]'], ['label'=>'test']))
//                 ->setLayout([['hour:1', 'name:4', 'tags[]:3']])
//         ]
//     )
// );
// $layout[] = ['test'];
?>