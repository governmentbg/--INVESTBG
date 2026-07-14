<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var string $version
 * @var array<string,string> $core
 * @var array<string,string> $versions
 * @var bool $cors
 * @var bool $csrf
 * @var bool $csp
 * @var bool $maintenance
 * @var bool $debug
 * @var bool $writable
 * @var bool $pp
 * @var bool $fp
 * @var bool $ids
 * @var bool $ratelimit
 * @var bool $push
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
    <h3 class="ui left floated black header settings-header">
        <i class="cogs icon"></i>
        <span class="content"><?= $this->e($intl('settings.title')) ?></span>
    </h3>
</div>
<?php $this->stop() ?>

<div class="ui five doubling cards">
    <div class="ui blue small card">
        <div class="center aligned content">
            <div class="ui icon header">
                <i class="search icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('settings.versions.title')) ?></p>
                    <div class="ui divider"></div>
                    <p><?= $this->e($version) ?></p>
                    <div class="sub header">
                        <?= $this->e($intl('settings.versions.description')) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php if (count($versions ?? [])) : ?>
        <div class="extra center aligned content">
            <button class="ui blue labeled icon button" id="versions">
                <i class="search icon"></i> <?= $this->e($intl('settings.versions.view')) ?>
            </button>
        </div>
        <?php endif ?>
    </div>
    <div class="ui green small card">
        <div class="center aligned content">
            <div class="ui icon header">
                <i class="lightning icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('settings.clearcache.title')) ?></p>
                    <div class="ui divider"></div>
                    <div class="sub header">
                        <?= $this->e($intl('settings.clearcache.description')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="extra center aligned content">
            <form method="post" class="ui form" action="<?= $this->e($url('settings/clear')) ?>">
                <button class="ui green labeled icon button">
                    <i class="recycle icon"></i> <?= $this->e($intl('settings.cache.clear')) ?>
                </button>
            </form>
        </div>
    </div>
    <div class="ui teal small card">
        <div class="center aligned content">
            <div class="ui icon header">
                <i class="sync apternate icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('settings.languagecache.title')) ?></p>
                    <div class="ui divider"></div>
                    <div class="sub header">
                        <?= $this->e($intl('settings.languagecache.description')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="extra center aligned content">
            <form method="post" class="ui form" action="<?= $this->e($url('settings/langs')) ?>">
                <button class="ui teal labeled icon button">
                    <i class="sync apternate icon"></i> <?= $this->e($intl('settings.languages.cache')) ?>
                </button>
            </form>
        </div>
    </div>
    <div class="ui olive small card">
        <div class="center aligned content">
            <div class="ui icon header">
                <i class="tachometer alternate icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('settings.envcache.title')) ?></p>
                    <div class="ui divider"></div>
                    <div class="sub header">
                        <?= $this->e($intl('settings.envcache.description')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="extra center aligned content">
            <form method="post" class="ui form" action="<?= $this->e($url('settings/env')) ?>">
                <button class="ui olive labeled icon button">
                    <i class="sync apternate icon"></i> <?= $this->e($intl('settings.env.cache')) ?>
                </button>
            </form>
        </div>
    </div>
    <div class="ui yellow small card">
        <div class="center aligned content">
            <a class="ui center aligned yellow icon header"
                href="<?= $this->e($url('config')) ?>">
                <i class="cog icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('config.title')) ?></p>
                    <div class="ui divider"></div>
                    <div class="sub header">
                        <?= $this->e($intl('config.description')) ?>
                    </div>
                </div>
            </a>
        </div>
        <div class="extra center aligned content">
            <form method="get" class="ui form" action="<?= $this->e($url('config')) ?>">
                <button class="ui yellow labeled icon button">
                    <i class="cog icon"></i> <?= $this->e($intl('config.title')) ?>
                </button>
            </form>
        </div>
    </div>
    <div class="ui purple small card">
        <div class="center aligned content">
            <a class="ui center aligned purple icon header"
                href="<?= $this->e($url('modules')) ?>">
                <i class="puzzle icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('modules.title')) ?></p>
                    <div class="ui divider"></div>
                    <div class="sub header">
                        <?= $this->e($intl('modules.description')) ?>
                    </div>
                </div>
            </a>
        </div>
        <div class="extra center aligned content">
            <form method="get" class="ui form" action="<?= $this->e($url('modules')) ?>">
                <button class="ui purple labeled icon button">
                    <i class="puzzle icon"></i> <?= $this->e($intl('modules.title')) ?>
                </button>
            </form>
        </div>
    </div>
    <div class="ui teal special small card">
        <div class="center aligned content">
            <div class="ui icon header">
                <i class="file icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('settings.files.title')) ?></p>
                    <div class="ui divider"></div>
                    <div class="sub header">
                        <?= $this->e($intl('settings.files.description')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="extra center aligned content">
            <form method="post" class="ui form" action="<?= $this->e($url('settings/files')) ?>">
                <div class="ui fluid action input">
                    <input type="password" class="" name="code"
                        placeholder="<?= $this->e($intl('settings.files.code')) ?>" />
                    <button class="ui teal icon button" id="checkFiles">
                        <i class="icon lock"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="ui divider"></div>
<?php if (!$writable) : ?>
    <div class="ui warning centered message">
        <?= $this->e($intl('settings.dbconfig_not_enabled')) ?>
    </div>
<?php endif ?>
<div class="ui five doubling cards">
    <?php
    foreach (
        [
            'debug' => [ $debug, 'bug' ],
            'maintenance' => [ $maintenance, 'configure' ],
            'cors' => [ $cors, 'plug' ],
            'csrf' => [ $csrf, 'spy' ],
            'csp' => [ $csp, 'zoom' ],
            'pp' => [ $pp, 'zoom alternate' ],
            'fp' => [ $fp, 'zoom alternate' ],
            'ids' => [ $ids, 'hand paper' ],
            'ratelimit' => [ $ratelimit, 'wait' ],
            'push' => [ $push, 'bell' ]
        ] as $key => $data
    ) : ?>
        <?php list($value, $icon) = $data; ?>
        <div class="ui <?= $value ? 'green' : 'red' ?> basic small card">
            <div class="center aligned content">
                <div class="ui <?= $value ? 'green' : 'red' ?> icon header">
                    <i class="<?= $icon ?> icon"></i>
                    <div class="content">
                        <p><?= $this->e($intl('settings.' . $key . '.title')) ?></p>
                        <div class="ui divider"></div>
                        <p><?= $this->e($intl($value ? 'settings.ison' : 'settings.isoff')) ?></p>
                        <div class="sub header">
                            <?= $this->e($intl('settings.' . $key . '.description')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!in_array($key, ['debug', 'maintenance', 'push']) || $writable) : ?>
            <div class="extra center aligned content">
                <form method="post" class="ui form" action="<?= $this->e($url('settings/toggle')) ?>">
                    <input type="hidden" name="key" value="<?= $this->e($key) ?>" />
                    <button class="ui <?= $value ? 'gray' : 'green' ?> labeled icon button">
                        <i class="<?= $value ? 'remove' : 'check' ?> icon"></i> 
                        <?= $this->e($intl('settings.' . ($value ? 'off' : 'on'))) ?>
                    </button>
                </form>
            </div>
            <?php endif ?>
        </div>
    <?php endforeach ?>
</div>

<div class="ui modal" id="versions_modal">
    <i class="close icon"></i>
    <div class="scrolling content">
        <table class="ui definition basic compact table">
            <thead>
                <tr>
                    <th>System</th>
                    <th>Version</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($core ?? [] as $k => $v) : ?>
                    <tr><td><strong><?= $this->e($k) ?></strong></td><td><?= $this->e($v) ?></td></tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <table class="ui definition basic compact table">
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Version</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($versions ?? [] as $k => $v) : ?>
                    <tr><td><strong><?= $this->e($k) ?></strong></td><td><?= $this->e($v) ?></td></tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <div class="ui segment">
            <pre><?php echo $this->e((string)json_encode($_SERVER, JSON_PRETTY_PRINT)) ?></pre>
        </div>
    </div>
</div>
<div class="ui modal" id="files_modal">
    <i class="close icon"></i>
    <form class="ui form" method="post"
        action="<?= $this->e($url('settings/updateFiles')) ?>">
        <div class="scrolling content">
            <h3 class="dividing header"><?= $this->e($intl('settings.files.results')) ?></h3>
            <div class="ui inverted dimmer">
                <div class="content">
                    <div class="center">
                        <div class="ui text loader dimmer-message dimmer-message-load">
                            <?= $this->e($intl('common.pleasewait')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <table class="ui definition basic compact table">
                <tbody>
                    <tr><td>File</td><td></td></tr>
                    <tr><td>Read</td><td></td></tr>
                    <tr class="negative"><td>Hash</td><td>
                        <div class="ui icon mini transparent fluid input">
                            <input id="hashFiles"
                                placeholder="<?= $this->e($intl('settings.files.hash')) ?>" type="password">
                            <i class="remove icon"></i>
                        </div>
                    </td></tr>
                    <tr><td>Version</td><td></td></tr>
                    <tr><td>Generated</td><td></td></tr>
                    <tr><td>Created</td><td class="mono-date"></td></tr>
                    <tr><td>Changed</td><td class="mono-date"></td></tr>
                    <tr><td>Deleted</td><td class="mono-date"></td></tr>
                </tbody>
            </table>
        </div>
    </form>
</div>

<style nonce="<?= $this->e($cspNonce) ?>">
.settings-title {padding:0.5rem !important;}
.mono-date { font-family: monospace !important; white-space: pre !important; }
#files_modal .form { padding:1rem 2rem 2rem 2rem !important; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$('#versions').on('click', function (e) {
    e.preventDefault();
    $('#versions_modal').modal('show');
});
$('#files_modal').modal({
    onHide : function (el) {
        $('#hashFiles').val('');
        $('#checkFiles').closest('form').find('input[type="password"]').val('');
        $('#updateFiles').prev().val('');
    }
});
$('#checkFiles').closest('form').submit(function (e) {
    e.preventDefault();
    $('#files_modal').modal('show');
    $('#files_modal').find('.dimmer').dimmer('show');
    $.ajax({
        type : "GET",
        url : $(this).attr('action'),
        headers : { 'Authorization' : $('#checkFiles').closest('form').find('input[type="password"]').val() }
    })
        .done(function (data) {
            if (!data) {
                $('#files_modal').find('.dimmer').dimmer('hide');
                $('#files_modal').modal('hide');
            } else {
                $('#files_modal').find('.dimmer').dimmer('hide');
                $('#files_modal tr').eq(0).attr('class', data.file ? 'positive' : 'negative').find('td').eq(1)
                    .html('<i class="' + (data.file ? 'check' : 'remove') + ' icon"></i>');
                $('#files_modal tr').eq(1).attr('class', data.read ? 'positive' : 'negative').find('td').eq(1)
                    .html('<i class="' + (data.read ? 'check' : 'remove') + ' icon"></i>');
                $('#files_modal tr').eq(2).attr('class', 'negative').data('hash', data.hash)
                    .data('generated', data.generated).find('icon').attr('class', 'ui remove icon');
                $('#files_modal tr').eq(3).find('td').eq(1).text(data.author);
                $('#files_modal tr').eq(4).find('td').eq(1).text(data.generated);
                $('#files_modal tr').eq(5).attr('class', data.created.length ? 'negative' : '').find('td').eq(1)
                    .text(data.created.join("\n"));
                $('#files_modal tr').eq(6).attr('class', data.changed.length ? 'negative' : '').find('td').eq(1)
                    .text(data.changed.join("\n"));
                $('#files_modal tr').eq(7).attr('class', data.deleted.length ? 'negative' : '').find('td').eq(1)
                    .text(data.deleted.join("\n"));
            }
        })
        .fail(function () {
            $('#files_modal').find('.dimmer').dimmer('hide');
            $('#files_modal').modal('hide');
        });
    setTimeout(function () {
        $('#checkFiles').closest('form').find('input[type="password"]').val('');
        $('#updateFiles').prev().val('');
    }, 200);
});
$('#hashFiles').keyup(function () {
    var row = $('#files_modal tr').eq(2);
    var hsh = md5(row.data('generated') + $(this).val()) === row.data('hash');
    row.attr('class', hsh ? 'positive' : 'negative');
    $(this).next().attr('class', hsh ? 'ui check icon' : 'ui remove icon');
});
$('#updateFiles').closest('form').submit(function (e) { e.preventDefault(); });
function md5(string) {
   function RotateLeft(lValue, iShiftBits) {
           return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
   }
   function AddUnsigned(lX,lY) {
           var lX4,lY4,lX8,lY8,lResult;
           lX8 = (lX & 0x80000000);
           lY8 = (lY & 0x80000000);
           lX4 = (lX & 0x40000000);
           lY4 = (lY & 0x40000000);
           lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
           if (lX4 & lY4) {
                   return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
           }
           if (lX4 | lY4) {
                   if (lResult & 0x40000000) {
                           return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                   } else {
                           return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                   }
           } else {
                   return (lResult ^ lX8 ^ lY8);
           }
   }

   function F(x,y,z) { return (x & y) | ((~x) & z); }
   function G(x,y,z) { return (x & z) | (y & (~z)); }
   function H(x,y,z) { return (x ^ y ^ z); }
   function I(x,y,z) { return (y ^ (x | (~z))); }

   function FF(a,b,c,d,x,s,ac) {
           a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
           return AddUnsigned(RotateLeft(a, s), b);
   };

   function GG(a,b,c,d,x,s,ac) {
           a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
           return AddUnsigned(RotateLeft(a, s), b);
   };

   function HH(a,b,c,d,x,s,ac) {
           a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
           return AddUnsigned(RotateLeft(a, s), b);
   };

   function II(a,b,c,d,x,s,ac) {
           a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
           return AddUnsigned(RotateLeft(a, s), b);
   };

   function ConvertToWordArray(string) {
           var lWordCount;
           var lMessageLength = string.length;
           var lNumberOfWords_temp1=lMessageLength + 8;
           var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
           var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
           var lWordArray=Array(lNumberOfWords-1);
           var lBytePosition = 0;
           var lByteCount = 0;
           while ( lByteCount < lMessageLength ) {
                   lWordCount = (lByteCount-(lByteCount % 4))/4;
                   lBytePosition = (lByteCount % 4)*8;
                   lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
                   lByteCount++;
           }
           lWordCount = (lByteCount-(lByteCount % 4))/4;
           lBytePosition = (lByteCount % 4)*8;
           lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
           lWordArray[lNumberOfWords-2] = lMessageLength<<3;
           lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
           return lWordArray;
   };

   function WordToHex(lValue) {
           var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
           for (lCount = 0;lCount<=3;lCount++) {
                   lByte = (lValue>>>(lCount*8)) & 255;
                   WordToHexValue_temp = "0" + lByte.toString(16);
                   WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
           }
           return WordToHexValue;
   };

   function Utf8Encode(string) {
           string = string.replace(/\r\n/g,"\n");
           var utftext = "";

           for (var n = 0; n < string.length; n++) {

                   var c = string.charCodeAt(n);

                   if (c < 128) {
                           utftext += String.fromCharCode(c);
                   }
                   else if((c > 127) && (c < 2048)) {
                           utftext += String.fromCharCode((c >> 6) | 192);
                           utftext += String.fromCharCode((c & 63) | 128);
                   }
                   else {
                           utftext += String.fromCharCode((c >> 12) | 224);
                           utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                           utftext += String.fromCharCode((c & 63) | 128);
                   }

           }

           return utftext;
   };

   var x=Array();
   var k,AA,BB,CC,DD,a,b,c,d;
   var S11=7, S12=12, S13=17, S14=22;
   var S21=5, S22=9 , S23=14, S24=20;
   var S31=4, S32=11, S33=16, S34=23;
   var S41=6, S42=10, S43=15, S44=21;

   string = Utf8Encode(string);

   x = ConvertToWordArray(string);

   a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;

   for (k=0;k<x.length;k+=16) {
           AA=a; BB=b; CC=c; DD=d;
           a=FF(a,b,c,d,x[k+0], S11,0xD76AA478);
           d=FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
           c=FF(c,d,a,b,x[k+2], S13,0x242070DB);
           b=FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
           a=FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
           d=FF(d,a,b,c,x[k+5], S12,0x4787C62A);
           c=FF(c,d,a,b,x[k+6], S13,0xA8304613);
           b=FF(b,c,d,a,x[k+7], S14,0xFD469501);
           a=FF(a,b,c,d,x[k+8], S11,0x698098D8);
           d=FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
           c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
           b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
           a=FF(a,b,c,d,x[k+12],S11,0x6B901122);
           d=FF(d,a,b,c,x[k+13],S12,0xFD987193);
           c=FF(c,d,a,b,x[k+14],S13,0xA679438E);
           b=FF(b,c,d,a,x[k+15],S14,0x49B40821);
           a=GG(a,b,c,d,x[k+1], S21,0xF61E2562);
           d=GG(d,a,b,c,x[k+6], S22,0xC040B340);
           c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
           b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
           a=GG(a,b,c,d,x[k+5], S21,0xD62F105D);
           d=GG(d,a,b,c,x[k+10],S22,0x2441453);
           c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
           b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
           a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
           d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
           c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
           b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
           a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
           d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
           c=GG(c,d,a,b,x[k+7], S23,0x676F02D9);
           b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
           a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
           d=HH(d,a,b,c,x[k+8], S32,0x8771F681);
           c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
           b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
           a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
           d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
           c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
           b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
           a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
           d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
           c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
           b=HH(b,c,d,a,x[k+6], S34,0x4881D05);
           a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
           d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
           c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
           b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
           a=II(a,b,c,d,x[k+0], S41,0xF4292244);
           d=II(d,a,b,c,x[k+7], S42,0x432AFF97);
           c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);
           b=II(b,c,d,a,x[k+5], S44,0xFC93A039);
           a=II(a,b,c,d,x[k+12],S41,0x655B59C3);
           d=II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
           c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
           b=II(b,c,d,a,x[k+1], S44,0x85845DD1);
           a=II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
           d=II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
           c=II(c,d,a,b,x[k+6], S43,0xA3014314);
           b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);
           a=II(a,b,c,d,x[k+4], S41,0xF7537E82);
           d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);
           c=II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
           b=II(b,c,d,a,x[k+9], S44,0xEB86D391);
           a=AddUnsigned(a,AA);
           b=AddUnsigned(b,BB);
           c=AddUnsigned(c,CC);
           d=AddUnsigned(d,DD);
    }
    var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);
    return temp.toLowerCase();
}
</script>

