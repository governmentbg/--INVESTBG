<?php

declare(strict_types=1);

namespace webadmin\modules\common\ekatte;

enum Region: int
{
    case BLAGOEVGRAD    = 1;
    case BURGAS         = 2;
    case VARNA          = 3;
    case VELIKO_TARNOVO = 4;
    case VIDIN          = 5;
    case VRATSA         = 6;
    case GABROVO        = 7;
    case DOBRICH        = 8;
    case KARDZHALI      = 9;
    case KYUSTENDIL     = 10;
    case LOVECH         = 11;
    case MONTANA        = 12;
    case PAZARDZHIK     = 13;
    case PERNIK         = 14;
    case PLEVEN         = 15;
    case PLOVDIV        = 16;
    case RAZGRAD        = 17;
    case RUSE           = 18;
    case SILISTRA       = 19;
    case SLIVEN         = 20;
    case SMOLYAN        = 21;
    case SOFIA          = 22;
    case SOFIA_OBLAST   = 23;
    case STARA_ZAGORA   = 24;
    case TARGOVISHTE    = 25;
    case HASKOVO        = 26;
    case SHUMEN         = 27;
    case YAMBOL         = 28;

    public static function fromCode(string $code): ?self
    {
        return match (strtoupper($code)) {
            'BLG'   => self::BLAGOEVGRAD,
            'BGS'   => self::BURGAS,
            'VAR'   => self::VARNA,
            'VTR'   => self::VELIKO_TARNOVO,
            'VID'   => self::VIDIN,
            'VRC'   => self::VRATSA,
            'GAB'   => self::GABROVO,
            'DOB'   => self::DOBRICH,
            'KRZ'   => self::KARDZHALI,
            'KNL'   => self::KYUSTENDIL,
            'LOV'   => self::LOVECH,
            'MON'   => self::MONTANA,
            'PAZ'   => self::PAZARDZHIK,
            'PER'   => self::PERNIK,
            'PVN'   => self::PLEVEN,
            'PDV'   => self::PLOVDIV,
            'RAZ'   => self::RAZGRAD,
            'RSE'   => self::RUSE,
            'SLS'   => self::SILISTRA,
            'SLV'   => self::SLIVEN,
            'SML'   => self::SMOLYAN,
            'SFO'   => self::SOFIA_OBLAST,
            'SOF'   => self::SOFIA,
            'SZR'   => self::STARA_ZAGORA,
            'TGV'   => self::TARGOVISHTE,
            'HKV'   => self::HASKOVO,
            'SHU'   => self::SHUMEN,
            'JAM'   => self::YAMBOL,
            default => null,
        };
    }
}
