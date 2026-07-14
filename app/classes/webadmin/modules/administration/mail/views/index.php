<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var \webadmin\modules\VisualModuleInterface $module
 * @var ?string $date
 * @var array $mail
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<h3 class="ui left floated teal header">
    <i class="<?= $this->e($module->getIcon()) ?> icon"></i>
    <span class="content"><?= $this->e($intl($url->getSegment(0, 'dashboard') . '.title')) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui center aligned segment top-container">
    <form method="get" class="ui form">
        <div class="inline one field">
            <div class="field">
                <?= $this->insert(
                    'webadmin::field/date',
                    [
                        'field' => new \webadmin\components\html\Field(
                            'date',
                            [ 'name' => 'date', 'value' => $date ],
                            [ 'label' => 'mail.fields.date', 'maxDate' => 'now' ]
                        )
                    ]
                ) ?>
            </div>
        </div>
    </form>
</div>

<div class="ui segment">
    <?php if (!count($mail)) : ?>
    <div class="ui message"><?= $this->e($intl('common.table.norecords')) ?></div>
    <?php else : ?>
    <table class="ui basic compact table">
        <thead>
            <tr>
                <th><?= $this->e($intl('mail.time')) ?></th>
                <th><?= $this->e($intl('mail.recv')) ?></th>
                <th><?= $this->e($intl('mail.subject')) ?></th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mail as $m) : ?>
            <tr>
                <td><?= $this->e($m['time']) ?></td>
                <td><?= $this->e($m['recv']) ?></td>
                <td><?= $this->e($m['subject']) ?></td>
                <td>
                    <a class="ui mini teal icon button" href="<?= $this->e($url('mail/download/' . $m['file'])) ?>">
                        <i class="download icon"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <?php endif ?>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.top-container { margin-top:0; }
.top-container .one.field { margin-bottom:0; text-align:center; }
input[name="date"] { text-align:center !important; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$(function () {
    $('[name=date]').change(function (e) { window.location = '?date=' + this.value; });
});
</script>
