<?php

/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var \vakata\http\Request $req
 */
?>
<?php
    $value = $field->getValue();
    $form = $field->getForm();
    $readonly = !is_null($form) ? ($form->getContext('type') == 'read') : false;
?>
<h3><?= $intl('maintenancereports.empl_persons') ?></h3>

<table class="ui celled table">
    <thead>
        <tr>
            <th><?= $this->e($intl('maintenancereports.empl_persons.job_number')) ?></th>
            <th><?= $this->e($intl('maintenancereports.empl_persons.nkpd_code')) ?></th>
            <th><?= $this->e($intl('maintenancereports.empl_persons.nkpd_name')) ?></th>
            <th><?= $this->e($intl('maintenancereports.empl_persons.fullname')) ?></th>
            <th><?= $this->e($intl('maintenancereports.empl_persons.contract_start_date')) ?></th>
            <th><?= $this->e($intl('maintenancereports.empl_persons.last_term')) ?></th>
            <th><?= $this->e($intl('maintenancereports.empl_persons.job_start_date')) ?></th>
            <th><?= $this->e($intl('maintenancereports.empl_persons.job_end_date')) ?></th>
            <th><?= $this->e($intl('maintenancereports.empl_persons.unsupported_pm')) ?></th>
            <th><?= $this->e($intl('maintenancereports.empl_persons.refund_sum')) ?></th>
            <?php if ($value['hideComments']) : ?>
                <th><?= $this->e($intl('maintenancereports.columns.persons_comments')) ?></th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($value['persons'] as $position) : ?>
            <?php
            $personsCount = count($position['persons']);
            $rowspan = ($personsCount > 0) ? $personsCount : 1;
            ?>
            <tr <?= $position['unsupported'] ? "class=\"error\"" : "" ?>>
                <td rowspan="<?= $rowspan ?>">
                    <?= $this->e($position['job_number']) ?>
                </td>
                <td rowspan="<?= $rowspan ?>">
                    <?= $value['kids']; ?>
                </td>
                <td rowspan="<?= $rowspan ?>">
                    <?= $this->e($position['nkpd_code'] . ', ' . $position['nkpd_name']) ?>
                </td>

                <?php if ($personsCount > 0) : ?>
                    <?php foreach ($position['persons'] as $index => $p) : ?>
                        <?php if ($index > 0) : ?>
            <tr <?= $position['unsupported'] ? "class=\"error\"" : "" ?>>
                        <?php endif; ?>

            <input type="hidden" name="persons[]"
                value="<?= $this->e($p['workplace_empl']) ?>" />

            <td><?= $this->e($p['name']) ?></td>
            <td>
                        <?= $p['contract_start_date']
                            ? $this->e(date('d.m.Y', strtotime($p['contract_start_date']) ?: null)) : "" ?>
            </td>
            <td><?= $this->e($p['last_term'] ?? "") ?></td>
            <td>
                        <?= $p['start_date'] ? $this->e(date('d.m.Y', strtotime($p['start_date']) ?: null)) : "" ?>
            </td>
            <td>
                        <?= $p['end_date'] ? $this->e(date('d.m.Y', strtotime($p['end_date']) ?: null)) : "" ?>
            </td>
            <td>
                        <?= $position['unsupported'] ?
                            $this->e($intl('maintenancereports.empl_persons.unsupported_pm'))
                            : ""
                        ?>
            </td>
            <td><?= $this->e($p['refund_sum'] ?? "") ?></td>
                        <?php if ($value['hideComments']) : ?>
                <td>
                    <input type="text" maxlength="500"
                        name="persons_comments[<?= $this->e($p['workplace_empl']) ?>]"
                        value="<?= $this->e($p['comment'] ?? "") ?>"
                            <?= $readonly ? "readonly='readonly'" : "" ?>
                            <?= $value['disableComments'] ? "disabled='disabled' " : "" ?> />
                </td>
                        <?php endif; ?>

                        <?php if ($index > 0) : ?>
            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else : ?>
    <td colspan="7"><?= $intl('maintenancereports.no_empl_persons') ?></td>
                <?php endif; ?>
</tr>
        <?php endforeach; ?>
    </tbody>
</table>