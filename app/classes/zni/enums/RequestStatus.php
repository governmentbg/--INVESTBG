<?php

namespace zni\enums;

enum RequestStatus: int
{
    case Pending  = 0;
    case Approved = 1;
    case Refused  = 2;

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Чакаща',
            self::Approved => 'Одобрена',
            self::Refused  => 'Отказана',
        };
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
