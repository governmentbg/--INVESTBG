<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var \webadmin\modules\VisualModuleInterface $module
 * @var array $roots
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<h3 class="ui left floated yellow header">
    <i class="<?= $this->e($module->getIcon()) ?> icon"></i>
    <span class="content"><?= $this->e($intl($url->getSegment(0, 'dashboard') . '.title')) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment tree-struct">
    <button title="<?= $this->e($intl('organization.actions.add')) ?>"
        class="ui green icon button tree-create">
        <i class="plus icon"></i></button>
    <button title="<?= $this->e($intl('organization.actions.rename')) ?>"
        class="ui orange icon button tree-rename">
        <i class="pencil icon"></i></button>
    <button title="<?= $this->e($intl('organization.actions.details')) ?>"
        class="ui teal icon button tree-details">
        <i class="file icon"></i></button>
    <button title="<?= $this->e($intl('organization.actions.delete')) ?>"
        class="ui red icon button tree-remove">
        <i class="remove icon"></i></button>
    <select name="root" id="root"
        class="ui search dropdown <?= count($roots) < 2 ? 'search-hidden' : '' ?>">
        <?php foreach ($roots as $id => $name) : ?>
            <option value="<?= $this->e((string)$id) ?>"><?= $this->e($name) ?></option>
        <?php endforeach ?>
    </select>
    <div class="ui fluid icon input">
      <input placeholder="<?= $this->e($intl('common.search')) ?>" type="text" name="tree-search" />
      <i class="search icon"></i>
    </div>
    <div class="ui divider"></div>
    <div class="pages-tree"></div>
</div>

<div class="ui modal" id="error_modal">
    <i class="close icon"></i>
    <div class="ui form">
        <h3 class="ui red dividing header"><?= $this->e($intl('organization.notempty')) ?></h3>
        <table class="ui basic striped table">
            <thead>
                <tr>
                    <th><?= $this->e($intl('organization.user')) ?></th>
                    <th><?= $this->e($intl('organization.node')) ?></th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<div class="ui modal" id="details_modal">
    <i class="close icon"></i>
    <div class="ui inverted dimmer">
        <div class="content">
            <div class="center">
                <div class="ui text loader dimmer-message dimmer-message-load">
                    <?= $this->e($intl('common.pleasewait')) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="ui form">
        <div class="fields-a"></div>
        <div class="ui section divider"></div>
        <div class="ui center aligned orange secondary segment">
            <button class="ui orange icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
        </div>
    </div>
</div>

