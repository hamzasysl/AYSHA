<?php

namespace App\Support;

/**
 * Temizlik fiyatı (m² başına):
 *  - 100 m²'ye kadar 100 ₺/m²  (50 m² → 5.000 ₺, 100 m² → 10.000 ₺)
 *  - 100 m² üstü 80 ₺/m²       (101 m² → 8.080 ₺)
 *  - 50 m² altı da 100 ₺/m² ama "küçük alan" diye işaretlenir.
 */
class M2Pricing
{
    public const SMALL_LIMIT = 50;

    public const LARGE_LIMIT = 100;

    public const RATE = 100;

    public const LARGE_RATE = 80;

    /** @return array{m2: float, rate: int, total: float, small: bool, large: bool} */
    public static function calculate(float $m2): array
    {
        $m2 = max(0, $m2);
        $large = $m2 > self::LARGE_LIMIT;
        $rate = $large ? self::LARGE_RATE : self::RATE;

        return [
            'm2' => $m2,
            'rate' => $rate,
            'total' => round($m2 * $rate, 2),
            'small' => $m2 > 0 && $m2 < self::SMALL_LIMIT,
            'large' => $large,
        ];
    }
}
