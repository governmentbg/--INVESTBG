<?php

/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var \webadmin\components\html\Form $form
 * @var array<string,string> $fields
 * @var string $cspNonce
 * @var string $back
 * @var string $title
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php
$this->layout('webadmin::main');
?>



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
        <?= $this->insert('webadmin::form', ['form' => $form]) ?>
        <div class="import-columns">
            <select name="columns[]" disabled="disabled">
                <option value=""><?= $this->e($intl('import.donotuse')) ?></option>

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
    .error-row {
        margin: 1rem 0 0 0;
    }

    .error-msg {
        margin: 0;
    }

    .import-data {
        margin-top: 20px;
    }

    .import-columns {
        display: none;
    }

    .excel-preview-container {
        max-height: 350px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 0;
        background: #fff;
    }

    .excel-preview-table {
        width: 100%;
        min-width: max-content;
    }

    .excel-preview-table thead th {
        position: sticky;
        top: 0;
        background: #f9fafb !important;
        font-weight: bold !important;
        z-index: 2;
        border-bottom: 2px solid #ccc !important;
    }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
    $('[name=excel_file]').on('changed.plupload', function(e, data) {

        if (!data || !data.url) {
            return;
        }

        $.get(data.url + '?info=1&sample=10')
            .done(function(file) {

                if (!file || !file.sample || !file.sample.length) {
                    return alert("<?= $this->e($intl('import.emptyfile')) ?>");
                }

                $('.main-form .data').empty();

                var sample = file.sample;

                var header = sample[0];
                var colCount = header.length;

                var container = $('<div class="excel-preview-container"></div>');
                var table = $('<table class="ui celled compact striped table excel-preview-table"></table>');

                var thead = $('<thead><tr></tr></thead>');
                header.forEach(function(colName) {
                    thead.find("tr").append(
                        $('<th>').append($('<b>').text(colName ?? ''))
                    );

                });
                table.append(thead);


                var tbody = $('<tbody></tbody>');

                sample.forEach(function(row, i) {

                    if (i === 0) return;

                    var tr = $('<tr></tr>');
                    var rowArray = Array.isArray(row) ? row : Object.values(row);

                    for (var c = 0; c < colCount; c++) {
                        tr.append($('<td></td>').text(rowArray[c] ?? ""));
                    }

                    tbody.append(tr);
                });

                table.append(tbody);
                container.append(table);

                $('.main-form .data').append(container);
            });
    });
</script>