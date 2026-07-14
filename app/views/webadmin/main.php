<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var array<string,\webadmin\modules\VisualModuleInterface> $modules
 * @var \webadmin\modules\VisualModuleInterface $module
 * @var ?string $helper
 * @var \vakata\http\Uri $url
 * @var \vakata\user\User $user
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::master'); ?>

<?php $this->start('head'); ?>

    <link rel="stylesheet" href="<?= $asset('assets/static/perfect-scrollbar/perfect-scrollbar.css') ?>" />
    <script src="<?= $asset('assets/static/perfect-scrollbar/perfect-scrollbar.min.js') ?>"></script>

    <link rel="stylesheet" href="<?= $asset('assets/plupload/plupload.css') ?>" />
    <script src="<?= $asset('assets/static/plupload/plupload.full.min.js') ?>"></script>
    <script src="<?= $asset('assets/plupload/plupload.js') ?>"></script>

    <link rel="stylesheet" href="<?= $asset('assets/static/leaflet/leaflet.css') ?>" />
    <script src="<?= $asset('assets/static/leaflet/leaflet.js') ?>"></script>

    <link rel="stylesheet" href="<?= $asset('assets/static/jstree/themes/default/style.min.css') ?>" />
    <script src="<?= $asset('assets/static/jstree/jstree.js') ?>"></script>

    <link rel="stylesheet" href="<?= $asset('assets/dtpckr/dtpckr.css') ?>" />
    <script src="<?= $asset('assets/dtpckr/dtpckr.js') ?>"></script>

    <script nonce="<?= $this->e($cspNonce) ?>">window.tinyNonce = "<?= $this->e($cspNonce) ?>";</script>
    <script src="<?= $asset('assets/static/tinymce/tinymce.min.js') ?>"></script>

    <link rel="stylesheet" href="<?= $asset('assets/main.css') ?>" />
    <script src="<?= $asset('assets/validator.js') ?>"></script>
    <script src="<?= $asset('assets/static/jq-tablesort/tablesort.min.js') ?>"></script>
    <script src="<?= $asset('assets/static/moment/moment.min.js') ?>"></script>
    <script src="<?= $asset('assets/static/urijs/URI.min.js') ?>"></script>
    <script src="<?= $asset('assets/main.js') ?>"></script>
    <script nonce="<?= $cspNonce ?>">
    $.fn.dropdown.settings.delay.search = 150;
    $.fn.modal.settings.detachable = true;
    $.fn.modal.settings.centered = false;
    $.fn.popup.settings.inline = false;
    $.fn.popup.settings.movePopup = false;
    $.fn.popup.settings.onVisible = function (module) {
        var o = $(module).offset();
        this.appendTo(document.body).css({
            'top' : $(module).outerHeight() + o.top + 0,
            'bottom' : 'unset',
            'left' : $(module).outerWidth() + o.left - (this.hasClass('right') ? this.outerWidth() - 8 : 0),
            'right' : 'unset'
        });
    };
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            if (!settings.crossDomain) {
                xhr.setRequestHeader('X-CSPNonce', '<?= $cspNonce ?>');
            }
        }
    });
    window.fromLS = '<?= $this->e($intl('common.fromLS')) ?>';
    <?php if ($config('PUSH_NOTIFICATIONS') && is_file($config('STORAGE_KEYS') . '/push_public.txt')) : ?>
    window.onload = function () {
        if (Notification.permission === "default") {
            $('body').one('click', function () {
                Notification.requestPermission().then(perm => {
                    if (Notification.permission === "granted") {
                        registerWorker();
                    }
                });
            });
        } else if (Notification.permission === "granted") {
            registerWorker();
        }
    };
    function registerWorker() {
        navigator.serviceWorker.register(
            "<?= $url('service_worker.js') ?>",
            { scope: "<?= $url->getBasePath() ?>" }
        ).then(function (registration) {
            registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey:
                    `<?=
                        str_replace(
                            ["\r", "\n"],
                            "",
                            @file_get_contents($config('STORAGE_KEYS') . '/push_public.txt') ?: ''
                        )
                        ?>`
            }).then(function (subscription) {
                const user = <?= json_encode(array_keys(json_decode($user->get('push') ?? '[]', true) ?? [])) ?>;
                crypto.subtle.digest('SHA-1', (new TextEncoder()).encode(subscription.endpoint))
                    .then(function (hash) {
                        const sha1 = Array.from(new Uint8Array(hash))
                            .map(v => v.toString(16).padStart(2, '0'))
                            .join('');
                        if (user.indexOf(sha1) === -1) {
                            $.post(
                                "<?= $url("pushnotifications") ?>",
                                { "subscription" : JSON.stringify(subscription) }
                            );
                        }
                    });
            });
        });
    }
    <?php endif ?>
    </script>

