<?php

// visual installer for a new system

declare(strict_types=1);

use vakata\random\Generator;
use webadmin\App;
use webadmin\Jobs;

set_time_limit(0);

if (!is_file(__DIR__ . '/.env.php')) {
    file_put_contents(
        __DIR__ . '/.env.php',
        '<?' . 'php return null;' . "\n"
    );
}

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    @ob_end_flush();
    @ob_implicit_flush(true);
    echo '<xmp>';
    echo 'Trying to install dependencies ...' . "\n";
    if (!is_dir(__DIR__ . '/tools/composer/') && !is_file(__DIR__ . '/tools/composer.phar')) {
        echo ' - Downloading composer ...';
        $temp = @file_get_contents('https://getcomposer.org/download/2.8.5/composer.phar');
        if ($temp !== false) {
            if (md5($temp) === 'df9878425edae0db14bff16c3ecf7414') {
                @file_put_contents(__DIR__ . '/tools/composer.phar', $temp);
                echo 'Done.' . "\n";
            }
        }
        if (!is_file(__DIR__ . '/tools/composer.phar')) {
            echo 'FAIL!' . "\n";
            die();
        }
    }
    if (!is_dir(__DIR__ . '/tools/composer/')) {
        echo ' - Extracting composer ...';
        $composer = new Phar(__DIR__ . '/tools/composer.phar');
        $composer->extractTo(__DIR__ . '/tools/composer');
        echo 'Done.' . "\n";
    }

    putenv('COMPOSER_HOME=' . __DIR__ . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'composer');
    require_once(__DIR__ . '/tools/composer/vendor/autoload.php');

    // server side
    echo ' - Installing ...';
    $input = new \Symfony\Component\Console\Input\StringInput(
        'install --no-progress -d ' . str_replace('\\', '\\\\', __DIR__)
    );
    $handle = fopen(__DIR__ . '/tools/composer/debug', 'w');
    $output = new \Symfony\Component\Console\Output\StreamOutput($handle);
    $app = new \Composer\Console\Application();
    $app->setAutoExit(false);
    $app->run($input, $output);
    echo 'Done.' . "\n";
    echo "\n\n";
    echo 'Log: ' . "\n";
    echo file_get_contents(__DIR__ . '/tools/composer/debug') . "\n\n";
    fclose($handle);

    // client side
    echo ' - Installing client side ...';
    $input = new \Symfony\Component\Console\Input\StringInput(
        'run-script frontend-dependencies -d ' . str_replace('\\', '\\\\', __DIR__)
    );
    $handle = fopen(__DIR__ . '/tools/composer/debug', 'w');
    $output = new \Symfony\Component\Console\Output\StreamOutput($handle);
    $app = new \Composer\Console\Application();
    $app->setAutoExit(false);
    $app->run($input, $output);
    echo 'Done.' . "\n";
    echo "\n\n";
    echo 'Log: ' . "\n";
    echo file_get_contents(__DIR__ . '/tools/composer/debug') . "\n\n";
    fclose($handle);

    echo '</xmp>';
    echo '<a href=".">Continue &raquo;</a>';
    die();
}

require_once __DIR__ . '/vendor/autoload.php';

if (!is_file(__DIR__ . '/.env')) {
    file_put_contents(
        __DIR__ . '/.env',
        implode("\n", [
            'SIGNATUREKEY = "' . Generator::string(32) . '"',
            'ENCRYPTIONKEY = "' . Generator::string(32) . '"',
            ''
        ])
    );
}

$dir = basename(__DIR__);
$app = App::init();
$jobs = $app->di()->instance(Jobs::class);

if (!isset($_POST['appname']) && isset($_GET['proceed'])) {
    $jobs->migrationsUp();
    $jobs->frontendFix();
    $jobs->frontendSVG();
    $jobs->cacheClean();
    header('Location: ./public/');
    die();
}

if (strpos(file_get_contents(__DIR__ . '/.env'), 'APPNAME') !== false) {
    header('Location: ./public/');
    die();
}

