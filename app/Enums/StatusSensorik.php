<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status penglihatan/pendengaran (SKRINING_SENSORIK).
 */
enum StatusSensorik: string
{
    use HasOptions;

    case Normal = 'normal';
    case Gangguan = 'gangguan';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Gangguan => 'Ada Gangguan',
        };
    }
}
