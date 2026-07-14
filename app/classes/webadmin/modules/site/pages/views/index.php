<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $widgetsForm
 * @var array<string,string> $languages
 * @var bool $structPermission
 * @var bool $widgetPermission
 * @var bool $changePermission
 * @var bool $publishPermission
 * @var array<array{template:int,name:string}> $templates
 * @var array<string> $widgets
 * @var \webadmin\components\html\Form $settings
 * @var \webadmin\components\html\Form $permissions
 * @var string $cspNonce
 * @var string $name
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>


<div id="pages-container">
<div class="ui segment tree-struct">
    <div class="ui selection dropdown languages-dropdown">
        <input name="pages-lang" type="hidden" value="<?= $this->e(key($languages) ?? '') ?>">
        <i class="dropdown icon"></i>
        <div class="text">
            <i class="<?= $this->e(array_values($languages)[0] == 'en' ? 'gb' : array_values($languages)[0]) ?> flag">
            </i>
        </div>
        <div class="menu">
            <?php foreach ($languages as $lang => $code) : ?>
                <div class="item" data-value="<?= $this->e($lang) ?>">
                    <i class="<?= $this->e($code == 'en' ? 'gb' : $code) ?> flag"></i>
                </div>
            <?php endforeach ?>
        </div>
    </div>
    <?php if ($structPermission) : ?>
        <button title="<?= $this->e($intl('pages.actions.add')) ?>" class="ui green icon button tree-create">
            <i class="plus icon"></i></button>
    <?php endif ?>
    <button title="<?= $this->e($intl('pages.actions.rename')) ?>" class="ui orange icon button tree-rename">
        <i class="pencil icon"></i></button>
    <?php if ($structPermission) : ?>
        <button title="<?= $this->e($intl('pages.actions.delete')) ?>" class="ui red icon button tree-remove">
            <i class="remove icon"></i></button>
    <?php endif ?>
    <button title="<?= $this->e($intl('pages.actions.visibility')) ?>" class="ui blue icon button tree-toggle">
        <i class="hide icon"></i></button>

    <div class="ui fluid icon input">
      <input placeholder="<?= $this->e($intl('common.search')) ?>" type="text" name="tree-search" />
      <i class="search icon"></i>
    </div>
    <div class="pages-tree"></div>
</div>
<div class="ui segment tree-data">
    <button class="ui green icon button save" 
        title="<?= $this->e($intl('common.save')) ?>">
        <i class="save icon"></i></button>
    <button class="ui teal icon button versions"
        title="<?= $this->e($intl('pages.actions.loadversion')) ?>">
        <i class="history icon"></i></button>
    <button class="ui olive icon button preview"
        title="<?= $this->e($intl('pages.actions.preview')) ?>">
        <i class="eye icon"></i></button>
    <div class="ui secondary menu">
        <a class="active item">
            <div class="ui floating dropdown template-dropdown">
                <i class="pencil icon"></i>
                <span class="text"></span>
                <div class="menu">
                    <div class="ui icon search input">
                    <i class="search icon"></i>
                    <input type="text" placeholder="">
                    </div>
                    <div class="divider"></div>
                    <div class="scrolling menu">
                        <?php foreach ($templates as $template) : ?>
                            <div class="item" data-value="<?= $this->e((string)$template['template']) ?>">
                                <?= $this->e($template['name']) ?>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        </a>
        <?php if ($widgetPermission && count($widgets)) : ?>
        <a class="item">
            <i class="puzzle icon"></i> <?= $this->e($intl('pages.tabs.widgets')) ?>
        </a>
        <?php endif ?>
        <a class="item">
            <i class="settings icon"></i> <?= $this->e($intl('pages.tabs.settings')) ?>
        </a>
        <?php if ($changePermission) : ?>
        <a class="item">
            <i class="lock icon"></i> <?= $this->e($intl('pages.tabs.permissions')) ?>
        </a>
        <?php endif ?>
    </div>
    <div class="ui inverted dimmer">
        <div class="content">
            <div class="center">
                <h2 class="ui header dimmer-message dimmer-message-permission">
                    <?= $this->e($intl('pages.texts.permission')) ?>
                </h2>
                <h2 class="ui header dimmer-message dimmer-message-no">
                    <?= $this->e($intl('pages.texts.choose')) ?>
                </h2>
                <h2 class="ui header dimmer-message dimmer-message-multi">
                    <?= $this->e($intl('pages.texts.manychosen')) ?>
                </h2>
                <div class="ui text loader dimmer-message dimmer-message-load">
                    <?= $this->e($intl('common.pleasewait')) ?>
                </div>
            </div>
        </div>
    </div>
    <form class="ui form validate-form tree-form" method="post"
        data-redraw="<?= $this->e($url($url->getSegment(0) . '/redraw')) ?>" data-serialize="true">
        <input type="hidden" name="id" value="" />
        <input type="hidden" name="lang" value="" />
        <input type="hidden" name="template" value="" />
        <input type="hidden" name="publish" value="0" />
        <input type="hidden" name="version" value="" />
        <div class="section page tree-content">
            <?php foreach ($templates as $template) : ?>
                <div
                    data-id="<?= $this->e((string)$template['template']) ?>"
                    data-serialize="<?= $this->e((string)$template['template']) ?>"
                    class="template template-<?= $this->e((string)$template['template']) ?>"
                >
                </div>
            <?php endforeach ?>
        </div>
        <?php if ($widgetPermission && count($widgets)) : ?>
        <div class="section page">
            <div class="tree-widgets">
                <?= $widgetsForm ?>
            </div>
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
        <?php endif ?>
        <div class="section page tree-settings" data-serialize="settings">
            <?= $this->insert('webadmin::form', [ 'form' => $settings ]) ?>
        </div>
        <?php if ($changePermission) : ?>
        <div class="section page tree-permissions" data-serialize="permissions">
            <?= $this->insert('webadmin::form', [ 'form' => $permissions ]) ?>
        </div>
        <?php endif ?>
    </form>
