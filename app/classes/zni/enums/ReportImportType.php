<?php

namespace zni\enums;

enum ReportImportType: int
{
    case report  = 0;
    case pdf = 1;

    public function label(): string
    {
        return match ($this) {
            self::report  => 'Отчет',
            self::pdf => 'Декларация',
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
