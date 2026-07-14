<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var array $user
 * @var array $locales
 * @var array $certificates
 * @var array $totps
 * @var array $devices
 * @var array $codes
 * @var array<string,array{ip:string,ua:string,cr:int}> $sessions
 * @var array $tokens
 * @var array $totp
 * @var bool $forceTFA
 * @var ?\vakata\user\Provider $password
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>
<style nonce="<?= $this->e($cspNonce) ?>">
.code { font-family:monospace; display:inline-block; min-width:24%; text-align:center; padding:1rem; font-size:1.4rem; }

div.qrcode > div > div {
    margin: 0;
    padding: 0;
    height: 10px;
}
div.qrcode > div > div > span {
    display: inline-block;
    width: 10px;
    height: 10px;
}
#profile-grid .header-center { text-align:center; }
#profile-grid .grid-center { margin:0 3rem; }
#profile-grid .input-disabled { background:#ebebeb; }
#profile-grid .form-password .inline.field { text-align:left; }
#profile-grid .form-password .inline.field > label { width:40%; text-align:right; }
#profile-grid .form-password .inline.field > input { width:50%; }
#profile-grid .form-password .inline.field-hidden { display:none; }
#profile-grid .accordion { text-align:left; }
#profile-grid .accordion .acc-button { float:right; margin-top:-3px; }
#profile-grid .lefted > span.icon { float:right; }
.modal > .form {padding:2rem;}
.form .mono { font-family:monospace}
</style>

