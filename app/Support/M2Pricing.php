<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Ofis temizlik fiyatı (m² başına). Tutarlar Ayarlar > m² Fiyatları'ndan gelir:
 *  - büyük alan sınırına kadar (varsayılan 100 m²) standart fiyat (100 ₺/m²)
 *  - sınır üstü büyük alan fiyatı (80 ₺/m²)
 *  - küçük alan sınırı altı (50 m²) yine standart fiyat, sadece "küçük alan" diye işaretlenir.
 */
class M2Pricing
{
    /** @return array{rate: float, large_rate: float, large_limit: float, small_limit: float} */
    public static function rates(): array
    {
        return [
            'rate' => Setting::amount('m2_rate'),
            'large_rate' => Setting::amount('m2_large_rate'),
            'large_limit' => Setting::amount('m2_large_limit'),
            'small_limit' => Setting::amount('m2_small_limit'),
        ];
    }

    /** @return array{m2: float, rate: float, total: float, small: bool, large: bool} */
    public static function calculate(float $m2): array
    {
        $r = self::rates();
        $m2 = max(0, $m2);
        $large = $m2 > $r['large_limit'];
        $rate = $large ? $r['large_rate'] : $r['rate'];

        return [
            'm2' => $m2,
            'rate' => $rate,
            'total' => round($m2 * $rate, 2),
            'small' => $m2 > 0 && $m2 < $r['small_limit'],
            'large' => $large,
        ];
    }
}
