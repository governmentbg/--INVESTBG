<?php
/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var string $error
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?php $this->layout('webadmin::master') ?>

<style nonce="<?= $this->e($cspNonce) ?>">
    body {
        background: #e0e0e0;
        min-width: 320px;
    }

    h1 {
        font-size: 1.4em;
        text-align: center;
        margin: 2em 0 0 0;
        color: #8b0000;
        text-shadow: 1px 1px 0 white;
    }

    h2 {
        color: #222;
        font-size: 1.2em;
        margin: 1em 0 1em 0;
        text-align: center;
        text-shadow: 1px 1px 0 white;
    }

    .pre {
        font-family: Monospace;
        margin: 1em auto;
        max-width: 1200px;
        border: 1px solid gray;
        background: white;
        overflow: hidden;
    }

    .pre>strong {
        display: block;
        background: #ebebeb;
        text-align: center;
        border-bottom: 1px solid silver;
        line-height: 2em;
    }

    .pre>.line {
        padding: 0 1em;
        line-height: 2em;
        background: white;
        white-space: pre;
    }

    .pre>.line>code {
        white-space: pre;
    }

    .pre>.line:nth-child(2n) {
        background: #ebebeb;
    }

    .pre>.current {
        background: lightyellow !important;
        position: relative;
        color: #8b0000;
        font-weight: bold;
        box-shadow: 0 0 0px 1px gray;
        z-index: 2;
    }

    .pre>.line>strong {
        float: left;
        text-align: right;
        width: 40px;
        border-right: 1px solid silver;
        padding-right: 1em;
        margin-right: 1em;
    }
    .green { color:green; }
    .gray { color:gray; }
    .navy { color:navy; }
    .red { color:#8b0000; }
</style>

<h1><?= $this->e($intl('common.error.exception')) ?></h1>

<?php if ($config('DEBUG')) : ?>
    <h2><?= $this->e(preg_replace('/, called in.*/', '', $error) ?? '') ?></h2>
    <?php if (isset($e) && $e instanceof \Throwable) : ?>
        <div class="pre">
            <strong><?= $this->e((@$e->getFile()) . ' : ' . (@$e->getLine())) ?></strong>
            <?php
            $file = @file($e->getFile());
            $line = (int)@$e->getLine() - 1;
            if ($file && $line) {
                for ($i = max($line - 5, 0); $i < max($line - 5, 0) + 11; $i++) {
                    if (!isset($file[$i])) {
                        break;
                    }
                    echo '<div class="line ' . ($line === $i ? 'current' : '') . '">';
                    echo '<strong>' . ($i + 1) . '. </strong> ';
                    echo '<code>' . htmlspecialchars(trim($file[$i], "\r\n")) . ' </code>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        <div class="pre">
            <strong>Stack trace</strong>
            <?php
            foreach ($e->getTrace() as $k => $trace) {
                if ($k === 0) {
                    $trace['file'] = @$e->getFile();
                    $trace['line'] = @$e->getLine();
                }
                echo '<div class="line ' . ($k === 0 ? 'current' : '') . '">';
                echo '<code class="green">';
                echo htmlspecialchars(isset($trace['file']) ? $trace['file'] : '');
                echo '</code>';
                echo '<code class="gray"> ' . htmlspecialchars(isset($trace['file']) ? ':' : '') . ' </code>';
                echo '<code class="red">';
                echo htmlspecialchars(isset($trace['line']) ? (string)$trace['line'] : '');
                echo '</code>';
                echo '<code class="gray"> &raquo; </code>';
                echo '<code class="navy">';
                echo (
                    isset($trace['class']) ?
                        htmlspecialchars($trace['class'] . ($trace['type'] ?? '') . $trace['function']) . '()' :
                        htmlspecialchars($trace['function']) . '()'
                );
                echo '</code></div>';
            }
            ?>
        </div>
    <?php endif ?>
<?php endif ?>