<div class="ui fluid divided stackable grid">
<div class="ui two column row" id="profile-grid">
    <div class="ui column">
        <div class="ui teal center aligned segment">
            <h4 class="dividing icon header header-center">
                <i class="user icon"></i> <?= $this->e($intl('profile.personaldata')) ?>
            </h4>
            <form method="post" class="ui form" action="<?= $this->e($url('profile/data')) ?>">
                <div class="ui divider"></div>
                <div class="ui stackable two column wide grid grid-center">
                    <div class="column">
                        <div class="field">
                            <label><?= $this->e($intl('profile.name')) ?></label>
                            <input type="text" name="name" autocomplete="name" value="<?= $this->e($user['name']) ?>" />
                        </div>
                        <div class="field">
                            <label><?= $this->e($intl('profile.mail')) ?></label>
                            <input type="text" name="mail" autocomplete="email"
                                value="<?= $this->e($user['mail']) ?>" />
                        </div>
                        <?php if ($password) : ?>
                        <div class="disabled field">
                            <label><?= $this->e($intl('profile.username')) ?></label>
                            <input class="input-disabled" type="text" disabled="disabled"
                                value="<?= $this->e($password->getID()) ?>" />
                        </div>
                        <?php endif ?>
                    </div>
                    <div class="column">
                        <div class="field image-picker">
                            <?= $this->insert(
                                'webadmin::field/image',
                                [ 'field' => new \webadmin\components\html\Field(
                                    'image',
                                    [ 'name' => 'avatar', 'value' => $user['avatar'] ],
                                    [ 'label' => 'profile.picture', 'editor' => true ]
                                ) ]
                            ) ?>
                        </div>
                    </div>
                </div>
                <div class="ui divider"></div>
                <button class="ui teal labeled icon button">
                    <i class="user icon"></i> <?= $this->e($intl('profile.savepersonaldata')) ?>
                </button>
            </form>
        </div>
        <?php if ($password) : ?>
        <div class="ui orange center aligned segment">
            <h4 class="dividing icon header">
                <i class="lock icon"></i> <?= $this->e($intl('profile.changepassword')) ?>
            </h4>
            <p><?= $this->e($intl('profile.changepasswordhelp')) ?></p>
            <form method="post" class="ui form form-password" action="<?= $this->e($url('profile/password')) ?>">
                <div class="ui divider"></div>
                <div class="inline field field-hidden">
                    <label><?= $this->e($intl('profile.username')) ?></label>
                    <input type="text" disabled="disabled"
                        value="<?= $this->e($password->getID()) ?>" />
                </div>
                <div class="inline field">
                    <label><?= $this->e($intl('profile.password')) ?></label>
                    <div class="ui right icon input">
                        <input type="password" name="old_password" autocomplete="current-password" />
                        <i class="eye slash link icon password-reveal"></i>
                    </div>
                </div>
                <div class="inline field">
                    <label><?= $this->e($intl('profile.newpassword')) ?></label>
                    <div class="ui right icon input">
                        <input type="password" name="new_password1" autocomplete="new-password" />
                        <i class="eye slash link icon password-reveal"></i>
                    </div>
                </div>
                <div class="inline field">
                    <label><?= $this->e($intl('profile.repeatpassword')) ?></label>
                    <div class="ui right icon input">
                        <input type="password" name="new_password2" autocomplete="new-password" />
                        <i class="eye slash link icon password-reveal"></i>
                    </div>
                </div>
                <div class="ui divider"></div>
                <button class="ui orange labeled icon button">
                    <i class="icon lock"></i> <?= $this->e($intl('profile.change')) ?>
                </button>
            </form>
        </div>
        <?php endif ?>

        <?php if (count($locales) > 1) : ?>
        <div class="ui olive center aligned segment">
            <h4 class="dividing icon header"><i class="world icon"></i> <?= $this->e($intl('profile.locale')) ?></h4>
            <form method="post" class="ui form" action="<?= $this->e($url('profile/locale')) ?>">
                <div class="field">
                    <select class="ui search dropdown" name="locale">
                        <?php foreach ($locales as $v) : ?>
                            <option value="<?= $this->e($v) ?>"
                                <?= $req->getAttribute('locale') == $v ? ' selected="selected"' : '' ?>>
                                <?= $this->e($v) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="ui divider"></div>
                <button class="ui olive labeled icon button">
                    <i class="world icon"></i> <?= $this->e($intl('profile.savecertificate')) ?>
                </button>
            </form>
        </div>
        <?php endif ?>
    </div>
    <div class="ui column">
        <div class="ui center aligned <?= $user['tfa'] || $user['tfaF'] ? 'green' : 'red' ?> segment" id="tfa">
            <span class="ui <?= $user['tfa'] || $user['tfaF'] ? 'green' : 'red' ?> top left attached label">
                <?= $this->e($intl($user['tfa'] || $user['tfaF'] ? 'profile.tfaon' : 'profile.tfaoff')) ?>
            </span>
            <h4 class="icon header"><i class="lock icon"></i> <?= $this->e($intl('profile.tfa')) ?></h4>
            <p><?= $this->e($intl('profile.tfahelp')) ?></p>
            <div class="ui divider"></div>
            <?php if ($user['tfaF']) : ?>
                <?php if ($forceTFA) : ?>
                    <div class="ui error message"><?= $this->e($intl('tfa.required')) ?></div>
                <?php else : ?>
                    <div class="ui info message"><?= $this->e($intl('tfa.forced')) ?></div>
                <?php endif ?>
            <?php else : ?>
                <form method="post" action="<?= $this->e($url('profile/tfa')) ?>">
                    <input type="hidden" name="tfa" value="<?= $user['tfa'] ? 0 : 1 ?>" />
                    <button class="ui large <?= $user['tfa'] ? 'red' : 'green' ?> labeled icon button">
                        <i class="icon <?= $user['tfa'] ? 'warning' : 'lock' ?>"></i>
                        <?= $this->e($intl($user['tfa'] ? 'profile.tfaturnoff' : 'profile.tfaturnon')) ?>
                    </button>
                </form>
                <div class="ui divider"></div>
            <?php endif ?>
            <div class="ui styled fluid accordion">
                <div class="title lefted">
                    <?php if (count($certificates)) : ?>
                        <span class="ui mini green circular icon label acc-button">
                            <i class="check icon"></i>
                        </span>
                    <?php else : ?>
                        <span class="ui mini red circular icon label acc-button">
                            <i class="remove icon"></i>
                        </span>
                    <?php endif ?>
                    <i class="certificate icon"></i> <?= $this->e($intl('tfa.certificates')) ?>
                </div>
                <div class="content">
                    <form method="post" class="centered">
                        <div class="ui centered content">
                            <button class="ui purple right labeled icon button" id="certfile">
                                <i class="file icon"></i> <?= $this->e($intl('profile.addcertificatefile')) ?>
                            </button>
                            <button class="ui purple right labeled icon button" id="certtext">
                                <i class="pencil icon"></i> <?= $this->e($intl('profile.addcertificatetext')) ?>
                            </button>
                            <button class="ui purple right labeled icon button" id="certcurrent">
                                <i class="certificate icon"></i> <?= $this->e($intl('profile.addcertificatecurrent')) ?>
                            </button>
                        </div>
                    </form>
                    <div class="ui divider"></div>
                    <?php if (count($certificates)) : ?>
                        <form method="post" class="certificates" action="<?= $this->e($url('profile/certificates')) ?>">
                            <input type="hidden" name="certificates[]" value="" />
                            <table class="ui basic table">
                                <thead>
                                    <tr>
                                        <th><?= $this->e($intl('profile.certificateno')) ?></th>
                                        <th><?= $this->e($intl('profile.certificatecreated')) ?></th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($certificates as $certificate) : ?>
                                        <tr>
                                            <td>
                                                <input type="hidden" disabled name="certificates[]"
                                                    value="<?= $this->e($certificate->getID()) ?>" />
                                                <?= strtoupper($this->e(explode(' / ', $certificate->getID())[0])) ?>
                                            </td>
                                            <td><?= $this->e(date('d.m.Y H:i:s', $certificate->getCreated())) ?></td>
                                            <td class="centered">
                                                <a href="#" class="ui mini icon button remove-certificate">
                                                    <i class="trash icon"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                                <tfoot class="full-width">
                                    <tr>
                                        <th colspan="3" class="centered">
                                            <button class="ui purple labeled icon button">
                                                <i class="certificate icon"></i>
                                                <?= $this->e($intl('profile.savecertificate')) ?>
                                            </button>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </form>
                    <?php else : ?>
                        <div class="ui message centered">
                            <?= $this->e($intl('profile.nocertificates')) ?></div>
                    <?php endif ?>
                </div>
                <div class="title lefted">
                    <?php if (count($totps)) : ?>
                        <span class="ui mini green circular icon label acc-button">
                            <i class="check icon"></i></span>
                    <?php else : ?>
                        <span class="ui mini red circular icon label acc-button">
                            <i class="remove icon"></i></span>
                    <?php endif ?>
                    <i class="mobile icon"></i> <?= $this->e($intl('tfa.app')) ?>
                </div>
                <div class="content centered">
                    <p><?= $this->e($intl('profile.totpapps')) ?></p>
                    <div class="ui divider"></div>
                    <button class="ui large green right labeled icon button totp-add">
                        <i class="icon plus"></i>
                        <?= $this->e($intl('profile.totpadd')) ?>
                    </button>
                    <div class="ui divider"></div>
                    <?php if (count($totps)) : ?>
                        <form method="post" class="totps" action="<?= $this->e($url('profile/totps')) ?>">
                            <input type="hidden" name="totps[]" value="" />
                            <table class="ui basic table">
                                <thead>
                                    <tr>
                                        <th><?= $this->e($intl('profile.totpname')) ?></th>
                                        <th><?= $this->e($intl('profile.totpcreated')) ?></th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($totps as $key) : ?>
                                        <tr>
                                            <td>
                                                <input type="hidden" disabled name="totps[]"
                                                    value="<?= $this->e($key->getID()) ?>" />
                                                <?= $this->e($key->getName()) ?>
                                            </td>
                                            <td><?= $this->e(date('d.m.Y H:i:s', $key->getCreated())) ?></td>
                                            <td class="centered">
                                                <a href="#" class="ui mini icon button remove-key">
                                                    <i class="trash icon"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                                <tfoot class="full-width">
                                    <tr>
                                        <th colspan="3" class="centered">
                                            <button disabled class="ui green labeled icon button">
                                                <i class="save icon"></i> <?= $this->e($intl('profile.totpssave')) ?>
                                            </button>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </form>
                    <?php else : ?>
                        <div class="ui message"><?= $this->e($intl('profile.nototps')) ?></div>
                    <?php endif ?>
                </div>
                <div class="title lefted">
                    <?php if (count($devices)) : ?>
                        <span class="ui mini green circular icon label acc-button">
                            <i class="check icon"></i>
                        </span>
                    <?php else : ?>
                        <span class="ui mini red circular icon label acc-button">
                            <i class="remove icon"></i>
                        </span>
                    <?php endif ?>
                    <i class="computer icon"></i> <?= $this->e($intl('profile.totpdevices')) ?>
                </div>
                <div class="content">
                    <?php if (count($devices)) : ?>
                        <p><strong></strong></p>
                        <form method="post" class="totp-devices" action="<?= $this->e($url('profile/devices')) ?>">
                            <input type="hidden" name="devices[]" value="" />
                            <table class="ui basic table">
                                <thead>
                                    <tr>
                                        <th><?= $this->e($intl('profile.totpdevicename')) ?></th>
                                        <th><?= $this->e($intl('profile.totpdeviceused')) ?></th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($devices as $device) : ?>
                                        <tr>
                                            <td>
                                                <input type="hidden" disabled name="devices[]"
                                                    value="<?= $this->e($device->getID()) ?>" />
                                                <?= $this->e($device->getName()) ?>
                                            </td>
                                            <td>
                                                <?= $this->e(
                                                    date('d.m.Y H:i:s', $device->getUsed() ?? $device->getCreated())
                                                ) ?>
                                            </td>
                                            <td class="centered">
                                                <a href="#" class="ui mini icon button remove-device">
                                                    <i class="trash icon"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                                <tfoot class="full-width">
                                    <tr>
                                        <th colspan="3" class="centered">
                                            <button disabled class="ui primary labeled icon button">
                                                <i class="save icon"></i>
                                                <?= $this->e($intl('profile.totpdevicesave')) ?>
                                            </button>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </form>
                    <?php else : ?>
                        <div class="ui message"><?= $this->e($intl('profile.totpnodevices')) ?></div>
                    <?php endif ?>
                </div>
                <div class="title lefted">
                    <?php if (count($codes)) : ?>
                        <span class="ui mini green circular icon label acc-button">
                            <i class="check icon"></i></span>
                    <?php else : ?>
                        <span class="ui mini red circular icon label acc-button">
                            <i class="remove icon"></i></span>
                    <?php endif ?>
                    <i class="plane icon"></i> <?= $this->e($intl('profile.recoverycodes')) ?>
                </div>
                <div class="content">
                    <?php if (count($codes)) : ?>
                        <?php foreach ($codes as $code) : ?>
                            <?php if ($code->getUsed() !== null && $code->getUsed() !== '0000-00-00 00:00:00') : ?>
                                <s class="code"><?= $this->e($code->getID()) ?></s>
                            <?php else : ?>
                                <span class="code"><?= $this->e($code->getID()) ?></span>
                            <?php endif ?>
                        <?php endforeach ?>
                    <?php else : ?>
                        <div class="ui message"><?= $this->e($intl('profile.nocodes')) ?></div>
                    <?php endif ?>
                    <div class="ui divider"></div>
                    <form method="post" action="<?= $this->e($url('profile/codes')) ?>" class="centered">
                        <button class="ui yellow right labeled icon button">
                            <i class="icon refresh"></i> <?= $this->e($intl('profile.codesgenerate')) ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="ui green center aligned segment">
            <h4 class="dividing icon header"><i class="key icon"></i> <?= $this->e($intl('profile.sessions')) ?></h4>
            <p><?= $this->e($intl('profile.sessionshelp')) ?></p>
            <div class="ui divider"></div>
            <?php if (count($sessions)) : ?>
                <form method="post" class="sessions" action="<?= $this->e($url('profile/sessions')) ?>">
                    <input type="hidden" name="sessions[]" value="" />
                    <table class="ui basic table">
                        <thead>
                            <tr>
                                <th><?= $this->e($intl('profile.sessioncr')) ?></th>
                                <th><?= $this->e($intl('profile.sessionip')) ?></th>
                                <th><?= $this->e($intl('profile.sessionua')) ?></th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session => $sessionData) : ?>
                                <tr>
                                    <td>
                                        <input type="hidden" disabled name="sessions[]"
                                            value="<?= $this->e($session) ?>" />
                                        <?= $this->e(date('d.m.Y H:i:s', $sessionData['cr'])) ?>
                                    </td>
                                    <td><?= $this->e($sessionData['ip'] ?? '') ?></td>
                                    <td><?= $this->e($sessionData['ua'] ?? '') ?></td>
                                    <td class="centered">
                                        <a href="#" class="ui mini icon button remove-session">
                                            <i class="trash icon"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                        <tfoot class="full-width">
                            <tr>
                                <th colspan="4" class="centered">
                                    <button disabled class="ui green labeled icon button">
                                        <i class="save icon"></i> <?= $this->e($intl('profile.sessionssave')) ?>
                                    </button>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </form>
            <?php else : ?>
                <div class="ui message"><?= $this->e($intl('profile.nosessions')) ?></div>
            <?php endif ?>
        </div>
        <div class="ui yellow center aligned segment">
            <h4 class="dividing icon header"><i class="settings icon"></i> <?= $this->e($intl('profile.tokens')) ?></h4>
            <p><?= $this->e($intl('profile.tokenshelp')) ?></p>
            <div class="ui divider"></div>
            <form method="post" action="<?= $this->e($url('profile/token')) ?>">
                <div class="ui action input">
                    <input name="token" placeholder="<?= $this->e($intl('profile.tokensshortname')) ?>" />
                    <button class="ui yellow right labeled icon button">
                        <i class="icon settings"></i> <?= $this->e($intl('profile.tokensgenerate')) ?>
                    </button>
                </div>
            </form>
            <div class="ui divider"></div>
            <?php if (count($tokens)) : ?>
                <p><strong><?= $this->e($intl('profile.tokens')) ?></strong></p>
                <form method="post" class="totp-devices" action="<?= $this->e($url('profile/tokens')) ?>">
                    <input type="hidden" name="tokens[]" value="" />
                    <table class="ui basic table">
                        <thead>
                            <tr>
                                <th><?= $this->e($intl('profile.tokenname')) ?></th>
                                <th><?= $this->e($intl('profile.tokencreated')) ?></th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tokens as $token) : ?>
                                <tr>
                                    <td>
                                        <input type="hidden" disabled name="tokens[]"
                                            value="<?= $this->e($token->getID()) ?>" />
                                        <?= $this->e($token->getName()) ?>
                                    </td>
                                    <td><?= $this->e(date('d.m.Y H:i:s', $token->getCreated())) ?></td>
                                    <td class="centered">
                                        <a href="#" class="ui mini icon button show-token"
                                            data-token="<?= $this->e($token->getData()) ?>"><i class="eye icon"></i></a>
                                        <a href="#" class="ui mini icon button remove-device">
                                            <i class="trash icon"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                        <tfoot class="full-width">
                            <tr>
                                <th colspan="3" class="centered">
                                    <button disabled class="ui yellow labeled icon button">
                                        <i class="save icon"></i> <?= $this->e($intl('profile.tokenssave')) ?>
                                    </button>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </form>
            <?php else : ?>
                <div class="ui message"><?= $this->e($intl('profile.notokens')) ?></div>
            <?php endif ?>
        </div>
    </div>
