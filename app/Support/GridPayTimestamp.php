<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;

final class GridPayTimestamp
{
    public static function parse(mixed $value): Carbon
    {
        if ($value === null || $value === '') {
            return Carbon::now();
        }

        if (is_int($value) || is_float($value)) {
            return Carbon::createFromTimestamp((int) $value)->timezone(config('app.timezone'));
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return Carbon::now();
            }
            if (preg_match('/^\d{9,12}$/', $trimmed) === 1) {
                return Carbon::createFromTimestamp((int) $trimmed)->timezone(config('app.timezone'));
            }

            return Carbon::parse($trimmed)->timezone(config('app.timezone'));
        }

        return Carbon::now();
    }
}
