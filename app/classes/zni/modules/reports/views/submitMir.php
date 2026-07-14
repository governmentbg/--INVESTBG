<?php

/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 */
?>
<?php
$valueRaw  = $field->getValue();
$value = $valueRaw['data'];
$months = $value['months'] ?? [];
$rows   = $value['rows'] ?? [];
$totals   = $value['totals'] ?? [];
$kids   = $value['kids'] ?? [];
$general_comment   = $value['general_comment'] ?? null;
$totalCols = count($months) + 9;
?>
<h3 class="ui header">Справка МИР – детайлна</h3>

<div class="mir-table-wrapper">
    <table class="ui celled compact striped table mir-table">
        <thead>
            <tr>
                <th>Име</th>
                <th>Статус</th>
                <th>Незаети дни</th>
                <th>Начало</th>
                <th>Край</th>
                <th>Работно място</th>
                <th>НКПД</th>
                <th>КИД</th>

                <?php foreach ($months as $label) : ?>
                    <th><?= $this->e($label) ?></th>
                <?php endforeach; ?>

                <th>Общо заплата</th>
                <th>Общо осигуровки</th>
                <th>Общо месеци</th>
                <th>Разлика</th>
                <?php if ($valueRaw['hideComments']) : ?>
                    <th>Коментар</th>
                <?php endif; ?>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($rows as $row) : ?>
                <tr class="">
                    <td>
                        <?= $this->e($row['fullname']) ?><br>
                        <small style="color:#888">ID: <?= (int)$row['workplace_empl'] ?></small>
                    </td>
                    <td><?= $this->e($row['job_vacancy_label']) ?></td>
                      <?php
                        $days = (int)$row['job_vacancy_days'];
                        $colorClass = '';
                        if ($days < 90) {
                            $colorClass = 'positive';
                        } elseif ($days < 180) {
                            $colorClass = 'orange';
                        } else {
                            $colorClass = 'negative';
                        }
                        ?>
                    <td class="<?= $colorClass ?>"><?= $days ?></td>
                 
                    <td>
                        <?= !empty($row['job_start'])
                            ? date('d.m.Y', strtotime($row['job_start']) ?: 0)
                            : '-' ?>
                    </td>
                    <td>
                        <?= !empty($row['job_end'])
                            ? date('d.m.Y', strtotime($row['job_end']) ?: 0)
                            : '-' ?>
                    </td>
                    <td><?= $this->e($row['workplace_no']) ?></td>
                    <td><?= $this->e($row['nkpd_code']) ?></td>
                    <td>
                        <?php foreach ($kids as $code => $kid) : ?>
                            <?= $this->e($code) ?>
                        <?php endforeach; ?>
                    </td>

                    <?php foreach ($months as $key => $_) : ?>
                        <td><?= number_format($row['salary'][$key] ?? 0, 2) ?></td>
                    <?php endforeach; ?>
                    
                    <td><strong><?= number_format($row['total_salary'], 2) ?></strong></td>
                    <td><?= number_format($row['refund_sum'], 2) ?></td>
                    <td><?= $this->e($row['total_months']) ?></td>
                    <td class="<?= $row['is_mismatch'] ? 'negative' : 'positive' ?>">
                        <?= number_format($row['difference'], 2) ?>
                    </td>

                    <?php if ($valueRaw['hideComments']) : ?>
                        <td>
                            <textarea
                                name="comments[<?= (int)$row['workplace_empl'] ?>]"
                                class="ui small textarea"
                                rows="2"
                                <?= $valueRaw['disableComments'] ? 'disabled="disabled"' : "" ?>
                                placeholder="Коментар..."><?= $this->e($row['comment'] ?? '') ?></textarea>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <tr>
                <?php for ($i = 1; $i <= $totalCols; $i++) : ?>
                    <td></td>
                <?php endfor; ?>
                <td>Общо Заплата</td>
                <td>Общо осигуровки</td>
                <td>Общо месеци</td>
                <td></td>
                <?php if ($valueRaw['hideComments']) : ?>
                    <td></td>
                <?php endif; ?>
            </tr>
            <tr>
                <?php for ($i = 1; $i <= $totalCols; $i++) : ?>
                    <td></td>
                <?php endfor; ?>
                <td><?= $this->e(number_format($totals['total_salary'], 2)) ?></td>
                <td><?= $this->e(number_format($totals['total_insurance'], 2)) ?></td>
                <td><?= $this->e($totals['total_months']) ?></td>
                <td></td>
                <?php if ($valueRaw['hideComments']) : ?>
                    <td></td>
                <?php endif; ?>
            </tr>
            <tr>
                <?php for ($i = 1; $i <= $totalCols; $i++) : ?>
                    <td></td>
                <?php endfor; ?>
                <td>Средна работна заплата</td>
                <td></td>
                <td></td>
                <td></td>
                <?php if ($valueRaw['hideComments']) : ?>
                    <td></td>
                <?php endif; ?>
            </tr>
            <tr>
                <?php for ($i = 1; $i <= $totalCols; $i++) : ?>
                    <td></td>
                <?php endfor; ?>
                <td><?= $this->e(number_format($totals['average_salary'], 2)) ?></td>
                <td></td>
                <td></td>
                <td></td>
                <?php if ($valueRaw['hideComments']) : ?>
                    <td></td>
                <?php endif; ?>
            </tr>
        </tbody>
    </table>
