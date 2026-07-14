<?php

/**
 * @var \vakata\views\View $this
 * @var array<string,mixed> $attrs
 * @var \vakata\intl\Intl $intl
 */
if (!isset($skip) || !is_array($skip)) {
    $skip = [];
}
if (!isset($translate) || !is_array($translate)) {
    $translate = [];
}

foreach ($attrs as $k => $v) {
    if (in_array($k, $skip)) {
        continue;
    }
    if (in_array($k, $translate) && is_string($v)) {
        $v = $intl($v);
    }
    if (is_array($v) || is_object($v)) {
        echo $this->e($k) . '=\'';
        echo json_encode(
            $v,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT
        ) . '\' ';
    } else {
        echo $this->e($k) . '="' . $this->e($v ?? '') . '" ';
    }
}
