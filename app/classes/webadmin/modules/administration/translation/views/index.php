<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var string $lang
 * @var array<string,string> $langs
 * @var bool $all
 * @var array<string,string> $data
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
    <h3 class="ui left floated header translation-header">
        <i class="language icon"></i>
        <span class="content"><?= $this->e($intl('translation.title')) ?></span>
    </h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <a class="ui yellow right floated icon labeled button" id="store">
        <i class="disk icon"></i> <?= $this->e($intl('translation.save')) ?>
    </a>
    <a href="?lang=<?= $this->e($lang) ?>&download=1" class="ui green right floated icon labeled button">
        <i class="download icon"></i> <?= $this->e($intl('translation.download')) ?>
    </a>
    <select class="ui dropdown" id="lang">
        <?php foreach ($langs as $k => $v) : ?>
            <option value="<?= $this->e($k) ?>" <?= $k == $lang ? 'selected' : '' ?>><?= $this->e($k) ?></option>
        <?php endforeach ?>
    </select>
    <div class="ui divider"></div>
    <form class="ui form validate-form" method="post" id="translation">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.pleasewait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- <p><?= $this->e($intl('translation.description')) ?></p> -->
        <div class="ui pointing secondary menu">
            <a href="?lang=<?= $this->e($lang) ?>&all=0" class="item <?= $all ? '' : 'active' ?>">
                <?= $this->e($intl('translations.missing')) ?>
            </a>
            <a href="?lang=<?= $this->e($lang) ?>&all=1" class="item <?= $all ? 'active' : '' ?>">
                <?= $this->e($intl('translations.all')) ?>
            </a>
        </div>
        <?php
        $last = null;
        if (!count($data) && !$all) {
            echo '<div class="ui info message block-message">' .
                $this->e($intl('translations.nomissing')) .
                '</div>';
        }
        foreach ($data as $k => $v) {
            $word = explode('.', $k)[0];
            if ($last !== $word) {
                echo '<h4 class="ui dividing header">' . $this->e($word) . '</h4>';
                $last = $word;
            }
            echo '<div class="two fields">';
            echo '<div class="ui field">';
            echo '<div class="ui input">';
            echo '<input name="keys[]" readonly value="' . $this->e($k) . '" />';
            echo '</div>';
            echo '</div>';
            echo '<div class="ui field">';
            echo '<div class="ui input">';
            echo '<input name="values[]" value="' . $this->e($v ?? '') . '" />';
            echo '</div>';
            echo '</div>';
            if ($all) {
                echo '<button class="ui red icon button remove-button"><i class="delete icon"></i></button>';
            }
            echo '</div>';
        }
        if ($all) {
            echo '<h4 class="ui dividing header">' . $this->e($intl('translation.new')) . '</h4>';
            echo '<div id="translation_new"></div>';
            echo '<div class="translation-center">';
            echo '<button id="translation_add" class="ui green labeled icon button">';
            echo '<i class="plus icon"></i>' . $this->e($intl('translation.add'));
            echo '</button></div>';
        }
        ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned orange secondary segment">
            <button class="ui orange icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
        </div>
    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.block-message { display:block;}
.translation-header { padding:0.5rem !important; }
.translation-center { text-align:center; }
</style>

<script nonce="<?= $this->e($cspNonce) ?>">
$("#lang").on('change', function () {
    window.location.href = '?lang=' + this.value;
}).dropdown();
$("#store").on('click', function (e) {
    e.preventDefault();
    $.post('<?= $url('translation/store') ?>?lang=<?= $this->e($lang) ?>').always(function () {
        window.location.reload();
    })
});
$('#translation').on('click', '.remove-button', function (e) {
    e.preventDefault();
    var h = $(this).parent().prevAll('h4').eq(0);
    $(this).parent().remove();
    if (h.next().is('h4')) {
        h.remove();
    }
});
$('#translation_add').click(function (e) {
    e.preventDefault();
    $('#translation_new').append(
        '<div class="two fields">'+
        '<div class="ui required field" '+
        ' data-validate=\'[{"rule":"required","data":[],"message":"","when":null}]\'>'+
        '<div class="ui input"><input name="keys[]" value="" /></div>'+
        '</div>'+
        '<div class="ui field"><div class="ui input"><input name="values[]" value="" /></div></div>'+
        '<button class="ui red icon button remove-button"><i class="delete icon"></i></button>'+
        '</div>'
    );
});
</script>