</div>
<div id="upload" data-plupload='{ "url" : "<?= $url('upload') ?>", "chunksize" : "250kb" }'></div>
</div>
<div class="ui modal" id="versions-modal">
    <i class="close icon"></i>
    <div class="ui form">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.pleasewait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="data scrolling content">
        </div>
    </div>
</div>

<div class="ui small modal" id="save-modal">
    <div class="header"><?= $this->e($intl('pages.titles.saveandpublish')) ?></div>
    <div class="content">
        <p><?= $this->e($intl('pages.texts.saveandpublish')) ?></p>
    </div>
    <div class="actions">
        <div class="ui negative button cancel"><?= $this->e($intl('common.cancel')) ?></div>
        <div class="ui positive button draft"><?= $this->e($intl('pages.actions.saveasdraft')) ?></div>
        <div class="ui positive right labeled icon button publish">
            <?= $this->e($intl('pages.actions.saveandpublish')) ?><i class="checkmark icon"></i>
        </div>
    </div>
</div>

<style nonce="<?= $this->e($cspNonce) ?>">
#pages-container { position:relative; }
#pages-container .tree-struct { position:fixed; width:400px; top:4.5rem; bottom:0; }
#pages-container .pages-tree { position:fixed; top:13rem; width:370px; bottom:2rem; overflow:auto }
#pages-container .tree-data { position:absolute; left:416px; top:0; bottom:0; margin-top:0; right:0rem; }
#pages-container .tree-data > button { float:right !important; }
#pages-container .tree-data > .menu { position:relative; margin-top:0; margin-right: 12rem; }
#pages-container .tree-data > .secondary.menu > .item { padding:0.8rem; }
#pages-container .tree-content > .template { display: none; }
#versions-modal > .form { padding:2rem; }
#pages-container .tree-struct .input { margin-top:1rem; clear:right; }
#pages-container .languages-dropdown { float:right; min-width:0; <?= count($languages) < 2 ? 'display:none;' : '' ?> }
.submenu { display:inline-block }
.tree-data > .form {
    position: absolute; right: 1rem; left: 1rem; bottom: 1rem; top: 5rem; overflow:auto; padding:0 1rem;
}
#pages-container .jstree-clicked,
#pages-container .jstree-hovered { box-shadow:none; }
.tree-struct .jstree-anchor { padding-right:1rem; }
.tree-content .one.column > .column { padding-left:0.3rem; padding-right:0.3rem; }
.tree-stale { color:red !important; }
.tree-hidden > .jstree-anchor { opacity:0.6; color:gray; }
.tree-hidden > .jstree-anchor > .jstree-icon { filter: gray; filter: grayscale(100%); }
#template { padding:0.5rem; margin-right:-8px; }
#template .item { margin:0; }
.published-version > td { background:green !important; color:white; }
#pages-container .jstree-default-large .jstree-icon:empty { line-height:28px; }
.tree-widgets > div { border:1px solid #ebebeb; margin-top:1rem; padding:1rem 1rem 1rem 1rem; }
.tree-widgets > div > .grid { clear:right; }
.tree-widgets > div:first-child > .widget-up { display:none; }
.tree-widgets > div:last-child > .widget-down { display:none; }
.tree-widgets > div .widget-toggle { margin-left:1rem !important; }
.widgets-dropdown { min-width:300px !important; }
.tree-widgets > div > .widget-title { display:block; line-height:1.8rem; color:#bebebe; }
.tree-widgets > .widget_main { background: #fbfdef !important; border-color:#8abc1e; color:#8abc1e; }
.tree-widgets > .widget_main h4 { text-align:center; border-bottom:0 !important; color:#8abc1e !important;}
.tree-widgets > .widget_main > .widget-remove { display:none !important; }
.tree-widgets > .widget_main > .widget-title { display:none !important; }
.tree-widgets > .widget_main > .widget-zone { display:none !important; }
.tree-form > .message { margin-left: -0.7rem; margin-right: -0.7rem; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$(function () {
    var skipredraw = false;
    setTimeout(function () {
        $('.tree-data .dimmer').find('.dimmer-message').hide()
            .filter('.dimmer-message-no').show().end().end().dimmer('show');

        // UI resizing
        $(window).on('resize', function () {
            $('.tree-data').outerHeight($('.tree-struct').outerHeight());
        }).resize();

        //$('.tree-data .submenu')
        //   .popup({ inline: true, on: 'click', position: 'bottom right', 'popup' : $('#template') });
        $('#template').on('click', '.item', function (e) {
            $('.submenu > span').text($(this).text());
            $('.tree-data .form [name=template]').val($(this).data("id"));
            $('.tree-data .submenu').popup('hide');
            $('.tree-content .template').hide().filter('.template-' + $(this).data("id")).show();
        });

        // Content tabs
        $('.tree-data .secondary.menu > .item').click(function (e) {
            var tab = $(this)
                .closest('.tree-data').find('.section.page').hide()
                .eq($(this).siblings().removeClass('active').end().addClass('active').index())
                    .show();
            if (tab.is('.tree-content, .tree-draft')) {
                //tab.children('div').eq(0).focus();
            }
        }).eq(0).click();

        function serialize(elm) {
            var rslt = {};
            elm.find(':input')
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

        function serializeWidgets(elm) {
            var rslt = {};
            elm.find('[data-serialize]').each(function () {
                rslt[$(this).data('serialize')] = serialize($(this));
            });
            return rslt;
        }
        
        $('#versions-modal')
            .on('click', '.version-load', function (e) {
                $('#versions-modal').find('.dimmer').dimmer('show');
                $.get(
                    "<?= $url($name . '/version') ?>",
                    'id=' + $('.pages-tree').jstree(true).get_selected()[0] +
                    '&lang=' + $('.tree-struct').find('[name="pages-lang"]').val() +
                    '&version=' + $(this).data('version')
                ).done(function (d) {
                    $('#versions-modal').find('.dimmer').dimmer('hide');
                    $('#versions-modal').modal('hide');
                    //$('#template .item[data-id="'+(d.template)+'"]').click();
                    skipredraw = true;
                    $('.template-dropdown').dropdown('set exactly', d.template);
                    skipredraw = false;
                    $('.tree-widgets').html(d.html.widgets);
                    $('.widgets-dropdown').dropdown();
                    $('.tree-widgets')
                        .find('.widget-zone').each(function () {
                            $(this).dropdown({
                                onChange: function(value, text, $selectedItem) {
                                    $(this).parent().find('[name=__zone]').val(value);
                                }
                            });
                        });
                    $('.page.tree-settings').html(d.html.settings);
                    $('.page.tree-permissions').html(d.html.permissions);
                    $('.page.tree-content .template').each(function () {
                        $(this).html(d.html.templates[$(this).data('id')] || '');
                    });
                    //populate(d);
                });
            });

        $('#save-modal')
            .on('click', '.cancel', function (e) {
                $('#save-modal').modal('hide');
            })
            .on('click', '.draft', function (e) {
                $('.tree-data').find('[name=publish]').val(0).end().find('form').submit();
            })
            .on('click', '.publish', function (e) {
                $('.tree-data').find('[name=publish]').val(1).end().find('form').submit();
            });

        // Content tabs form handling
        var canPublish = false;
        $('.tree-data')
            .on('click', '.save', function (e) {
                <?php if ($publishPermission) : ?>
                    if (canPublish) {
                        $('#save-modal').modal('show');
                    } else {
                        $('.tree-data').find('[name=publish]').val(0).end().find('form').submit();
                    }
                <?php else : ?>
                    $('.tree-data').find('[name=publish]').val(0).end().find('form').submit();
                <?php endif ?>
            })
            .on('click', '.preview', function (e) {
                var form = $('.tree-data').find('form');
                form.children('.message').remove();
                $('.tree-data .dimmer')
                    .find('.dimmer-message')
                        .hide()
                        .filter('.dimmer-message-load')
                            .show()
                            .end()
                        .end()
                    .dimmer('show');
                form.find('textarea.richtext').not('.richtext-waiting').each(function () {
                    tinymce.get(this.id).save();
                });
                $.post(
                    "<?= $url($name . '/save') ?>",
                    'content=' + encodeURIComponent(
                        JSON.stringify(serialize($('.template-' + $('.tree-data .form [name=template]').val())))
                    ) +
                    '&widgets=' + encodeURIComponent(JSON.stringify(serializeWidgets($('.tree-widgets')))) +
                    '&settings=' + encodeURIComponent(JSON.stringify(serialize($('.tree-settings')))) +
                    '&permissions=' + encodeURIComponent(JSON.stringify(serialize($('.tree-permissions')))) +
                    '&id=' + $('.pages-tree').jstree(true).get_selected()[0] +
                    '&template=' + $('.tree-data').find('[name="template"]').val() +
                    '&publish=0' +
                    '&version=' + $('.tree-data').find('[name="version"]').val() +
                    '&lang=' + $('.tree-struct').find('[name="pages-lang"]').val() + 
                    '&preview=1'
                ).always(function () {
                    $('.tree-data .dimmer').dimmer('hide');
                    $('#save-modal').modal('hide');
                }).fail(function () {
                }).done(function () {
                    window.open(
                        "<?= $url($name . '/preview/') ?>" +
                        '/' +
                        $('.tree-struct').find('[name="pages-lang"]').val() + '/' +
                        $('.pages-tree').jstree(true).get_selected()[0]
                    );
                });
                form.data('editing', false).find('.btn').blur();
            })
            .on('click', '.versions', function (e) {
                $('#versions-modal').modal('show');
                $('#versions-modal').find('.dimmer').dimmer('show');
                $.get(
                    "<?= $url($name . '/versions') ?>",
                    'id=' + $('.pages-tree').jstree(true).get_selected()[0] +
                    '&lang=' + $('.tree-struct').find('[name="pages-lang"]').val()
                ).done(function (data) {
                    $('#versions-modal .data').empty();
                    var str = '<table class="ui basic striped compact table"><thead><tr>'+
                        '<th><?= $this->e($intl('pages.versions.number')) ?></th>'+
                        '<th><?= $this->e($intl('pages.versions.date')) ?></th>'+
                        '<th><?= $this->e($intl('pages.versions.author')) ?></th>'+
                        '<th></th></tr></thead><tbody>';
                    for (var i = 0; i < data.length; i++) {
                        str += '<tr class="'+(data[i].published==1?'published-version':'')+'">'+
                            '<td>'+data[i].version+'</td>'+
                            '<td>'+data[i].created+'</td>'+
                            '<td>'+data[i].name+'</td>'+
                            '<td>'+
                                '<button class="ui teal mini button version-load" data-version="'+data[i].version+'">'+
                                '<?= $this->e($intl('pages.versions.load')) ?>'+
                                '</button>'+
                                '<a href="<?= $url($name . '/preview') ?>/' +
                                    $('.tree-struct').find('[name="pages-lang"]').val() + '/' +
                                    $('.pages-tree').jstree(true).get_selected()[0]+'/'+
                                    data[i].version+'" '+
                                    'class="ui olive mini button version-preview" target="_blank">'+
                                '<?= $this->e($intl('pages.versions.preview')) ?></a></td></tr>';
                    }
                    str += '</tbody></table>';
                    $('#versions-modal .data').html(str);
                    $('#versions-modal').find('.dimmer').dimmer('hide');
                });
            })
            .on('change', function (e) {
                $(e.target).closest('form').data('editing', true);
            })
            .on('submit', function (e) {
                e.preventDefault();
                $(e.target).children('.message').remove();
                $('.tree-data .dimmer')
                    .find('.dimmer-message')
                        .hide()
                        .filter('.dimmer-message-load')
                            .show()
                            .end()
                        .end()
                    .dimmer('show');
                $(this).find('textarea.richtext').not('.richtext-waiting').each(function () {
                    tinymce.get(this.id).save();
                });
                $.post(
                    "<?= $url($name . '/save') ?>",
                    'content=' + encodeURIComponent(
                        JSON.stringify(serialize($('.template-' + $('.tree-data .form [name=template]').val())))
                    ) +
                    '&widgets=' + encodeURIComponent(JSON.stringify(serializeWidgets($('.tree-widgets')))) +
                    '&settings=' + encodeURIComponent(JSON.stringify(serialize($('.tree-settings')))) +
                    '&permissions=' + encodeURIComponent(JSON.stringify(serialize($('.tree-permissions')))) +
                    '&id=' + $('.pages-tree').jstree(true).get_selected()[0] +
                    '&template=' + $('.tree-data').find('[name="template"]').val() +
                    '&publish=' + $('.tree-data').find('[name="publish"]').val() +
                    '&version=' + $('.tree-data').find('[name="version"]').val() +
                    '&lang=' + $('.tree-struct').find('[name="pages-lang"]').val()
                ).always(function () {
                    $('.tree-data .dimmer').dimmer('hide');
                    $('#save-modal').modal('hide');
                }).fail(function () {
                    $(e.target).prepend(
                        '<div class="ui negative message">'+
                        '<div><?= $this->e($intl('common.tryagain')) ?></div></div>'
                    );
                }).done(function () {
                    var ref = $('.pages-tree').jstree(true);
                    ref.get_node(ref.get_selected()[0]).a_attr['class'] = (ref.get_node(ref.get_selected()[0])
                        .a_attr['class'] || '').replace('tree-stale','');
                    if (parseInt($('.tree-data').find('[name="publish"]').val(), 10)) {
                        ref.get_node(ref.get_selected()[0], true).children('a').removeClass('tree-stale');
                    } else {
                        ref.get_node(ref.get_selected()[0]).a_attr['class'] += ' tree-stale';
                        ref.get_node(ref.get_selected()[0], true).children('a').addClass('tree-stale');
                    }
                    $(e.target).prepend(
                        '<div class="ui positive message">'+
                        '<div><?= $this->e($intl('pages.messages.saved')) ?>'+
                        '</div></div>'
                    );
                    setTimeout(function () {
                        $('.tree-data').find('form').children('.message').remove();
                    }, 5000);
                });
                $(e.target).data('editing', false).find('.btn').blur();
            });

        // language dropdown
        $('input[name="pages-lang"]').change(function () {
            $('.pages-tree').jstree(true).refresh();
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

        // Tree config
        var cto = null;
        $('.pages-tree')
            .jstree({
                'core' : {
                    'data' : {
                        'url' : "<?= $url($name . '/node') ?>",
                        'data' : function (node) {
                            return { 'id' : node.id, 'lang' : $('input[name="pages-lang"]').val() };
                        },
                        'dataType' : 'json',
                        'type' : 'GET'
                    },
                    'animation' : 0,
                    'strings' : {
                        'Loading ...' : '<?= $this->e($intl('pages.texts.loading')) ?>'
                    },
                    'check_callback' : function (op, node, par, pos, more) {
                        if ((par === '#' || par.id === '#') &&
                            (op === 'create_node' || op === 'move_node' || op === 'copy_node')
                        ) {
                            return false;
                        }
                        if (par && par.data && par.data.locked &&
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
                    'worker' : false
                },
                'dnd' : {
                    "is_draggable" : function (nodes) {
                        for (var i = 0, j = nodes.length; i < j; i++) {
                            if (nodes[i].data && nodes[i].data.locked) { return false; }
                        }
                        return true;
                    }
                },
                'massload' : {
                    'url' : "<?= $url($name . '/nodes') ?>",
                    'data' : function (ids) {
                        return { 'id' : ids.join(','), 'lang' : $('input[name="pages-lang"]').val() };
                    },
                    'dataType' : 'json',
                    'type' : 'GET'
                },
                'search' : {
                    'show_only_matches' : true,
                    'ajax' : {
                        'url' : "<?= $url($name . '/search') ?>",
                        'data' : { 'lang' : $('input[name="pages-lang"]').val() },
                        'type' : 'GET'
                    }
                },
                'conditionalselect' : function () {
                    var editing = false;
                    $('.tree-data form').each(function () {
                        if ($(this).data('editing')) {
                            editing = true;
                            return false;
                        }
                    });
                    return (editing &&
                        !confirm('<?= $this->e($intl('pages.texts.confirmnotsaved')) ?>')
                    ) ? false : true;
                },
                // massload / search / langs
                'plugins' : [ 'dnd', 'search','state','conditionalselect','massload']
            })
            .on('delete_node.jstree', function (e, data) {
                $.post(
                    "<?= $url($name . '/remove') ?>",
                    { 'id' : data.node.id, 'lang' : $('input[name="pages-lang"]').val() }
                )
                    .fail(function () {
                        data.instance.refresh();
                    });
            })
            .on('create_node.jstree', function (e, data) {
                $.post(
                    "<?= $url($name . '/create') ?>", 
                    {
                        'id' : data.node.parent,
                        'lang' : $('input[name="pages-lang"]').val(),
                        'position' : data.position,
                        'title' : data.node.text
                    })
                    .done(function (d) {
                        data.instance.set_id(data.node, d.id);
                        data.instance.deselect_all();
                        data.instance.select_node(data.node.id);
                    })
                    .fail(function () {
                        data.instance.refresh();
                    });
            })
            .on('rename_node.jstree', function (e, data) {
                $.post(
                    "<?= $url($name . '/rename') ?>",
                    {
                        'id' : data.node.id,
                        'lang' : $('input[name="pages-lang"]').val(),
                        'title' : data.text
                    })
                    .done(function () {
                        data.instance.deselect_all();
                        data.instance.select_node(data.node.id);
                    })
                    .fail(function () {
                        data.instance.refresh();
                    });
            })
            .on('move_node.jstree', function (e, data) {
                if (confirm('<?= $this->e($intl('pages.texts.confirmmove')) ?>')) {
                    $.post(
                        "<?= $url($name . '/move') ?>",
                        {
                            'id' : data.node.id,
                            'lang' : $('input[name="pages-lang"]').val(),
                            'parent' : data.parent,
                            'position' : data.position
                        })
                        .fail(function () {
                            data.instance.refresh();
                        });
                } else {
                    data.instance.refresh();
                }
            })
            .on('copy_node.jstree', function (e, data) {
                if (confirm('<?= $this->e($intl('pages.texts.confirmcopy')) ?>')) {
                    $.post(
                        "<?= $url($name . '/copy') ?>",
                        {
                            'id' : data.original.id,
                            'lang' : $('input[name="pages-lang"]').val(),
                            'parent' : data.parent,
                            'position' : data.position
                        })
                        .always(function () {
                            data.instance.refresh();
                        });
                } else {
                    data.instance.refresh();
                }
            })
            .on('changed.jstree ready.jstree', function (e, data) {
                data.selected = data.instance.get_selected();
                if (cto) {
                    clearTimeout(cto);
                }
                cto = setTimeout(function () {
                    $('.tree-data form > .message').remove();
                    if (data.selected.length == 1) {
                        $('.tree-data .dimmer')
                            .find('.dimmer-message')
                                .hide()
                                .filter('.dimmer-message-load')
                                    .show()
                                    .end()
                                .end()
                            .dimmer('show');
                        $.ajax({
                            "type" : 'GET',
                            "url" : "<?= $url($name . '/data') ?>",
                            "data" : {
                                'id' : data.selected[0],
                                'lang' : $('input[name="pages-lang"]').val()
                            }
                        })
                        .always(function (xhr) {
                            $('.tree-data .dimmer').dimmer('hide');
                            if (xhr.status == 403) {
                                $('.tree-data .dimmer').find('.dimmer-message').hide()
                                    .filter('.dimmer-message-permission').show().end().end().dimmer('show');
                            }
                        })
                        .done(function (d) {
                            $('.tree-form [name="id"]').val(data.selected[0]);
                            $('.tree-form [name="lang"]').val($('input[name="pages-lang"]').val());
                            if (!d.url) {
                                var languages = JSON.parse('<?= json_encode($languages) ?>');
                                d.url = languages.length > 1 ?
                                    (languages[parseInt($('input[name="pages-lang"]').val(), 10)] || '') + '/' +
                                        data.selected[0] :
                                    data.selected[0];
                            }
                            canPublish = d.canPublish;
                            d.template = d.template;
                            //$('#template .item[data-id="'+(d.template)+'"]').click();
                            skipredraw = true;
                            $('.template-dropdown').dropdown('set exactly', d.template);
                            skipredraw = false;
                            $('.tree-widgets').html(d.html.widgets);
                            $('.tree-widgets')
                                .find('.widget-zone').each(function () {
                                    $(this).dropdown({
                                        onChange: function(value, text, $selectedItem) {
                                            $(this).parent().find('[name=__zone]').val(value);
                                        }
                                    });
                                });
                            $('.widgets-dropdown').dropdown();
                            $('.page.tree-settings').html(d.html.settings);
                            $('.page.tree-permissions').html(d.html.permissions);
                            $('.page.tree-content .template').each(function () {
                                $(this).html(d.html.templates[$(this).data('id')] || '');
                            });
                            //populate(d);
                            setTimeout(function () {
                                $('.tree-data form').each(function () { $(this).data('editing', false); });
                            }, 50);
                            return;
                        });
                        $('.tree-create, .tree-rename, .tree-remove, .tree-toggle')
                            .attr(
                                'disabled', 
                                false
                            );
                        var block = false;
                        for (var i = 0; i < data.selected.length; i++) {
                            if (data.instance.get_node(data.selected[i]).parent == '#') {
                                block = true;
                                break;
                            }
                        }
                        if (block) {
                            $('.tree-remove')
                                .attr(
                                    'disabled', 
                                    true
                                );
                        }
                        $('.tree-toggle').children('i')
                            .attr(
                                'class', 
                                'ui ' +
                                (data.instance.get_node(data.selected[0]).li_attr['class'].indexOf('hidden') !== -1 ?
                                    'unhide' : 'hide') + ' icon'
                            );
                    } else if (data.selected.length === 0) {
                        $('.tree-data .dimmer').find('.dimmer-message').hide()
                            .filter('.dimmer-message-no').show().end().end().dimmer('show');
                        $('.tree-struct button').attr('disabled', true);
                    } else {
                        $('.tree-data .dimmer').find('.dimmer-message').hide()
                            .filter('.dimmer-message-multi').show().end().end().dimmer('show');
                        $('.tree-struct button').not('.tree-remove').attr('disabled', true);
                        var block = false;
                        for (var i = 0; i < data.selected.length; i++) {
                            if (data.instance.get_node(data.selected[i]).parent == '#') {
                                block = true;
                                break;
                            }
                        }
                        if (block) {
                            $('.tree-remove')
                                .attr(
                                    'disabled', 
                                    true
                                );
                        }
                    }
                }, 50);
            });

        /**
         * Tree menu buttons
         */
        // create button
        $('.tree-create').on('click', function(e) {
            e.preventDefault();
            var ref = $('.pages-tree').jstree(true),
                sel = ref.get_selected();
            if (sel.length === 1 || !$(this).attr('disabled')) {
                sel = ref.create_node(
                    sel[0],
                    { 'icon' : 'ui large file alternate outline icon', 'li_attr' : { 'class' : 'tree-hidden' } }
                );
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
            if (sel.length &&
                !$(this).attr('disabled') &&
                confirm('<?= $this->e($intl('pages.texts.confirmdelete')) ?>')
            ) {
                ref.delete_node(sel);
            }
        });

        // show / hide button
        $('.tree-toggle').on('click', function(e) {
            e.preventDefault();
            var ref = $('.pages-tree').jstree(true),
                sel = ref.get_selected(),
                t = $(this);
            if (sel.length === 1 && !$(this).attr('disabled')) {
                sel = sel[0];
                var hidden = ref.get_node(sel).li_attr['class']
                if (
                    (t.children('i').hasClass('hide') &&
                        confirm('<?= $this->e($intl('pages.texts.confirmhide')) ?>')) ||
                    (!t.children('i').hasClass('hide') &&
                        confirm('<?= $this->e($intl('pages.texts.confirmshow')) ?>'))
                ) {
                    $.post(
                        "<?= $url($name . '/toggle') ?>",
                        {
                            'id' : sel,
                            'lang' : $('[name="pages-lang"]').val(),
                            'hidden' : t.children('i').hasClass('hide') ? '1' : '0'
                        })
                        .done(function () {
                            ref.get_node(sel).li_attr['class'] = t.children('i').hasClass('hide') ?
                                ref.get_node(sel).li_attr['class'] + ' tree-hidden' :
                                ref.get_node(sel).li_attr['class'].replace('tree-hidden','');
                            ref.get_node(sel, true).toggleClass('tree-hidden');
                            t.children('i').toggleClass('unhide hide');
                        })
                        .fail(function () {
                            ref.refresh();
                        });
                }
            }
        });
        $('.tree-struct .dropdown').dropdown();
    }, 100);
    $('.template-dropdown').dropdown({
        onChange : function (v) {
            $('.tree-data .form [name=template]').val(v);
            $('.tree-content .template').hide().filter('.template-' + v).show();
            if (!$('.tree-form [name=template_dummy]').length) {
                $('.tree-form').append('<input type="hidden" name="template_dummy" data-redraw="1" />');
            }
            if (!skipredraw) {
                $('.tree-form').find('[name=template_dummy]').trigger('change');
            }
        }
    });
    $('.add-widget').on('click', function (e) {
        e.preventDefault();
        var w = $('[name="widget-chooser"]').val();
        var d = Date.now();
        $('.tree-widgets')
            .append(
                '<div data-serialize="'+w+'__'+d+'">' +
                '<input type="hidden" name="new_widget_dummy" data-redraw="1" />' +
                '</div>'
            );
        $('.tree-widgets').find('[name=new_widget_dummy]').trigger('change');
    });
    $('.tree-widgets').on('click', '.widget-remove', function (e) {
        e.preventDefault();
        $(this).closest('div').remove();
    });
    $('.tree-widgets').on('click', '.widget-up', function (e) {
        e.preventDefault();
        var r = $(this).closest('div');
        r.prev().before(r);
    });
    $('.tree-widgets').on('click', '.widget-down', function (e) {
        e.preventDefault();
        var r = $(this).closest('div');
        r.next().after(r);
    });
    $('.tree-widgets').on('click', '.widget-toggle', function (e) {
        e.preventDefault();
        $(this).toggleClass('teal orange').children().toggleClass('slash');
        $(this).parent().find('[name=__hidden]').val($(this).hasClass('orange') ? '1' : '0');
    });
    $('.tree-form').on('redrawn', function () {
        $('.tree-widgets')
            .find('.widget-zone').each(function () {
                $(this).dropdown({
                    onChange: function(value, text, $selectedItem) {
                        $(this).parent().find('[name=__zone]').val(value);
                    }
                });
            });
    })
});
</script>
