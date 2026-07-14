<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 */
if (!$field->hasAttr('value') && ($temp = json_decode($field->getValue(), true))) {
    $field->setValue($temp);
}
?>
<?php if (strlen($field->getOption('label', ''))) : ?>
    <label>
        <?php if ($field->getOption('tooltip')) : ?>
            <span 
                data-tooltip="<?= $this->e($intl($field->getOption('tooltip'))) ?>"
                data-inverted="">
                <i class="question circle icon"></i>
            </span>
        <?php endif ?>
        <?= $this->e($intl($field->getOption('label'))) ?>
    </label>
<?php endif ?>
<?php if (is_array($field->getValue()) && count($field->getValue())) : ?>
    <div class="ui comments">
    <?php foreach ($field->getValue() as $comment) : ?>
        <div class="comment">
            <a class="avatar"><i class="ui big orange user icon"></i></a>
            <div class="content">
                <a class="author" href="mailto:<?= $this->e($comment['name']) ?>"><?= $this->e($comment['name']) ?></a>
                <div class="metadata">
                    <?= strtotime($comment['created']) ?
                        date('d.m.Y H:i:s', strtotime($comment['created'])) : ''
                    ?> &bull;
                    <?= $this->e($comment['ip']) ?>
                </div>
                <div class="text"><?= $this->e($comment['comment']) ?></div>
            </div>
        </div>
    <?php endforeach ?>
    </div>
<?php else : ?>
    <p><?= $this->e($intl('common.fields.comments.nocomments')) ?></p>
<?php endif ?>
