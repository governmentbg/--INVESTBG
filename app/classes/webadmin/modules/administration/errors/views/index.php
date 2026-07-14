<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var \webadmin\modules\VisualModuleInterface $module
 * @var ?string $date
 * @var int $source
 * @var array<int, string> $sources
 * @var bool $picker
 * @var array<string,array{level:string,count:int,time:int,text:string,file:string,line:string}> $errors
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<h3 class="ui left floated red header">
    <i class="<?= $this->e($module->getIcon()) ?> icon"></i>
    <span class="content"><?= $this->e($intl($url->getSegment(0, 'dashboard') . '.title')) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui center aligned segment errors-container">
    <form method="get" class="ui err-form form">
    <div class="<?= $picker ? 'four' : 'three' ?> fields">
            <div class="field"></div>
            <div class="field">
                <?= $this->insert(
                    'webadmin::field/date',
                    [
                        'field' => new \webadmin\components\html\Field(
                            'date',
                            [ 'name' => 'date', 'value' => $date ],
                            [ 'label' => 'errors.fields.date', 'maxDate' => 'now' ]
                        )
                    ]
                ) ?>
            </div>
            <?php if ($picker) : ?>
                <div class="field">
                    <?= $this->insert(
                        'webadmin::field/select',
                        [
                            'field' => new \webadmin\components\html\Field(
                                'select',
                                [ 'name' => 'source', 'value' => $source ],
                                [ 'values' => $sources, 'label' => 'errors.fields.source', 'translate' => true ],
                            ),
                        ]
                    ) ?>
                </div>
            <?php endif ?>
        </div>
    </form>
</div>

<div class="ui segment">
    <?php if (!count($errors)) : ?>
    <div class="ui message"><?= $this->e($intl('common.table.norecords')) ?></div>
    <?php else : ?>
    <table class="ui basic compact table">
        <thead>
            <tr>
                <th><?= $this->e($intl('errors.error')) ?></th>
                <th><?= $this->e($intl('errors.times')) ?></th>
                <th><?= $this->e($intl('errors.date')) ?></th>
                <th> </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($errors as $k => $error) : ?>
            <tr class="<?= $this->e($error['level']) ?>" id="err_<?= $this->e($k) ?>">
                <td>
                    <?= $this->e($error['text']) ?><br />
                    <small><?= $error['file'] ? $this->e($error['file'] . ' : ' . $error['line']) : '&nbsp;' ?></small>
                </td>
                <td><?= $this->e((string)$error['count']) ?></td>
                <td><?= $this->e(date('d.m.Y H:i:s', $error['time'])) ?></td>
                <td>
                    <?php if (strlen($error['context'] ?? '') > 3) : ?>
                        <a href="#" class="ui blue mini labeled icon button err-modal">
                            <i class="eye icon"></i>
                            <?= $this->e($intl('errors.context')) ?>
                        </a>
                    <?php endif ?>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <?php endif ?>
</div>
<?php foreach ($errors as $k => $error) : ?>
    <div class="ui large modal" id="err_<?= $this->e($k) ?>_modal">
        <i class="close icon"></i>
        <div class="scrolling content">
            <pre>
                <?= $this->e(print_r(json_decode($error['context'] ?? '', true), true)) ?>
            </pre>
        </div>
    </div>
<?php endforeach ?>
<style nonce="<?= $this->e($cspNonce) ?>">
.top-container { margin-top:0; }
.top-container .one.field { margin-bottom:0; text-align:center; }
input[name="date"] { text-align:center !important; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$(function () {
    $('.err-form').change(function (e) { this.submit(); });
    $('.err-modal').on('click', function (e) {
        e.preventDefault();
        $('#' + $(this).closest('tr').attr('id') + '_modal').modal('show');
    });
});
</script>
