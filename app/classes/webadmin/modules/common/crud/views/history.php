<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var string $breadcrumb
 * @var string $back
 * @var string $title
 * @var array $pkey
 * @var array $versions
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
        'breadcrumb' => '<i class="' . $this->e($icon ?? 'clock') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.history'])) .
            '<i class="right angle icon divider"></i> ' .
            implode('_', $pkey)
    ]
);
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
<h3 class="ui left floated gray header">
    <i class="<?= $this->e($icon ?? 'clock') ?> icon"></i>
    <span class="content"><?= $this->e($intl([$title, 'crud.titles.history'])) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div>
    <?php foreach ($versions as $k => $d) : ?>
        <a href="#" class="ui basic <?= $k ? 'orange' : 'green' ?> right pointing label history-label">
            <div class="detail">
                <i class="user icon"></i>
                <?= $this->e($d['author']) . '<br /><i class="clock icon"></i>' . $this->e($d['created']) ?>
            </div>
        </a>
    <?php endforeach ?>
</div>
<br />
<div class="ui segment">
    <?php if (!count($versions)) : ?>
    <div class="ui warning message"><?= $this->e($intl('crud.history.noversions')) ?></div>
    <?php endif ?>

    <?php foreach ($versions as $version) : ?>
    <form class="ui form history-item">
        <div class="ui section divider history-divider"></div>
        <div class="ui center aligned grey secondary segment">
            <a href="<?= $this->e($back) ?>" class="ui blue icon labeled submit button">
                <i class="left arrow icon"></i> <?= $this->e($intl('common.back')) ?>
            </a>
        </div>
    </form>
    <?php endforeach ?>
    <?= $this->section('content') ?>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.history-label { margin-top:10px !important; }
.history-label > div { line-height:1.4rem; margin-left:0; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$('.history-label')
    .click(function (e) {
        e.preventDefault();
        $(this).siblings().addClass('basic').end().removeClass('basic');
        var curr = $('.history-item').hide().eq($(this).index()).show();
        $.get(window.location.toString(), { version : $(this).index() }).done(function (data) {
            curr.find('.history-divider').prevAll().remove().end().end().prepend(data.curr);
            curr.find('.ui.accordion').accordion({ exclusive : false });
            var prev = curr.prev('.history-item');
            if (prev.length) {
                prev.find('.history-divider').prevAll().remove().end().end().prepend(data.prev);
                prev.find('.ui.accordion').accordion({ exclusive : false });
                var curr_inputs = curr.find(':input');
                var prev_inputs = prev.find(':input');
                curr_inputs.each(function (i) {
                    if (JSON.stringify($(this).val()) !== JSON.stringify(prev_inputs.eq(i).val()) ||
                    $(this).prop('checked') !== prev_inputs.eq(i).prop('checked')
                    ) {
                        $(this).closest('.field').addClass('ui positive message').css({ 'padding': '10px' });
                    } else {
                        $(this).closest('.field').css('opacity', '0.75');
                    }
                });
            }
            $('.accordion-content').removeClass('active').prev().removeClass('active');
            $('.field.positive').closest('.accordion-content').addClass('active').prev().addClass('active');
        });
    })
    .eq(-1).removeClass('right pointing orange').addClass('blue').click();
var last = null;
$('.history-item').on('submit', function (e) { e.preventDefault(); });
$('.history-item').each(function () {
    var curr = $(this).find(':input');
    if (last) {
        curr.each(function (i) {
            if (JSON.stringify($(this).val()) !== JSON.stringify(last.eq(i).val()) ||
               $(this).prop('checked') !== last.eq(i).prop('checked')
            ) {
                $(this).closest('.field').addClass('ui positive message').css({ 'padding': '10px' });
            } else {
                $(this).closest('.field').css('opacity', '0.75');
            }
        });
    }
    last = curr;
});
$('.accordion-content').removeClass('active').prev().removeClass('active');
$('.field.positive').closest('.accordion-content').addClass('active').prev().addClass('active');
</script>
