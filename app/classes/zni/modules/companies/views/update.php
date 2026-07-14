<?php

/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var array $pkey
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 */
?>
<?= $this->insert('crud::update', $this->data()); ?>
<?php $isRead = $this->data()['form']->getField('id')->getAttr('readonly'); ?>
<?php if ($isRead !== "readonly") : ?>
    <?= $this->insert('companies::eik_check'); ?>
<?php endif;