</div>
</div>

<div class="ui modal" id="token_modal">
    <i class="close icon"></i>
    <div class="ui form">
        <h3 class="dividing header">Token</h3>
        <textarea readonly class="mono"></textarea>
    </div>
</div>

<div class="ui modal" id="certcurrent_modal">
    <i class="close icon"></i>
    <form method="post" class="ui form" action="<?= $this->e($url('profile/certificate')) ?>">
        <textarea readonly name="certificatetext" class="mono"></textarea>
        <div class="ui divider"></div>
        <div class="ui center aligned basic segment">
            <button class="ui purple right labeled icon button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
        </div>
    </form>
</div>
<div class="ui modal" id="certtext_modal">
    <i class="close icon"></i>
    <form method="post" class="ui form" action="<?= $this->e($url('profile/certificate')) ?>">
        <textarea
            name="certificatetext"
            class="mono"
            placeholder="------ BEGIN CERTIFICATE ------"></textarea>
        <div class="ui divider"></div>
        <div class="ui center aligned basic segment">
            <button class="ui purple right labeled icon button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
        </div>
    </form>
</div>
<div class="ui modal" id="certfile_modal">
    <i class="close icon"></i>
    <form method="post" class="ui form" action="<?= $this->e($url('profile/certificate')) ?>">
        <div class="field image-picker">
            <?= $this->insert(
                'webadmin::field/file',
                [ 'field' => new \webadmin\components\html\Field(
                    'file',
                    [ 'name' => 'certificatefile' ],
                    [ 'label' => 'profile.certificatefile', 'picker' => false ]
                ) ]
            ) ?>
        </div>
        <div class="ui divider"></div>
        <div class="ui center aligned basic segment">
            <button class="ui purple right labeled icon button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
        </div>
    </form>
