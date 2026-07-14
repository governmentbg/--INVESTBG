<?php

declare(strict_types=1);

namespace zni\utility;

use DateTime;

class Helper
{
    public static function emailTemplateReplace(
        array $search,
        array $replace,
        string $text,
    ): string {
        return str_replace($search, $replace, $text);
    }

    public static function storeMail(
        \vakata\database\DBInterface $db,
        \vakata\mail\Mail $message,
    ): array {
        $recp = array_filter(
            array_unique(
                array_merge(
                    $message->getTo(true),
                    $message->getCc(true),
                    $message->getBcc(true)
                )
            )
        );
        $db->table('mails')->insert([
            'added' => date('Y-m-d H:i:s'),
            'recipient' => implode(', ', $recp),
            'subject' => $message->getSubject(),
            'content' => (string)$message
        ]);
        return [ 'good' => $recp, 'fail' => [] ];
    }


    public static function calcYearPeriod(DateTime $start, DateTime $end): array
    {
        $interval = new \DateInterval('P1Y');

        $period = new \DatePeriod($start, $interval, $end);
        $years = [];
        foreach ($period as $date) {
            $years[] = $date->format('Y');
        }
        return $years;
    }

    public static function getMaxIncomeByDate(array $data, DateTime $targetDate, int $category = 0): float
    {
        foreach ($data as $item) {
            $fromDate = new DateTime($item['from_date']);
            $toDate = new DateTime($item['to_date']);

            if ($targetDate >= $fromDate && $targetDate <= $toDate && $item['category'] == $category) {
                return (float) $item['max_income'];
            }
        }

        return 0;
    }

    public static function toDecimal(?string $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        $v = (string)$value;

        $v = str_replace(' ', '', $v);

        $v = str_replace(',', '.', $v);

        $v = preg_replace('/[^0-9.\-]/', '', $v);

        if ($v === '' || $v === '-' || $v === '.') {
            return '0.00';
        }

        return number_format((float)$v, 2, '.', '');
    }
}
