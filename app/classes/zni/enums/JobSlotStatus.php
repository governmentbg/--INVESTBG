<?php

namespace zni\enums;

enum JobSlotStatus: int
{
    case EMPTY                = 1;
    case ACTIVE               = 2;
    case INACTIVE_SHORT_GAP   = 3;
    case INACTIVE_LONG_GAP    = 4;
    case ACTIVE_LONG_GAP      = 5;

    public function label(): string
    {
        return match ($this) {
            self::EMPTY              => 'Няма заето място',
            self::ACTIVE             => 'Активно',
            self::ACTIVE_LONG_GAP    => 'Активно (с голяма пауза)',
            self::INACTIVE_SHORT_GAP => 'Неактивно',
            self::INACTIVE_LONG_GAP  => 'Неактивно',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EMPTY              => 'grey',
            self::ACTIVE             => 'green',
            self::ACTIVE_LONG_GAP    => 'orange',
            self::INACTIVE_SHORT_GAP => 'red',
            self::INACTIVE_LONG_GAP  => 'red',
        };
    }
}