</div>


<div class="ui segment">
    <label><strong>Общ коментар</strong></label>
    <textarea
        name="general_comment"
        class="ui textarea"
        rows="4"
        <?= $valueRaw['disableComments'] ? 'disabled="disabled"' : "" ?>
        placeholder="Общ коментар за отчета..."><?= $this->e(($general_comment) ?? '') ?></textarea>
</div>


<style nonce="<?= $this->e($cspNonce) ?>">
    .mir-table-wrapper {
        position: relative;
        overflow: auto;
        max-height: 70vh;
        border: 1px solid rgba(34, 36, 38, .15);
    }

    .mir-table {
        border-collapse: separate;
        border-spacing: 0;
        width: max-content;
    }

    .mir-table th,
    .mir-table td {
        white-space: nowrap;
        padding: 6px 8px;
        background: #fff;
    }

    .mir-table td.orange {
        background-color: #fff4e5;
    }


    .mir-table thead th {
        position: sticky;
        top: 0;
        background: #f9fafb;
        z-index: 30;
        box-shadow: inset 0 -1px 0 rgba(34, 36, 38, .15);
    }


    .mir-table th:nth-child(1),
    .mir-table td:nth-child(1) {
        min-width: 250px;
    }

    .mir-table th:nth-child(2),
    .mir-table td:nth-child(2) {
        min-width: 100px;
    }

    .mir-table th:nth-child(3),
    .mir-table td:nth-child(3) {
        min-width: 100px;
    }



    .mir-table th:nth-child(1),
    .mir-table td:nth-child(1) {
        position: sticky;
        left: 0;
        z-index: 20;
    }

    .mir-table th:nth-child(2),
    .mir-table td:nth-child(2) {
        position: sticky;
        left: 250px;
        z-index: 20;
    }

    .mir-table th:nth-child(3),
    .mir-table td:nth-child(3) {
        position: sticky;
        left: 350px;
        z-index: 20;
    }



    .mir-table thead th:nth-child(-n+3) {
        z-index: 40;
    }


    .mir-table th:last-child,
    .mir-table td:last-child {
        position: sticky;
        right: 0;
        z-index: 25;
        background: #fff;
        box-shadow: -2px 0 4px rgba(0, 0, 0, 0.06);
    }

    .mir-table thead th:last-child {
        z-index: 45;
    }


    .mir-table textarea {
        min-width: 220px;
        resize: vertical;
    }

    .mir-table tr.negative td {
        background-color: #fff6f6;
    }

    .mir-table td.positive {
        background-color: #fcfff5;
    }
</style>