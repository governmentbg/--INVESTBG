<?php

/**
 * @var array $data
 */
?>
<?php

$formatDate = static function (string $date): string {
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime((string)$date);
    if (!$timestamp) {
        return '';
    }

    return date('d.m.Y', $timestamp);
};
?>
<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <title>Декларация</title>
</head>

<body style="font-family: dejavusans; font-size: 7.6pt; color: #000000; margin: 0; padding: 0; line-height: 1.15;">
    <div style="width: 100%;">

        <div style="text-align: center; font-size: 11.5pt; font-weight: bold; 
            text-transform: uppercase; margin-bottom: 1px;">
            ДЕКЛАРАЦИЯ
        </div>

        <div style="text-align: center; font-size: 8pt; margin-bottom: 5px;">
            към справка за поддържане на заетост по договор с Министерство на иновациите и растежа
        </div>

        <div style="margin-bottom: 2px;">
            <div style="font-weight: bold; border-bottom: 1px solid #000000; 
                padding-bottom: 1px; margin-bottom: 1px;">
                I. Данни за договора и предприятието
            </div>

            <table cellpadding="2" cellspacing="0" border="1" style="width: 100%; 
                border-collapse: collapse; font-size: 8pt;">
                <tr>
                    <td style="width: 28%; font-weight: bold; background-color: #f3f3f3;">Номер на договор</td>
                    <td style="width: 22%;"><?= $data['contract']['contract_number'] ?? '' ?></td>
                    <td style="width: 28%; font-weight: bold; background-color: #f3f3f3;">Дата на договор</td>
                    <td style="width: 22%;"><?= $formatDate($data['contract']['contract_date'] ?? '') ?></td>
                </tr>
                <tr>
                    <td style="width: 28%; font-weight: bold; background-color: #f3f3f3;">Наименование на фирма</td>
                    <td colspan="3" style="width: 72%;"><?= $data['company']['company_name'] ?? '' ?></td>
                </tr>
                <tr>
                    <td style="width: 28%; font-weight: bold; background-color: #f3f3f3;">ЕИК</td>
                    <td style="width: 22%;"><?= $data['company']['id'] ?? '' ?></td>
                    <td style="width: 28%; font-weight: bold; background-color: #f3f3f3;">Адрес</td>
                    <td style="width: 22%;"><?= $data['company']['company_address'] ?? '' ?></td>
                </tr>
                <tr>
                    <td style="width: 28%; font-weight: bold; background-color: #f3f3f3;">Лице, подаващо отчета</td>
                    <td colspan="3" style="width: 72%;"><?= $data['submitted_by'] ?? '' ?></td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom: 2px;">
            <div style="font-weight: bold; border-bottom: 1px solid #000000; 
                padding-bottom: 1px; margin-bottom: 1px;">
                II. Данни за отчитането
            </div>

            <table cellpadding="2" cellspacing="0" border="1" style="width: 100%; 
                border-collapse: collapse; font-size: 8pt;">
                <tr>
                    <td style="width: 28%; font-weight: bold; background-color: #f3f3f3;">
                        Общ брой работни места
                    </td>
                    <td style="width: 22%;"><?= $data['jobs_count'] ?? '' ?></td>
                    <td style="width: 28%; font-weight: bold; background-color: #f3f3f3;">
                        Обща стойност работни заплати (<?= strtoupper($data['contract']['currency']) ?>)
                    </td>
                    <td style="width: 22%;"><?= $data['total_salaries_eur'] ?? '' ?></td>
                </tr>
                <tr>
                    <td style="width: 28%; font-weight: bold; background-color: #f3f3f3;">
                        Обща стойност осигуровки (<?= strtoupper($data['contract']['currency']) ?>)
                    </td>
                    <td style="width: 22%;"><?= $data['total_social_costs_eur'] ?? '' ?></td>
                    <td style="width: 28%; font-weight: bold; background-color: #f3f3f3;">
                        Отчетен период
                    </td>
                    <td style="width: 22%;">
                        <?= $formatDate($data['report']['date_from'] ?? '') ?> 
                        - <?= $formatDate($data['report']['date_to'] ?? '') ?>
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom: 2px; text-align: justify;">
            <div style="font-weight: bold; border-bottom: 1px solid #000000; 
                padding-bottom: 1px; margin-bottom: 1px;">
                III. Декларативна част
            </div>

            <div style="margin-bottom: 2px;">
                Долуподписаният/ата <b><?= $data['submitted_by'] ?? '' ?></b>
                , в качеството си на представляващ/упълномощено лице на
                <b><?= $data['company']['company_name'] ?? '' ?></b>, 
                ЕИК <b><?= $data['company']['id'] ?? '' ?></b>, декларирам, че настоящата справка 
                за поддържане на заетост се подава
                по Договор № <b><?= $data['contract']['contract_number'] ?? '' ?></b> от <b>
                    <?= $formatDate($data['contract']['contract_date'] ?? '') ?></b> и отчитането на работните места
                е изготвено в съответствие с 
                <b>чл. 4 от Договора – Техническо и финансово отчитане, плащания и обезпечения</b>.
            </div>

            <div style="margin-bottom: 1px;">
                Декларирам, че към отчета са приложени подписани с електронен подпис документи, 
                изисквани от Министерство на иновациите и растежа, а именно:
            </div>

            <div style="margin-left: 8px; margin-bottom: 1px;">
                - Доклад за поддържаната заетост за предходна календарна година;
            </div>
            <div style="margin-left: 8px; margin-bottom: 1px;">
                - Справка за поддържане на заетостта за предходна календарна година, 
                изготвена с натрупване за всяко работно място (от първото назначено лице до последния заместник);
            </div>
            <div style="margin-left: 8px; margin-bottom: 1px;">
                - Справка от НАП за действащите трудови договори към 31 декември на предходна календарна година;
            </div>
            <div style="margin-left: 8px; margin-bottom: 1px;">
                - Документи за годишно отчитане по Закона за статистиката, 
                част от Годишния отчет за дейността на нефинансовите предприятия, 
                съставящи баланс : 1/ Справка за предприятието; 2/ Справка за местните единици 
                (за обекта, в който се осъществява проекта); 3/ Справка за група предприятия; 
                4/ Раздел VI. Нетни приходи от продажби по икономически дейности към Справка за приходите и 
                разходите по видове и икономически дейности към Отчета за приходите и разходите; 
                5/ Отчет за заетите лица, средствата за работна заплата и други разходи за труд за предходна 
                календарна година; Баланс
            </div>
            <div style="font-size: 7.6pt; margin-top: 2px;">
                С настоящото декларирам, че посочените данни и приложените документи са верни, пълни и 
                съответстват на подадения отчет.
            </div>
        </div>

        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin-top: 4px;
             page-break-inside: avoid;">
            <tr>
                <td style="width: 30%; vertical-align: top; font-size: 8pt;">
                    <b>Дата:</b> <?= $formatDate($data['current_date'] ?? '') ?>
                </td>
                <td style="width: 70%; vertical-align: top; font-size: 8pt;">
                    <div style="font-weight: bold; margin-bottom: 2px;">Електронен подпис на подателя:</div>
                    <div style="border: 1px dashed #000000; min-height: 30px; padding: 4px; font-size: 7.6pt;">
                        Подписано с квалифициран електронен подпис от:<br><br>
                        <?= $data['submitted_by'] ?? '' ?>
                    </div>
                </td>
            </tr>
        </table>

    </div>
</body>

</html>