if (isset($_POST['appname'])) {
    $jobs->setup(
        $_POST['appname'],
        $_POST['appname_clean'],
        $_POST['db'],
        $_POST['host'],
        $_POST['host'],
        $_POST['user'],
        $_POST['pass'],
        $_POST['schema'],
        $_POST['features'] ?? []
    );
    header('Location: ' . $_SERVER['REQUEST_URI']);
    die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer</title>
    <link rel="stylesheet" href="./public/assets/static/fomantic-ui-css/semantic.min.css">
    <link rel="stylesheet" href="./public/assets/main.css">
    <script src="./public/assets/static/jquery/jquery.min.js"></script>
    <script src="./public/assets/static/fomantic-ui-css/semantic.min.js"></script>
</head>
<body>
    <div class="ui center aligned grid">
        <div class="column">
            <div class="ui segment">
                <form class="ui form" method="post" action="?proceed=1">
                    <h4 class="ui dividing header">Application name</h4>
                    <div class="two fields">
                        <div class="field">
                            <label>Appname</label>
                            <input type="text" name="appname" placeholder="webadmin"
                                value="<?= htmlspecialchars($dir) ?>">
                        </div>
                        <div class="field">
                            <label>Clean appname</label>
                            <input type="text" name="appname_clean" placeholder="WEBADMIN"
                                value="<?= htmlspecialchars(preg_replace('([^a-z0-9_]+)ui', '_', strtoupper($dir))) ?>"
                                >
                        </div>
                    </div>
                    <h4 class="ui dividing header">Database</h4>
                    <div class="two fields">
                        <div class="field">
                            <label>Engine</label>
                            <select name="db" class="ui fluid dropdown">
                                <option value="postgre" selected>postgreSQL</option>
                                <option value="mysql">mySQL</option>
                                <option value="oracle">Oracle</option>
                                <option value="sqlite">SQLite</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Host</label>
                            <input type="text" name="host" value="127.0.0.1">
                        </div>
                    </div>
                    <div class="field">
                        <label>Name</label>
                        <input type="text" name="schema"
                            value="<?= htmlspecialchars(preg_replace('([^a-z0-9_]+)ui', '_', $dir)) ?>">
                    </div>
                    <div class="two fields">
                        <div class="field">
                            <label>Username</label>
                            <input type="text" name="user" value="root" autocomplete="username">
                        </div>
                        <div class="field">
                            <label>Password</label>
                            <input type="password" name="pass" autocomplete="new-password">
                        </div>
                    </div>
                    <h4 class="ui dividing header">Features</h4>
                    <?php
                    foreach ($app->config()->toArray() as $k => $v) {
                        if (strpos($k, 'FEATURE_') === 0) {
                            $feature = strtoupper(str_replace('FEATURE_', '', $k));
                            echo '<div class="ui checkbox">';
                            echo '<input
                                id="f' . htmlspecialchars($feature) . '"
                                name="features[' . htmlspecialchars($feature) . ']" ';
                            echo ' value="1" type="checkbox" tabindex="0" class="hidden">';
                            echo '<label for="f' . htmlspecialchars($feature) . '">';
                            echo htmlspecialchars($feature);
                            echo '</label>';
                            echo '</div>';
                        }
                    }
                    ?>
                    <h4 class="ui dividing header">&nbsp;</h4>
                    <button class="ui large green button">Save</button>
                </form>
            </div>
        </div>
    </div>
    <style>
    body > .grid > .column { max-width:640px; margin-top:4rem; } 
    body > .grid > .column .segment { padding:2rem; }
    body > .grid > .column .header { text-shadow:1px 1px 0px rgba(255,255,255,0.75); }
    body { background: #cdeb8e; background: linear-gradient(to bottom, #cdeb8e 0%,#9dd34c 100%) !important; }
    .checkbox { margin-right:30px; }
    </style>
</body>
</html>
