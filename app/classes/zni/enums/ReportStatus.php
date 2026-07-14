<?php

namespace zni\enums;

enum ReportStatus: int
{
    case Draft = 0;
    case Submitted = 1;
    case UnderReview = 2;
    case Approved = 3;
    case Rejected = 4;
    case ReturnedForCorrection = 5;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Чернова',
            self::Submitted => 'Подаден',
            self::UnderReview => 'В проверка',
            self::Approved => 'Одобрен',
            self::Rejected => 'Отхвърлен',
            self::ReturnedForCorrection => 'За корекция',
        };
    }

    public static function options(): array
    {
        return array_column(
            array_map(fn($c) => [$c->value, $c->label()], self::cases()),
            1,
            0
        );
    }
}
