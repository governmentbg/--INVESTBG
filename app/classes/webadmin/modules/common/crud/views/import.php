<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \webadmin\components\html\Form $form
 * @var array<string,string> $fields
 * @var string $cspNonce
 * @var string $breadcrumb
 * @var string $back
 * @var string $title
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php
$this->layout(
    'webadmin::main',
    [
        'breadcrumb' => '<i class="' . $this->e($icon ?? 'plus') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.create']))
    ]
);
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
<h3 class="ui left floated yellow header">
    <i class="<?= $this->e($icon ?? 'plus') ?> icon"></i>
    <span class="content"><?= $this->e($intl([$title, 'crud.titles.import'])) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<?php if (isset($errors) && count($errors)) : ?>
<div class="ui error message">
    <h3><?= $this->e($intl('import.errors')) ?></h3>
    <?php foreach ($errors as $row => $err) : ?>
        <p class="error-row"><strong><?= $this->e($intl('import.row')) . ' ' . $this->e($row) ?></strong></p>
        <?php foreach ($err as $e) : ?>
            <p class="error-msg"><?= $this->e($intl($e)) ?></p>
        <?php endforeach ?>
    <?php endforeach ?>
</div>

<?php endif ?>

<div class="ui yellow segment">
    <form class="ui form validate-form main-form" method="post">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.pleasewait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <?= $this->insert('webadmin::form', [ 'form' => $form ]) ?>
        <div class="import-columns">
            <select name="columns[]" disabled="disabled">
                <option value=""><?= $this->e($intl('import.donotuse')) ?></option>
                <?php foreach ($fields as $field => $name) : ?>
                    <option value="<?= $this->e($field) ?>"><?= $this->e($intl($name)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="data import-data">
        </div>
        <div class="ui section divider"></div>
        <div class="ui center aligned yellow secondary segment">
            <button class="ui yellow icon labeled submit button">
                <i class="upload icon"></i> <?= $this->e($intl('common.import')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
        </div>
    </form>
    <?= $this->section('content') ?>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.error-row { margin:1rem 0 0 0; }
.error-msg { margin:0; }
.import-data { margin-top:20px; }
.import-columns { display:none; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$('[name=import]').on('changed.plupload', function (e, data) {
    $.get(data.url + '?info=1&sample=10')
        .done(function (file) {
            if (!file.sample || !file.sample.length || !file.sample[0] || !file.sample[0].length) {
                return alert("<?= $this->e($intl('import.emptyfile')) ?>");
            }
            $('.main-form data').empty().append('');
            var columnsCount = file.sample[0].length,
                table = $(
                    '<table class="ui basic striped celled compact table"><thead><tr></tr><tr>'+
                    '<th colspan="'+columnsCount+'"><div class="field">'+
                    '<div class="ui checkbox"><input id="skip_first" type="checkbox" name="skip_first" value="1" />'+
                    '<label for="skip_first"><?= $this->e($intl('import.skip_first')) ?></label></div></div>'+
                    '</th></tr></thead><tbody></tbody></table>'),
                thead = table.find('tr').eq(0),
                tbody = table.find('tbody'),
                select = $('.main-form select'),
                tr = $('<tr></tr>'),
                th = $('<th></th>').append(select.clone().prop('disabled', false)),
                i;
            for (i = 0; i < columnsCount; i++) {
                thead.append(th.clone());
            }
            file.sample.forEach(function (v) {
                tbody.append(tr.clone().append(v.map(function (cell) { return $('<td></td>').text(cell); })));
            });
            $('.main-form .data').append(table);
        });
});
</script>