</div>

<div class="ui modal" id="totp_modal">
    <i class="close icon"></i>
    <form method="post" action="<?= $this->e($url('profile/totp')) ?>" class="centered form">
        <p>
            <?= $this->e($intl('profile.totpcode')) ?><br />
            <code><?= $this->e($totp['secret']) ?></code><br /><br />
            <?= $this->e($intl('profile.totpqr')) ?>
        </p>
        <div class="qrcode"><?= str_replace(
            ['style="background: #fff;"', 'style="background: #000;"'],
            ['class="qr-white"', 'class="qr-black"'],
            (string)$totp['qr']
        ); ?></div>
        <div class="ui divider"></div>
        <div class="ui input"><input name="name" placeholder="<?= $this->e($intl('profile.totpname')) ?>" /></div>
        <div class="ui input"><input name="code" placeholder="<?= $this->e($intl('profile.totpinputcode')) ?>" /></div>
        <button class="ui large green right labeled icon button">
            <i class="icon lock"></i>
            <?= $this->e($intl('profile.totpadd')) ?>
        </button>
    </form>
</div>

<style nonce="<?= $this->e($cspNonce) ?>">
.image-picker label { display:none !important; }
::-ms-reveal { display:none }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$('#certfile').on('click', function (e) { e.preventDefault(); $('#certfile_modal').modal('show'); });
$('#certtext').on('click', function (e) { e.preventDefault(); $('#certtext_modal').modal('show'); });
$('#certcurrent').on('click', function (e) {
    e.preventDefault();
    $.get("<?= $this->e($url('cert')) ?>?full=1").done(function (cert) {
        if (!cert.length) {
            alert('<?= $this->e($intl('profile.nocert')) ?>');
        } else {
            $('#certcurrent_modal textarea').val(cert.toString());
            $('#certcurrent_modal').modal('show');
        }
    }).fail(function () { alert('<?= $this->e($intl('profile.nocert')) ?>'); });
});