<?php $this->stop(); ?>

<div class="main-menu">
    <div class="menu-top">
        <a href="#" class="ui tiny icon button menu-toggle">
            <i class="bars icon"></i>
        </a>
        <div class="ui breadcrumb">
            <?php if ($user->site !== null && count($user->sites) > 1 && isset($user->sites[$user->site])) : ?>
            <div class="ui floating dropdown labeled icon mini button site-dropdown">
                <i class="world icon"></i>
                <span class="text"><?= $this->e($user->sites[$user->site]) ?></span>
                <div class="menu">
                    <div class="ui icon search input">
                    <i class="search icon"></i>
                    <input type="text" placeholder="">
                    </div>
                    <div class="divider"></div>
                    <div class="scrolling menu">
                        <?php foreach ($user->sites as $k => $v) : ?>
                            <div class="item" data-value="<?= $this->e($k) ?>"><?= $this->e($v) ?></div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
            <?php endif ?>
            <?php if ($module->getName() !== 'dashboard') : ?>
                <?php if ($user->site !== null && count($user->sites) > 1 && isset($user->sites[$user->site])) : ?>
                    <i class="right angle icon divider"></i>
                <?php endif ?>
                <a href="<?= $url("") ?>" class="section">
                    <i class="home icon"></i> <?= $this->e($intl('common.home')) ?>
                </a>
                <i class="right angle icon divider"></i>
                <a href="<?= $this->e($url($url->getSegment(0))) ?>" class="section">
                    <i class="ui white <?= $this->e($module->getIcon()) ?> icon"></i>
                    <?= $this->e($intl($module->getName() . '.title')) ?>
                </a>
                <?php if (isset($breadcrumb)) : ?>
                    <i class="right angle icon divider"></i>
                    <span class="active section"><?= $breadcrumb ?></span>
                <?php endif ?>
            <?php else : ?>
                <!-- <div class="active section"><?= $this->e($intl('common.home')) ?></div> -->
            <?php endif ?>
        </div>
        <a class="ui right floated tiny compact red circular icon button floated-button"
            href="<?= $this->e($url($config('LOGIN_URL'))) ?>"
            title="<?= $this->e($intl('common.exit')) ?>">
            <i class="inverted <?= $user->impersonated ? 'spy' : 'power' ?> icon"></i>
        </a>
        <?php if ($config('FEATURE_MESSAGING') && isset($modules['notifications'])) : ?>
        <a data-count="<?= min(99, $user->notifications) ?>"
            class="ui <?= $user->notifications ? 'unread teal' : 'gray' ?> right floated tiny compact
                circular icon button notifications-button floated-button"
            href="<?= $this->e($url('notifications')) ?>"
            title="<?= $this->e($intl('notifications.title')) ?>">
            <i class="inverted mail icon"></i>
        </a>
        <div class="ui flowing popup bottom left transition hidden notifications-popup">
            <div class="notifications-list">
                <br /><br />
                <div class="ui active centered inline loader"></div>
                <br /><br />
            </div>
            <div class="ui divider"></div>
            <p class="centered">
                <a href="<?= $this->e($url('notifications')) ?>" class="ui teal button">
                    <?= $this->e($intl('notifications.all')) ?>
                </a>
            </p>
        </div>
        <?php endif ?>
        <?php if ($config('FEATURE_HELP') && isset($modules['help'])) : ?>
            <?php if ($helper || $user->hasPermission('help')) : ?>
        <a id="helper-show"
            class="ui item right floated tiny compact circular icon button floated-button
                <?= $helper ? 'blue' : 'gray' ?>"
            href="#" title="<?= $this->e($intl('help.show')) ?>">
            <i class="help circle icon"></i>
        </a>
            <?php endif ?>
        <?php endif ?>
        <?php if ($config('TRANSLATIONS') && isset($modules['translation'])) : ?>
            <?php if ($user->hasPermission('translation')) : ?>
        <a id="missing-translations"
            class="ui item right floated tiny compact gray circular icon button floated-button" href="#"
            title="<?= $this->e($intl('translation.missingtitle')) ?>">
            <i class="font icon"></i>
        </a>
            <?php endif ?>
        <?php endif ?>
        <a href="<?= ($user->hasPermission('profile')) ? $this->e($url('profile')) : "#" ?>"
            class="ui item right floated tiny compact <?= $this->e($user->color ?? 'teal') ?>
                circular icon button floated-button profile-button <?= $user->avatar_data ? 'avatar-button' : '' ?>"
            title="<?= $this->e($intl('common.profile') . ' ' . $user->name) ?>">
            <?php
            if ($user->avatar_data) {
                echo '<img class="ui right spaced avatar image" alt="" src="' . $this->e($user->avatar_data) . '">';
            } else {
                echo $this->e(mb_strtoupper(mb_substr($user->name, 0, 1)));
            }
            ?>
        </a>
    </div>
    <div class="menu-side">
        <div class="ui fluid attached inverted vertical menu">
            <div class="item header main-header" title="<?= $this->e($config('VERSION') ?? '') ?>">
                <?= $this->e($intl($config('APPNAME'))) ?>
            </div>
            <div class="ui inverted fluid accordion">
                <?php $parent = null; ?>
                <?php foreach ($modules as $name => $m) : ?>
                    <?php
                    if (!$m->inMenu()) {
                        continue;
                    }
                    if ($m->getParent() && $parent !== $m->getParent()) {
                        if ($parent !== null) {
                            echo '</div>';
                        }
                        $parent = null;
                        if (strlen($m->getParent())) {
                            $parent = $m->getParent();
                            echo '<div class="title" data-title="' . $this->e($intl($parent)) . '">';
                            echo '<div class="header item vertical-menu-header">';
                            echo '<i class="dropdown icon vertical-menu-dropdown"></i> ' . $this->e($intl($parent));
                            echo '</div>';
                            echo '</div>';
                            echo '<div class="content">';
                        }
                    }
                    ?>
                    <a href="<?= $this->e($url($name === 'dashboard' ? '' : $m->getSlug())) ?>"
                        class="item <?= $m === $module ? 'active' : '' ?>">
                        <i class="<?= $this->e($m->getIcon()) ?> icon"></i>
                        <?= $this->e($intl($name . '.title')) ?>
                    </a>
                <?php endforeach ?>
                <?php
                if ($parent !== null) {
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        <br />
    </div>

    <div class="content">
        <?php if ($config('MIDDLEWARE_MAINTENANCE')) : ?>
        <div class="ui warning message">
            <div class="header"><i class="configure icon"></i> <?= $this->e($intl('maintenance.mode')) ?></div>
        </div>
        <?php endif ?>

        <?= $this->section('title') ?>

        <?php if (isset($session) && $session->get('error')) : ?>
            <div class="ui error message">
                <div>
                <?php
                $mess = $session->del('error');
                if (!is_array($mess)) {
                    $mess = [ $mess ];
                }
                foreach ($mess as $i => $v) {
                    echo ($i ? '<br />' : '') . $this->e($intl($v));
                }
                ?>
                </div>
            </div>
        <?php endif ?>
        <?php if (isset($session) && $session->get('removeLS')) : ?>
            <script nonce="<?= $this->e($cspNonce) ?>">
                localStorage.removeItem("<?= $session->del('removeLS') ?>");
            </script>
        <?php endif ?>
        <?php if (isset($session) && $session->get('success')) : ?>
            <div class="ui positive message">
                <div>
                <?php
                $mess = $session->del('success');
                if (!is_array($mess)) {
                    $mess = [ $mess ];
                }
                foreach ($mess as $i => $v) {
                    echo ($i ? '<br />' : '') . $this->e($intl($v));
                }
                ?>
                </div>
            </div>
        <?php endif ?>

        <?php if ($config('FEATURE_HELP') && isset($modules['help'])) : ?>
        <div class="ui blue icon hidden message" id="helper">
            <i class="help circle icon"></i>
            <div class="content">
                <?= $helper ?>
            </div>
            <?php if ($user->hasPermission('help')) : ?>
                <a href="#" class="ui right floated orange icon button" id="helper-edit">
                    <i class="pencil icon"></i>
                </a>
            <?php endif ?>
            <script nonce="<?= $this->e($cspNonce) ?>">
            $('#helper-show')
                .click(function (e) {
                    e.preventDefault();
                    $('#helper').toggleClass('hidden');
                });
            </script>
        </div>
        <?php endif ?>

        <?= $this->section('content') ?>
    </div>
</div>

<link rel="stylesheet" href="<?= $asset('assets/darkroom/darkroom.css') ?>" />
<script src="<?= $asset('assets/static/fabric/fabric.js') ?>"></script>
<script src="<?= $asset('assets/darkroom/darkroom.js') ?>"></script>
<script nonce="<?= $this->e($cspNonce) ?>">
$('.menu-top .site-dropdown').dropdown({
    onChange : function (v) {
        document.cookie = "" +
            encodeURIComponent('<?= $config('APPNAME_CLEAN') ?>_SITE') + "=" + encodeURIComponent(v) + "; "+
            "expires=Fri, 31 Dec 9999 23:59:59 GMT; path=" + '<?= $url->getBasePath() ?>';
        window.location.reload();
    }
});
$('.menu-side .site-dropdown').dropdown({
    onChange : function (v) {
        document.cookie = "" +
            encodeURIComponent('<?= $config('APPNAME_CLEAN') ?>_SITE') + "=" + encodeURIComponent(v) + "; "+
            "expires=Fri, 31 Dec 9999 23:59:59 GMT; path=" + '<?= $url->getBasePath() ?>';
        window.location.reload();
    }
});
</script>

<div id="picker-modal" class="ui fullscreen modal"></div>

<?php if ($config('FEATURE_HELP') && isset($modules['help']) && $user->hasPermission('help')) : ?>
    <div id="helper-modal" class="ui modal">
        <form class="ui form validate-form" method="post"
            action="<?= $this->e($url('help')) ?>">
            <div class="ui inverted dimmer">
                <div class="content">
                    <div class="center">
                        <div class="ui text loader dimmer-message dimmer-message-load">
                            <?= $this->e($intl('common.pleasewait')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                echo $this->insert(
                    'webadmin::field/hidden',
                    [
                        'field' => new \webadmin\components\html\Field(
                            'hidden',
                            [ 'name' => 'url', 'value' => $url->getRealPath() ]
                        )
                    ]
                );
            ?>
            <div class="field">
                <?php
                    echo $this->insert(
                        'webadmin::field/richtext',
                        [
                            'field' => new \webadmin\components\html\Field(
                                'richtext',
                                [ 'id' => 'helper_content', 'name' => 'helper_content', 'value' => $helper ?? '' ],
                                ['label' => '' ]
                            )
                        ]
                    );
                ?>
            </div>
            <div class="ui section divider"></div>
            <div class="ui center aligned olive secondary segment">
                <button class="ui olive icon labeled submit button">
                    <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
                </button>
                <div class="ui basic cancel button">
                    <?= $this->e($intl('common.cancel')) ?>
                </div>
            </div>
        </form>
    </div>
    <script nonce="<?= $this->e($cspNonce) ?>">
    $('#helper-modal .cancel').on('click', function () {
        $(this).closest('.modal').modal('hide');
    });
    $(function () {
        //if (!!window.performance && window.performance.navigation.type === 2) {
        //    window.location.reload();
        //}
        $('#helper-modal').find('form').submit(function (e) {
            if (!e.isDefaultPrevented()) {
                e.preventDefault();
                $.post($(this).attr('action'), $(this).serialize())
                    .always(function () {
                        $('#helper-modal').modal('hide');
                    });
            }
        });
        $("#helper-modal").modal();
        $('#helper-edit')
            .click(function (e) {
                e.preventDefault();
                $('#helper-modal').modal('show');
            });
    });
    </script>
<?php endif ?>

<?php if ($config('TRANSLATIONS') && isset($modules['translation'])) : ?>
    <?php
    $translations = ['used' => [], 'missing' => []];
    foreach ($intl->getUsed(false) as $k => $v) {
        $k = (string)$k;
        if ($k === '') {
            continue;
        }
        if ($v === null || mb_strtolower($k) === mb_strtolower($v)) {
            $translations['missing'][$k] = $k;
        } else {
            $translations['used'][$k] = $v;
        }
    }
    ?>
    <?php if ($user->hasPermission('translation')) : ?>
    <div id="missing-translations-modal" class="ui modal">
        <div class="header"><?= $this->e($intl('translation.title')) ?></div>
        <div class="scrolling content">
            <form class="ui form validate-form" method="post"
            action="<?= $this->e($url('translation/missing')) ?>">
                <div class="ui inverted dimmer">
                    <div class="content">
                        <div class="center">
                            <div class="ui text loader dimmer-message dimmer-message-load">
                                <?= $this->e($intl('common.pleasewait')) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (count($translations['missing'])) : ?>
                    <p><?= $this->e($intl('translation.missingdescription')) ?></p>
                    <?php
                    ksort($translations['missing']);
                    foreach ($translations['missing'] as $t) {
                        echo '<div class="two fields">';
                        echo '<div class="ui field">';
                        echo '<div class="ui input">';
                        echo '<input name="keys[]" readonly value="' . $this->e((string)$t) . '" />';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="ui field">';
                        echo '<div class="ui input">';
                        echo '<input name="values[]" value="" />';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                    <div class="ui section divider"></div>
                <?php endif ?>
                <?php if (count($translations['used'])) : ?>
                    <p><?= $this->e($intl('translation.useddescription')) ?></p>
                    <?php
                    ksort($translations['used']);
                    foreach ($translations['used'] as $t => $tt) {
                        echo '<div class="two fields">';
                        echo '<div class="ui field">';
                        echo '<div class="ui input">';
                        echo '<input name="keys[]" readonly value="' . $this->e((string)$t) . '" />';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="ui field">';
                        echo '<div class="ui input">';
                        echo '<input name="values[]" value="' . $this->e($tt) . '" />';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                <?php endif ?>
                <div class="ui center aligned olive secondary segment">
                    <button class="ui olive icon labeled submit button">
                        <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
                    </button>
                    <div class="ui basic cancel button">
                        <?= $this->e($intl('common.cancel')) ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script nonce="<?= $this->e($cspNonce) ?>">
    $('#missing-translations-modal .cancel').on('click', function () {
        $(this).closest('.modal').modal('hide');
    });
    $(function () {
        //if (!!window.performance && window.performance.navigation.type === 2) {
        //    window.location.reload();
        //}
        $('#missing-translations-modal').find('form').on('submit', function (e) {
            if (!e.isDefaultPrevented()) {
                e.preventDefault();
                $.post($(this).attr('action'), $(this).serialize())
                    .done(function () {
                        $('#missing-translations').removeClass('olive').addClass('gray');
                    })
                    .always(function () {
                        $('#missing-translations-modal').find('form').find('.dimmer').dimmer('hide');
                        $('#missing-translations-modal').modal('hide');
                    });
            }
        });
    });
    </script>

        <?php if (count($translations['missing'])) : ?>
        <script nonce="<?= $this->e($cspNonce) ?>">
        $(function () {
            $('#missing-translations').removeClass('gray').addClass('olive')
                .click(function (e) {
                    e.preventDefault();
                    $('#missing-translations-modal').modal('show');
                    $('#missing-translations-modal form')[0].reset();
                });
        });
        </script>
        <?php else : ?>
        <script nonce="<?= $this->e($cspNonce) ?>">
        $(function () {
            $('#missing-translations').removeClass('olive').addClass('gray')
                .click(function (e) {
                    e.preventDefault();
                    $('#missing-translations-modal').modal('show');
                    $('#missing-translations-modal form')[0].reset();
                });
        });
        </script>
        <?php endif ?>
    <?php endif ?>
<?php endif ?>
