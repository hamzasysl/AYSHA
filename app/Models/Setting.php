<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['key', 'value'];

    /** Varsayılanlar: key => [label, default, group] */
    public const DEFAULTS = [
        'meal_allowance' => ['Yemek ücreti (aylık, standart)', 7800, 'allowance'],
        'meal_allowance_retired' => ['Yemek ücreti (aylık, emekli)', 7800, 'allowance'],
        'travel_allowance' => ['Yol ücreti (aylık, standart)', 3628, 'allowance'],
        'travel_allowance_retired' => ['Yol ücreti (aylık, emekli)', 3628, 'allowance'],
        'm2_rate' => ['m² başı fiyat (standart)', 100, 'pricing'],
        'm2_large_rate' => ['m² başı fiyat (büyük alan)', 80, 'pricing'],
        'm2_large_limit' => ['Büyük alan sınırı (m² üstü)', 100, 'pricing'],
        'm2_small_limit' => ['Küçük alan sınırı (m² altı)', 50, 'pricing'],
        'company_name' => ['Şirket adı', 'TTB Turizm', 'company'],
        'bank_corp_code' => ['Banka kurum kodu (Garanti)', '', 'bank'],
        'bank_branch_code' => ['Banka şube kodu', '', 'bank'],
        'bank_account' => ['Maaş ödeme hesabı', '', 'bank'],
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::remember('settings.all', 300, fn () => static::query()->pluck('value', 'key')->all());

        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        return $default ?? (self::DEFAULTS[$key][1] ?? null);
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }

    public static function amount(string $key): float
    {
        return (float) static::get($key);
    }
}
