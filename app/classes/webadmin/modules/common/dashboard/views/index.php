<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var array<string> $errors
 * @var array<string,\webadmin\modules\VisualModuleInterface> $modules
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::main'); ?>

<div class="dashboard-div">
<?php if (count($errors)) : ?>
<div class="ui icon error message">
    <i class="warning sign icon"></i>
    <div class="content">
        <div class="header"><?= $this->e($intl('admin_warnings')) ?></div>
        <ul>
        <?php foreach ($errors as $e) : ?>
            <li><?= $this->e($intl($e)) ?></li>
        <?php endforeach ?>
        </ul>
    </div>
</div>
<?php endif ?>

<div class="ui stackable cards">
    <?php
    $parent = '';
    $k = 0;
    ?>
    <?php foreach ($modules as $name => $module) : ?>
        <?php
        if (!$module->onDashboard()) {
            continue;
        }
        if ($parent !== $module->getParent()) {
            echo '</div>';
            echo '<br /><br />';
            $parent = $module->getParent();
            echo '<h3 class="ui grey horizontal left aligned clearing divider header">';
            echo '<i class="small cube icon"></i>&nbsp;&nbsp;<span>' . $this->e($intl($parent)) . '</span>';
            echo '</h3>';
            echo '<div class="ui stackable cards">';
        }
        ?>
        <a href="<?= $this->e($url($module->getSlug())) ?>"
            class="ui <?= $module->getColor() ?> card">
            <span class="content">
                <span class="ui <?= $module->getColor() ?> icon header"
                    href="<?= $this->e($url($module->getSlug())) ?>">
                    <i class="<?= $module->getColor() ?> <?= $module->getIcon() ?> icon"></i>
                    <span class="content">
                        <span class="ui <?= $module->getColor() ?> header">
                            <?= $this->e($intl($name . '.title')) ?>
                        </span>
                    </span>
                </span>
            </span>
            <span class="ui extra content ">
                <?= $this->e($intl($name . '.description')) ?>
            </span>
        </a>
    <?php endforeach ?>
    </div><br /><br /><br />
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.cards .header { padding-top:2rem; }
.cards .extra.content { text-align:center !important; background:#fafafa !important; }
.divider.header { text-transform: uppercase; }
.dashboard-div { padding:2rem; }
/* .stackable.cards .header .content { display:block; }
.module-link { display:block !important; padding:2rem 0 1rem 0 !important; }
.module-link .content { padding:0.5rem 1rem !important; }
.module-link .sub.header { padding-top:0.2rem !important; display:none !important; } */
.dashboard-div .cards .card { border: 1px solid #ddd !important; }
</style>
