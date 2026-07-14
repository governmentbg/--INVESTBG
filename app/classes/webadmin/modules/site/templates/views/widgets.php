<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \webadmin\components\html\Field $field
 * @var string $cspNonce
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'date_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$widgets = $field->getOption('widgets');
?>
<h4 class="ui dividing header"><?= $this->e($intl($field->getOption('label'))) ?></h4>
<div id="<?= $this->e($field->getAttr('id')) ?>">
    <textarea name="<?= $this->e($field->getName()) ?>" class="widgets-textarea">
    <?= $this->e($field->getValue('')) ?>
    </textarea>
    <div class="tree-widgets"><?= $field->getOption('form') ?></div>
    <h4 class="ui horizontal grey divider header"><?= $this->e($intl('pages.addwidget')) ?></h4>
    <div class="ui center aligned basic segment">
        <div class="ui action input">
            <div class="ui left attached selection dropdown widgets-dropdown">
                <input name="widget-chooser" type="hidden" value="<?= $this->e(array_values($widgets)[0]) ?>">
                <i class="dropdown icon"></i>
                <div class="text"><?= $this->e($intl(array_values($widgets)[0])) ?></div>
                <div class="menu">
                    <?php foreach ($widgets as $widget) : ?>
                        <div class="item" data-value="<?= $this->e($widget) ?>">
                            <?= $this->e($intl($widget)) ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
            <button class="right attached ui green icon button add-widget"><i class="plus icon"></i></button>
        </div>
    </div>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
#<?= $this->e($field->getAttr('id')) ?> .widgets-textarea { display: none; }
#<?= $this->e($field->getAttr('id')) ?> .action.input { width:auto !important; }
#<?= $this->e($field->getAttr('id')) ?> .tree-widgets > div { 
    border:1px solid #ebebeb; margin-top:1rem; padding:1rem 1rem 1rem 1rem;
}
#<?= $this->e($field->getAttr('id')) ?> .tree-widgets > div > .grid { clear:right; }
#<?= $this->e($field->getAttr('id')) ?> .tree-widgets > div:first-child > .widget-up { display:none; }
#<?= $this->e($field->getAttr('id')) ?> .tree-widgets > div:last-child > .widget-down { display:none; }
#<?= $this->e($field->getAttr('id')) ?> .tree-widgets > div .widget-toggle { margin-left:1rem !important; }
#<?= $this->e($field->getAttr('id')) ?> .widgets-dropdown { min-width:300px !important; }
#<?= $this->e($field->getAttr('id')) ?> .tree-widgets > div > .widget-title {
    display:block; line-height:1.8rem; color:#bebebe;
}
#<?= $this->e($field->getAttr('id')) ?> .tree-widgets > .widget_main {
    background: #fbfdef !important; border-color:#8abc1e; color:#8abc1e;
}
#<?= $this->e($field->getAttr('id')) ?> .tree-widgets > .widget_main h4 {
    text-align:center; border-bottom:0 !important; color:#8abc1e !important;
}
#<?= $this->e($field->getAttr('id')) ?> .tree-widgets > .widget_main > .widget-remove { display:none !important; }
#<?= $this->e($field->getAttr('id')) ?> .tree-widgets > .widget_main > .widget-title { display:none !important; }
#<?= $this->e($field->getAttr('id')) ?> .tree-widgets > .widget_main > .widget-zone { display:none !important; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
(function () {
    $('#<?= $this->e($field->getAttr('id')) ?> .widgets-dropdown').dropdown();
    $("#<?= $this->e($field->getAttr('id')) ?>").closest('form').data('serialize', '1').attr('data-serialize', '1');
    var serialize = function () {
        var rslt = {};
        $("#<?= $this->e($field->getAttr('id')) ?>").children('.tree-widgets')
            .find('[data-serialize]').each(function () {
                var tmp = {};
                var elm = $(this);
                $(this).find(':input')
                    .filter(function (i, v) {
                        var row = $(v).closest('.json-form-row');
                        if (!row || !row.closest(elm).length) {
                            return true;
                        }
                        return false;
                    })
                    .serializeArray()
                    .forEach(function (v) {
                        if (v.name.indexOf('[]') !== -1) {
                            if (!tmp[v.name.replace('[]', '')]) {
                                tmp[v.name.replace('[]', '')] = [];
                            }
                            if (v.value) {
                                tmp[v.name.replace('[]', '')].push(v.value);
                            }
                        } else {
                            tmp[v.name] = v.value;
                        }
                    });
                rslt[$(this).data('serialize')] = tmp;
            });
        $("#<?= $this->e($field->getAttr('id')) ?>").children('textarea').val(JSON.stringify(rslt));
        return rslt;
    };
    $("#<?= $this->e($field->getAttr('id')) ?>")
        .closest('form')
            .on('submit', function () {
                serialize();
            })
            .on('redrawn', function () {
                $('#<?= $this->e($field->getAttr('id')) ?> .widgets-dropdown').dropdown();
                $('#<?= $this->e($field->getAttr('id')) ?> .tree-widgets')
                    .find('.widget-zone').each(function () {
                        $(this).dropdown({
                            onChange: function(value, text, $selectedItem) {
                                $(this).parent().find('[name=__zone]').val(value);
                            }
                        });
                    });
            })
            .find('.add-widget')
                .on('click', function (e) {
                    e.preventDefault();
                    $('#<?= $this->e($field->getAttr('id')) ?> .tree-widgets').append(
                        '<div data-serialize="'+$('[name="widget-chooser"]').val()+'__'+(Date.now())+'">' +
                        '<input type="hidden" name="new_widget_dummy" data-redraw="1" />' +
                        '</div>'
                    );
                    $("#<?= $this->e($field->getAttr('id')) ?>").find('[name=new_widget_dummy]').trigger('change');
                });
    $('#<?= $this->e($field->getAttr('id')) ?> .tree-widgets')
        .find('.widget-zone').each(function () {
            $(this).dropdown({
                onChange: function(value, text, $selectedItem) {
                    $(this).parent().find('[name=__zone]').val(value);
                }
            });
        });
    $('#<?= $this->e($field->getAttr('id')) ?> .tree-widgets')
        .on('click', '.widget-remove', function (e) {
            e.preventDefault();
            $(this).closest('div').remove();
        })
        .on('click', '.widget-up', function (e) {
            e.preventDefault();
            var r = $(this).closest('div');
            r.prev().before(r);
        })
        .on('click', '.widget-down', function (e) {
            e.preventDefault();
            var r = $(this).closest('div');
            r.next().after(r);
        })
        .on('click', '.widget-toggle', function (e) {
            e.preventDefault();
            $(this).toggleClass('teal orange').children().toggleClass('slash');
            $(this).parent().find('[name=__hidden]').val($(this).hasClass('orange') ? '1' : '0');
        });
}());
</script>