$('.totp-devices').on('click', '.remove-device', function (e) {
    e.preventDefault();
    $(this).closest('tr').toggleClass('negative').find('input')
        .prop('disabled', !$(this).closest('tr').find('input').prop('disabled'));
    var cnt = $(this).closest('table').find('.negative').length;
    $(this).closest('table').find('tfoot button').prop('disabled', cnt === 0);
});

$('.sessions').on('click', '.remove-session', function (e) {
    e.preventDefault();
    $(this).closest('tr').toggleClass('negative').find('input')
        .prop('disabled', !$(this).closest('tr').find('input').prop('disabled'));
    var cnt = $(this).closest('table').find('.negative').length;
    $(this).closest('table').find('tfoot button').prop('disabled', cnt === 0);
});

$('.totp-add').click(function (e) {
    e.preventDefault();
    $('#totp_modal').modal('show');
});
$('.totps').on('click', '.remove-key', function (e) {
    e.preventDefault();
    $(this).closest('tr').toggleClass('negative').find('input')
        .prop('disabled', !$(this).closest('tr').find('input').prop('disabled'));
    var cnt = $(this).closest('table').find('.negative').length;
    $(this).closest('table').find('tfoot button').prop('disabled', cnt === 0);
});
$('.keys').on('click', '.remove-key', function (e) {
    e.preventDefault();
    $(this).closest('tr').toggleClass('negative').find('input')
        .prop('disabled', !$(this).closest('tr').find('input').prop('disabled'));
    var cnt = $(this).closest('table').find('.negative').length;
    $(this).closest('table').find('tfoot button').prop('disabled', cnt === 0);
});
$('.certificates').on('click', '.remove-certificate', function (e) {
    e.preventDefault();
    $(this).closest('tr').toggleClass('negative').find('input')
        .prop('disabled', !$(this).closest('tr').find('input').prop('disabled'));
});
$('.show-token').click(function (e) {
    e.preventDefault();
    $('#token_modal').find('textarea').val($(this).data('token')).end().modal('show');
});
$('.update-cert').click(function (e) {
    e.preventDefault();
    $.get("<?= $this->e($url('cert')) ?>").done(function (cert) {
        if (!cert.length) {
            alert('<?= $this->e($intl('profile.nocert')) ?>');
        }
        $('[name="certificate"]').val(cert.toString());
    });
});
$('#totp_modal').find('form').submit(function (e) {
    e.preventDefault();
    var name = $(this).find('[name="name"]').removeClass('error').val();
    var code = $(this).find('[name="code"]').removeClass('error').val();
    if (!name.length) {
        $(this).find('[name="name"]').parent().addClass('error');
        alert('<?= $this->e($intl('profile.nokeyname')) ?>');
        return false;
    }
    if (!code.length) {
        $(this).find('[name="code"]').parent().addClass('error');
        alert('<?= $this->e($intl('profile.nocode')) ?>');
        return false;
    }
    $.post(
        '<?= $this->e($url('profile/totp')) ?>',
        {
            code : code,
            name : name
        },
    )
        .done(function () { window.location.reload(); })
        .fail(function () { alert('<?= $this->e($intl('profile.posttotp.wrongcode')) ?>'); });
});
$('[name=avatar]').closest('form').submit(function (e) {
    if ($('[name=avatar_data]').length) {
        return true;
    }
    $('[name=avatar]').after('<input type="hidden" name="avatar_data" value="" />');
    var img = $('[name=avatar]').parent().find('.plupload-complete');
    if (!img) {
        return true;
    }
    var url = img.css('backgroundImage')
        .replace('url(','').replace(')','').replace(/^"|'/,'').replace(/"|'$/,'');
    if (!url) {
        return true;
    }
    e.preventDefault();
    var img = new Image();
    img.onload = function() {
        var canvas = document.createElement('CANVAS');
        var ctx = canvas.getContext('2d');
        canvas.height = 30;
        canvas.width  = 30;
        ctx.fillStyle = "white";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(this, 0, 0, 30, 30);
        var data = canvas.toDataURL('image/jpeg', 0.75);
        $('[name=avatar_data]').val(data);
        $('[name=avatar]').closest('form').submit();
    };
    img.onerror = function () {
        $('[name=avatar_data]').val('');
        $('[name=avatar]').closest('form').submit();
    };
    img.src = url;
});
</script>
<script nonce="<?= $this->e($cspNonce) ?>">
$('.ui.accordion').accordion({
    onOpening : function (item) {
        window.sessionStorage.setItem("profile_totp", (this.index() - 1) / 2);
    }
});
var acc = window.sessionStorage.getItem("profile_totp");
if (acc !== null) {
    $('.ui.accordion').accordion("open", parseInt(acc, 10));
}
</script>

<?php if ($forceTFA) : ?>
<script nonce="<?= $this->e($cspNonce) ?>">
    $('#tfa').next().remove();
    $('#tfa').parent().prev().remove();
    $('#tfa').parent().parent().toggleClass('two one');
    $('.menu-top, .menu-side').hide();
    $('.main-menu > .content').css('padding-left', '1rem');
    $('#tfa').css('max-width', '60rem').css('margin', '0 auto');
</script>
<?php endif ?>

<script nonce="<?= $this->e($cspNonce) ?>">
if ($('.password-reveal').length) {
    $('.password-reveal')
        .css({ 'right':'0', 'left' : 'auto' })
        .on('click', function (e) {
            e.preventDefault();
            $(this).toggleClass('slash')
                .prev().attr('type', $(this).prev().attr("type") === "password" ? "text" : "password");
        })
        .prev().css('paddingRight', '32px')
}
</script>
