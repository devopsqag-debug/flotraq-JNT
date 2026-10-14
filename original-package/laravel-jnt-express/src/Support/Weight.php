<?php

namespace JntExpress\Support;

class Weight
{
    /** Convert to kilograms. Unknown units are treated as kg. */
    public static function toKg(float $value, string $unit): float
    {
        return match (strtolower($unit)) {
            'g' => $value / 1000,
            'lb', 'lbs' => $value * 0.45359237,
            'oz' => $value * 0.028349523125,
            default => $value,
        };
    }
}
