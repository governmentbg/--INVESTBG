<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Form $form
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 */
$keys = [
    1 => 'one','two','three','four','five','six','seven','eight','nine','ten',
    'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen'
];
$layout = $form->getLayoutArray(true);
$usedFields = [];

// force validator refresh in case of changes to the validator object directly
if ($form->hasValidator() && $form->getValidator()) {
    $form->setValidator($form->getValidator());
}

$tabs = [];
foreach ($layout as $row) {
    if (is_string($row) && strpos($row, 'tab:') === 0) {
        $row = explode(':', $row, 2);
        if (strlen($row[1])) {
            $tabs[] = $row[1];
        }
    }
}

$id = null;
if (count($tabs)) {
    $id = md5(microtime() . rand(0, 100));
    echo '<div class="ui pointing primary menu form-tabs" id="tabs_' . $id . '">';
    foreach ($tabs as $tab) {
        $disabled = false;
        if (strpos($tab, 'disabled:') === 0) {
            $disabled = true;
            $tab = explode(':', $tab, 2)[1];
        }
        echo '<a class="' . ($disabled ? 'disabled' : '') . ' item">' . $this->e($intl($tab)) . '</a>';
    }
    echo '</div>';
    echo '<div id="content_' . $id . '" class="form-content">';
}
$tabOpen = false;
$accOpen = false;
$gridOpen = false;
foreach ($layout as $row) {
    if (is_string($row)) {
        if (strpos($row, 'acc:') === 0) {
            $row = explode(':', $row, 2);
            if ($gridOpen) {
                echo '</div>';
            }
            if ($accOpen) {
                echo '</div>';
            }
            if (strlen($row[1])) {
                if (!$accOpen) {
                    echo '<div class="ui styled fluid accordion">';
                }
                $active = false;
                if (strpos($row[1], 'open:') === 0) {
                    $active = true;
                    $row[1] = explode(':', $row[1], 2)[1] ?? '';
                }
                echo '<div class="' . ($active ? 'active' : '') . ' title">';
                echo '<i class="dropdown icon"></i> ' . nl2br($this->e($intl($row[1])));
                echo '</div>';
                echo '<div class="' . ($active ? 'active' : '') . ' content accordion-content">';
                echo '<div class="ui ' . ($form->hasClass('compact') ? 'compact' : '' ) . ' grid">';
                $accOpen = true;
                $gridOpen = true;
            } else {
                echo '</div>';
                $accOpen = false;
                $gridOpen = false;
            }
            continue;
        }
        if (strpos($row, 'tab:') === 0) {
            $row = explode(':', $row, 2);
            if ($gridOpen) {
                echo '</div>';
            }
            if ($accOpen) {
                echo '</div>';
                echo '</div>';
                $accOpen = false;
            }
            if ($tabOpen) {
                echo '</div>';
            }
            if (strlen($row[1])) {
                echo '<div class="ui tab">';
                echo '<div class="ui ' . ($form->hasClass('compact') ? 'compact' : '' ) . ' grid">';
                $tabOpen = true;
                $gridOpen = true;
            } else {
                $tabOpen = false;
                $gridOpen = false;
            }
            continue;
        }
        if (!$gridOpen) {
            echo '<div class="ui ' . ($form->hasClass('compact') ? 'compact' : '' ) . ' grid">';
            $gridOpen = true;
        }
        if (!strlen($row)) {
            echo '<div class="one column row"><div class="column"><div class="ui divider"></div></div></div>';
        } else {
            echo '<div class="one column row"><div class="column"><h4 class="ui dividing header form-header">';
            echo nl2br($this->e($row !== '' ? $intl($row) : ''));
            echo '</h4></div></div>';
        }
        continue;
    }
    if (!$gridOpen) {
        echo '<div class="ui ' . ($form->hasClass('compact') ? 'compact' : '' ) . ' grid">';
        $gridOpen = true;
    }
    $width  = 0;
    $fields = [];
    $auto = true;
    foreach ($row as $k => $field) {
        $temp = array_pad(preg_split('(\:(?=\d+$))', $field) ?: [], 2, null);
        if (!isset($temp[1])) {
            $temp[1] = (
                $form->hasField($temp[0]) &&
                ($form->getField($temp[0])->getType() === 'hidden' || $form->getField($temp[0])->isHidden())
             ) ? 0 : 1;
        }
        $temp[1] = (int)$temp[1];
        $width += $temp[1];
        if ($temp[1] !== 1 && $temp[1] !== 0) {
            $auto = false;
        }
        if ($form->hasField($temp[0])) {
            $fields[] = [ 'type' => 'field', 'width' => $temp[1], 'field' => $form->getField($temp[0]) ];
        } else {
            $fields[] = [ 'type' => 'text',  'width' => $temp[1], 'text'  => $temp[0] ];
        }
    }
    $ratio = $width ? max(1, floor(16 / $width)) : 1;
    //echo '<div class="' . $this->e($keys[$width]) . ' column row">';
    echo '<div class="' . ($auto ? ($keys[$width] ?? 'hide') . ' column' : '') . ' row">';
    foreach ($fields as $field) {
        switch ($field['type']) {
            case 'text':
                echo '<div class="' . ($auto ? '' : $this->e($keys[$field['width'] * $ratio]) . ' wide') . ' column">';
                if (substr($field['text'], 0, 2) === 'b:') {
                    echo '<strong>' . nl2br($this->e(substr($field['text'], 2))) . '</strong>';
                } elseif (substr($field['text'], 0, 2) === 'i:') {
                    echo '<div class="ui teal message">' . nl2br($this->e(substr($field['text'], 2))) . '</div>';
                } elseif (substr($field['text'], 0, 2) === 'w:') {
                    echo '<div class="ui yellow message">' . nl2br($this->e(substr($field['text'], 2))) . '</div>';
                } elseif (substr($field['text'], 0, 2) === 'e:') {
                    echo '<div class="ui red message">' . nl2br($this->e(substr($field['text'], 2))) . '</div>';
                } else {
                    echo nl2br($this->e($field['text']));
                }
                echo '</div>';
                break;
            case 'field':
                $f = $field['field'];
                if ($f->isHidden()) {
                    $usedFields[] = $f;
                    break;
                }
                if (!$field['width']) {
                    echo '<div class="hide">';
                } else {
                    echo '<div class="';
                    echo ($auto ? '' : $this->e($keys[$field['width'] * $ratio]) . ' wide');
                    echo ' column">';
                }
                echo '<div class="';
                if ($f->hasAttr('disabled')) {
                    echo 'disabled ';
                }
                $required = false;
                if (
                    $f->hasAttr('data-validate') &&
                    !$f->hasAttr('disabled') &&
                    !$f->hasAttr('readonly')
                ) {
                    foreach ($f->getAttr('data-validate') ?? [] as $rule) {
                        if (
                            isset($rule['rule']) &&
                            $rule['rule'] === 'required' &&
                            !isset($rule['when'])
                        ) {
                            $required = true;
                            break;
                        }
                    }
                }
                if ($required) {
                    echo 'required ';
                }
                echo ' field" ';
                if ($f->hasAttr('data-validate')) {
                    echo ' data-validate=\'';
                    echo json_encode(
                        $f->getAttr('data-validate'),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT
                    );
                    echo '\' ';
                }
                echo '>';
                echo $this->insert(
                    'webadmin::field/' . $f->getType(),
                    [ 'field' => $f ]
                );
                echo '</div>';
                echo '</div>';
                $usedFields[] = $f;
                break;
        }
    }
    echo '</div>';
}
if ($gridOpen) {
    echo '</div>';
}
if ($accOpen) {
    echo '</div></div>';
}
if ($tabOpen) {
    echo '</div>';
}
if (count($usedFields) !== count($form->getFields())) {
    echo '<div class="hidden-fields hide">';
    foreach ($form->getFields() as $field) {
        if (!in_array($field, $usedFields) && !$field->isHidden()) {
            echo $this->insert(
                'webadmin::field/' . $field->getType(),
                [ 'field' => $field ]
            );
        }
    }
    echo '</div>';
}
if (count($tabs)) {
    echo '</div>';
}
?>

<?php if ($id) : ?>
<script nonce="<?= $this->e($cspNonce) ?>">
$('#tabs_<?= $this->e($id) ?>').on('click', '> .item', function (e) {
    var id = $(this).siblings().removeClass('active').end().addClass('active').index();
    $('#content_<?= $this->e($id) ?> > .tab').hide().eq(id).show();
}).find('.item').eq(0).click();
</script>
<?php endif ?>
