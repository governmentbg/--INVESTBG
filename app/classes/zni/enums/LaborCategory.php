<?php

namespace zni\enums;

enum LaborCategory: int
{
    case second = 0;
    case third = 1;

    public function label(): string
    {
        return match ($this) {
            self::second => 'Втора категория',
            self::third => 'Трета категория',
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
