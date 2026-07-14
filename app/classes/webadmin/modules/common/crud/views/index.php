<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \webadmin\components\html\Table $table
 * @var string $cspNonce
 * @var \webadmin\modules\VisualModuleInterface $module
 * @var string $created
 * @var string $updated
 * @var array $filters
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
    <h3 class="ui left floated <?= $this->e($module->getColor()) ?> header">
    <i class="<?= $this->e($module->getIcon()) ?> icon"></i>
    <span class="content"><?= $this->e($intl($module->getName() . '.title')) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<?= $this->insert('webadmin::table', [ 'table' => $table ]) ?>
<?= $this->section('content') ?>

<div id="export-modal" class="ui small modal">
    <form method="post" class="ui form">
        <input type="hidden" name="columns" value="" />
        <div class="one field">
            <label><?= $this->e($intl('crud.export_format')) ?></label>
            <div class="ui required input">
                <select name="format" required>
                    <option value="xlsx">XLSX</option>
                    <option value="csv">CSV</option>
                    <option value="xml">XML</option>
                </select>
            </div>
        </div>
        <div class="field">
            <div class="ui checkbox">
                <input id="all_columns" type="checkbox" name="all_columns" value="1" />
                <label for="all_columns"><?= $this->e($intl('export.all_columns')) ?></label>
            </div>
        </div>
        <div class="field">
            <div class="ui checkbox">
                <input id="current_page_only" type="checkbox" name="current_page_only" value="1" />
                <label for="current_page_only"><?= $this->e($intl('export.current_page_only')) ?></label>
            </div>
        </div>
        <div class="ui section divider"></div>
        <div class="ui center aligned olive secondary segment">
            <button class="ui olive icon labeled submit button">
                <i class="download icon"></i> <?= $this->e($intl('common.export')) ?>
            </button>
            <div class="ui basic cancel button">
                <?= $this->e($intl('common.cancel')) ?>
            </div>
        </div>
    </form>
</div>
<script nonce="<?= $this->e($cspNonce) ?>">
$('#export-modal .cancel').on('click', function (e) {
    $(this).closest('.modal').modal('hide');
});
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
        $(window).on('load', function () {
            if (
                (JSON.parse('<?= json_encode($created) ?>') || JSON.parse('<?= json_encode($updated) ?>')) &&
                $('.table-read tbody tr').length === 1
            ) {
                setTimeout(function () {
                    $('.table-read tbody tr .row-pick').click();
                }, 100);
            }
        });
    }
}
$('.export-button')
    .on('click', function (e) {
        e.preventDefault();
        var columns = [];
        $('.table-read th:visible').each(function () {
            if (this.getAttribute('data-column')) {
                columns.push(this.getAttribute('data-column'));
            }
        });
        $('#export-modal').find('[name="columns"]').val(columns.join(',')).end().modal('show');
    })
$('#export-modal').find('form').on('submit', function () { $(this).closest('.modal').modal('hide'); });
</script>
<style nonce="<?= $this->e($cspNonce) ?>">
#export-modal form { padding:20px; }
</style>
