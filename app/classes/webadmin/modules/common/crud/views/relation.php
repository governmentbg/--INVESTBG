<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \webadmin\components\html\Table $table
 * @var string $cspNonce
 * @var string $name
 * @var \webadmin\modules\VisualModuleInterface $module
 * @var \webadmin\modules\VisualModuleInterface $relation
 * @var \vakata\http\Uri $url
 * @var \vakata\intl\Intl $intl
 */
?>
<?php
$this->layout(
    'webadmin::main',
    [
        'breadcrumb' => '<i class="' . $this->e($icon ?? 'eye') . ' icon"></i> ' .
            $this->e($intl('crud.breadcrumb.read')) .
            '<i class="right angle icon divider"></i> ' .
            $this->e($name) .
            '<i class="right angle icon divider"></i> ' .
            '<i class="' . $this->e($relation->getIcon() ?? 'puzzle') . ' icon"></i> ' .
            $this->e($intl($relation->getName() . '.title'))
    ]
)
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<h3 class="ui left floated blue header">
    <i class="<?= $this->e($relation->getIcon()) ?> icon"></i>
    <span class="content"><?= $this->e($intl($relation->getName() . '.title')) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<?= $this->insert('webadmin::table', [ 'table' => $table ]) ?>
<?= $this->section('content') ?>

<?php if ($table->hasOperation('create')) : ?>
    <?php $nested_id = 'nested_' . md5((string)microtime(true)); ?>
    <form method="post" action="<?= $this->e($url($table->getOperation('create')->getAttr('href'))) ?>">
        <input type="hidden" name="__nested_id" id="<?= $this->e($nested_id) ?>" />
    </form>
    <div id="modal_<?= $this->e($nested_id) ?>" class="ui fullscreen modal"></div>
    <script nonce="<?= $this->e($cspNonce) ?>">
    (function () {
        var multiple = false;
        $('.crud-header .row-operations').append(
            '<button class="ui orange labeled icon button" ' +
            'id="button_<?= $this->e($nested_id) ?>">' +
            '<i class="check icon"></i> <?= $this->e($intl('fields.module.pick')) ?>' +
            '</button>'
        );
        $('#button_<?= $this->e($nested_id) ?>').click(function (e) {
            e.preventDefault();
            $('#modal_<?= $this->e($nested_id) ?>')
                .html('<iframe class="module-field-iframe" src="" width="100%" height="80vh"></iframe>')
                .find('iframe')
                    .off('load')
                    .on('load', function () {
                        var iframe = this.contentWindow;
                        if (iframe.selectedPromise) {
                            iframe.selectedPromise.then(function (vv) {
                                $('#<?= $this->e($nested_id) ?>').val(vv.id).closest('form').submit();
                            });
                        } else {
                            $('#modal_<?= $this->e($nested_id) ?>').modal('hide');
                        }
                    })
                    .attr('src', "<?= $this->e($req->getUrl()->get($relation->getSlug())) ?>")
                    .end()
                .modal('show');
        });
        $('.crud-header .row-operations .button').eq(0).click(function (e) {
            e.preventDefault();
            $('#modal_<?= $this->e($nested_id) ?>')
                .html('<iframe src="" width="100%" height="80vh" class="module-field-iframe"></iframe>')
                .find('iframe')
                    .off('load')
                    .on('load', function () {
                        var iframe = this.contentWindow;
                        if (iframe.selectedPromise) {
                            iframe.selectedPromise.then(function (vv) {
                                $('#<?= $this->e($nested_id) ?>').val(vv.id).closest('form').submit();
                            });
                        } else {
                            $('#modal_<?= $this->e($nested_id) ?>').modal('hide');
                        }
                    })
                    .attr('src', "<?= $this->e($req->getUrl()->get($relation->getSlug() . '/create')) ?>")
                    .end()
                .modal('show');
        });
    }());
    </script>
<?php endif ?>

<script nonce="<?= $this->e($cspNonce) ?>">
if (window.parent && window.parent !== window.self) {
    var selectedPromise = {
        cbks : [],
        then : function (cb) { this.cbks.push(cb); },
        when : function (value) {
            this.cbks.forEach(function (v) {
                v.call(this, value);
            });
        }
    };
    $('body').addClass('no-menu').addClass('inside-modal');
    $('.row-operations').children().not('.thumb-button, [href$="create"]').hide();
    var tbl = $('.table-read');
    if (!tbl.hasClass('empty-table')) {
        tbl
            .find('td:last-child')
                .empty()
                .append('<a href="#" class="ui mini green labeled icon button row-pick">'+
                '<i class="check icon"></i> <?= $this->e($intl('fields.module.pickrow')) ?></a>')
                .end()
            .on('click', '.row-pick', function (e) {
                e.preventDefault();
                selectedPromise.when({
                    'id'   : $(this).closest('tr').data('id'),
                    'html' : $(this).closest('tr'),
                    'head' : $(this).closest('table').children('thead')
                });
                $(this).closest('tr').addClass('positive').find('.button').remove();
            });
    }
}
</script>
