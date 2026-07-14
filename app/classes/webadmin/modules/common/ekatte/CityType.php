<?php

declare(strict_types=1);

namespace webadmin\modules\common\ekatte;

use InvalidArgumentException;

enum CityType: int
{
    case CITY                = 1;
    case VILLAGE             = 2;
    case MONASTERY           = 3;
    case SETTLEMENT_NATIONAL = 4;
    case SETTLEMENT_LOCAL    = 5;
    case CITY_CUSTOM         = 6;

    public static function fromEkAtteType(int $type): self
    {
        return match ($type) {
            1       => self::CITY,
            3       => self::VILLAGE,
            7       => self::MONASTERY,
            default => throw new InvalidArgumentException(),
        };
    }

    public static function fromEkSobrType(int $type): self
    {
        return match ($type) {
            1       => self::SETTLEMENT_NATIONAL,
            2       => self::SETTLEMENT_LOCAL,
            default => throw new InvalidArgumentException(),
        };
    }

    public static function labels(): array
    {
        return [
            self::CITY->value                => 'ekatte.label.city',
            self::VILLAGE->value             => 'ekatte.label.village',
            self::MONASTERY->value           => 'ekatte.label.monastery',
            self::SETTLEMENT_NATIONAL->value => 'ekatte.label.settlement_national',
            self::SETTLEMENT_LOCAL->value    => 'ekatte.label.settlement_local',
            self::CITY_CUSTOM->value         => 'ekatte.label.custom_city',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value];
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::CITY,
            self::CITY_CUSTOM      => 'гр.',
            self::VILLAGE          => 'с.',
            self::MONASTERY        => 'ман.',
            self::SETTLEMENT_NATIONAL,
            self::SETTLEMENT_LOCAL => '',
        };
    }
}