<style nonce="<?= $this->e($cspNonce) ?>">
.tree-struct { margin-top:0 !important; ; }
.tree-struct .input { margin-top:1rem !important; ; clear:right !important; ; }
.tree-remove { margin-right:3rem !important; ; }
#error_modal .form { padding:2rem  !important; ; }
#details_modal .form { padding:2rem  !important; ; }
.tree-struct .jstree-anchor { padding-right:1rem; }
.search-hidden { display:none !important; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$(function () {
    if ($('#root').is(':visible')) {
        $('#root').dropdown();
        $('#root').change(function () {
            $('.pages-tree').jstree(true).refresh(false, true);
        });
    }
    $('.tree-create, .tree-rename, .tree-remove, .tree-details').prop('disabled', true);
    $('.pages-tree')
        .jstree({
            'core' : {
                'data' : {
                    'url' : "<?= $url('organization/node') ?>",
                    'data' : function (node) {
                        return { 'id' : node.id, 'root' : $('#root').val() };
                    },
                    'dataType' : 'json',
                    'type' : 'POST'
                },
                'animation' : 0,
                'strings' : {
                    'Loading ...' : '<?= $this->e($intl("organization.texts.loading")) ?>'
                },
                'check_callback' : function (op, node, par, pos, more) {
                    if ((par === '#' || par.id === '#') &&
                        (op === 'create_node' || op === 'move_node' || op === 'copy_node')
                    ) {
                        return false;
                    }
                    return true;
                },
                'themes' : {
                    'variant' : 'large',
                    'stripes' : true
                },
                'worker' : false,
                'multiple' : false
            },
            'state' : { 'key' : 'organization' },
            'dnd' : {
                "is_draggable" : function (nodes) {
                    for (var i = 0, j = nodes.length; i < j; i++) {
                        if (nodes[i].parent === '#' || nodes[i].parent.id === '#') {
                            return false;
                        }
                    }
                    return true;
                }
            },
            'massload' : {
                'url' : "<?= $url('organization/nodes') ?>",
                'data' : function (ids) {
                    return { 'id' : ids.join(','), 'root' : $('#root').val() };
                },
                'dataType' : 'json'
            },
            'search' : {
                'show_only_matches' : true,
                'ajax' : {
                    'url' : "<?= $url('organization/search') ?>",
                    'data' : { },
                    'type' : 'GET'
                }
            },
            'plugins' : [ 'dnd', 'massload', '-wholerow', 'search', 'state' ]
        })
        .on('ready.jstree', function (e, data) {
            //data.instance.open_all();
        })
        .on('delete_node.jstree', function (e, data) {
            data.instance.deselect_all();
            data.instance.select_node(data.parent);
            $.post("<?= $url('organization/remove') ?>", { 'id' : data.node.id, 'root' : $('#root').val() })
                .fail(function (xhr, status, err) {
                    data.instance.refresh();
                    if (xhr.status === 400) {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp) {
                            var tbody = $('#error_modal tbody').empty();
                            resp.forEach(function (v) {
                                var row = $('<tr><td></td><td></td></tr>');
                                row.find('td').eq(0).text(v.name).end().eq(1).text(v.title);
                                tbody.append(row);
                            });
                            $('#error_modal').modal('show');
                        }
                    }
                });
        })
        .on('create_node.jstree', function (e, data) {
            $.post(
                "<?= $url('organization/create') ?>", 
                {
                    'id' : data.node.parent,
                    'position' : data.position,
                    'title' : data.node.text,
                    'root' : $('#root').val()
                })
                .done(function (d) {
                    data.instance.set_id(data.node, d.id);
                })
                .fail(function () {
                    data.instance.refresh();
                });
        })
        .on('rename_node.jstree', function (e, data) {
            data.instance.deselect_all();
            data.instance.select_node(data.node);
            $.post(
                "<?= $url('organization/rename') ?>",
                {
                    'id' : data.node.id,
                    'title' : data.text,
                    'root' : $('#root').val()
                })
                .done(function () {
                    data.instance.deselect_all();
                    data.instance.select_node(data.node);
                })
                .fail(function () {
                    data.instance.refresh();
                });
        })
        .on('move_node.jstree', function (e, data) {
            if (confirm("<?= $this->e($intl('organization.texts.confirmmove')) ?>")) {
                $.post(
                    "<?= $url('organization/move') ?>",
                    {
                        'id' : data.node.id,
                        'parent' : data.parent,
                        'position' : data.position,
                        'root' : $('#root').val()
                    })
                    .fail(function () {
                        data.instance.refresh();
                    });
            } else {
                data.instance.refresh();
            }
        })
        .on('copy_node.jstree', function (e, data) {
            if (confirm("<?= $this->e($intl('organization.texts.confirmcopy')) ?>")) {
                $.post(
                    "<?= $url('organization/copy') ?>",
                    {
                        'id' : data.original.id,
                        'parent' : data.parent,
                        'position' : data.position,
                        'root' : $('#root').val()
                    })
                    .always(function () {
                        data.instance.refresh();
                    });
            } else {
                data.instance.refresh();
            }
        })
        .on('changed.jstree', function (e, data) {
            if (!data.selected || !data.selected.length) {
                return $('.tree-create, .tree-rename, .tree-remove, .tree-details').prop('disabled', true);
            }
            $('.tree-create, .tree-rename, .tree-remove, .tree-details').prop('disabled', false);
            if (data.instance.get_node(data.selected[0]).parent === '#' ||
                data.instance.get_node(data.selected[0]).parent.id === '#'
            ) {
                $('.tree-remove').prop('disabled', true);
            }
        });

    // create button
    $('.tree-create').on('click', function(e) {
        e.preventDefault();
        var ref = $('.pages-tree').jstree(true),
            sel = ref.get_selected();
        if (sel.length === 1 || !$(this).attr('disabled')) {
            sel = ref.create_node(sel[0], { icon : 'ui cube icon' });
            if (sel) {
                ref.edit(sel, false, function () {
                    ref.activate_node(sel);
                });
            }
        }
    });
    // rename button
    $('.tree-rename').on('click', function(e) {
        e.preventDefault();
        var ref = $('.pages-tree').jstree(true),
            sel = ref.get_selected();
        if (sel.length === 1 && !$(this).attr('disabled')) {
            ref.edit(sel[0]);
        }
    });
    // remove button
    $('.tree-remove').on('click', function(e) {
        e.preventDefault();
        var ref = $('.pages-tree').jstree(true),
            sel = ref.get_selected();
        if (sel.length && !$(this).attr('disabled') && confirm("<?= $this->e($intl('pages.texts.confirmdelete')) ?>")) {
            ref.delete_node(sel);
        }
    });
    function serialize(elm) {
        var rslt = {};
        elm.find(':input').serializeArray().forEach(function (v) {
            if (v.name.indexOf('[]') !== -1) {
                if (!rslt[v.name.replace('[]', '')]) {
                    rslt[v.name.replace('[]', '')] = [];
                }
                if (v.value) {
                    rslt[v.name.replace('[]', '')].push(v.value);
                }
            } else {
                rslt[v.name] = v.value;
            }
        });
        return rslt;
    }
    $('#details_modal .submit.button')
        .on('click', function (e) {
            e.preventDefault();
            var ref = $('.pages-tree').jstree(true),
                sel = ref.get_selected();
            if (sel.length === 1) {
                $('#details_modal').find('.dimmer').dimmer('show');
                $.post('<?= $url('organization/form') ?>?org=' + sel[0], serialize($('#details_modal')))
                    .always(function () {
                        $('#details_modal').modal('hide');
                    });
            }
        });
    $('.tree-details')
        .on('click', function(e) {
            e.preventDefault();
            var ref = $('.pages-tree').jstree(true),
                sel = ref.get_selected();
            if (sel.length === 1 && !$(this).attr('disabled')) {
                $('#details_modal')
                    .find('.fields-a').html('').end()
                    .find('.dimmer').dimmer('show').end()
                    .modal('show')
                $.get('<?= $url('organization/form') ?>', { org : sel[0] })
                    .done(function (data) {
                        $('#details_modal')
                            .find('.fields-a').html(data).end()
                            .find('.dimmer').dimmer('hide');
                    })
                    .fail(function () {
                        $('#details_modal').modal('hide');
                    })
            }
        });
    // tree menu search
    var to = null,
        last = null;
    $('[name="tree-search"]').keyup(function (e) {
        if (to) {
            clearTimeout(to);
        }
        to = setTimeout(function () {
            var v = $('[name="tree-search"]').val();
            if (last !== v) {
                $('.pages-tree').jstree(true).search(v);
                last = v;
            }
        }, 500);
    });
});
</script>
