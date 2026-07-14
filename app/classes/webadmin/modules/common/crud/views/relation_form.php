<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \webadmin\components\html\Form $form
 * @var string $cspNonce
 * @var string $name
 * @var string $back
 * @var string $relname
 * @var array<string,scalar> $relid
 * @var string $operation
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
            '<a href="' . $this->e($url($back)) . '" class="section">' .
                '<i class="' . $this->e($relation->getIcon() ?? 'puzzle') . ' icon"></i> ' .
                $this->e($intl($relation->getName() . '.title')) .
            '</a>' .
            '<i class="right angle icon divider"></i> ' .
            $this->e($relname)
    ]
)
?>

<div class="ui segment">
    <form class="ui form read-form main-form" method="post"
        data-redraw="<?= $this->e($url($relation->getSlug() . '/redraw/' . implode('|', $relid))) ?>">
        <?= $this->insert('webadmin::form', [ 'form' => $form ]) ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned blue secondary segment">
            <?php if ($operation === 'delete') : ?>
            <button class="ui red icon labeled submit button">
                <i class="trash icon"></i> <?= $this->e($intl('common.delete')) ?>
            </button>
            <?php endif ?>
            <?php if ($operation === 'update') : ?>
            <button class="ui orange icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
            <?php endif ?>
            <a href="<?= $this->e($url($back)) ?>" class="ui blue icon labeled submit button">
                <i class="left arrow icon"></i> <?= $this->e($intl('common.back')) ?>
            </a>
        </div>
    </form>
    <?= $this->section('content') ?>
</div>

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
