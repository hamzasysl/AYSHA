<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ExpenseCategory extends Model
{
    protected $fillable = ['slug', 'label', 'icon', 'color', 'employee_based', 'is_system', 'sort'];

    protected function casts(): array
    {
        return ['employee_based' => 'boolean', 'is_system' => 'boolean'];
    }

    /** Ayarlar formunda seçilebilen ikonlar ve renkler. */
    public const ICONS = ['fa-receipt', 'fa-file-medical', 'fa-hand-holding-dollar', 'fa-utensils', 'fa-bus', 'fa-spray-can-sparkles', 'fa-shirt', 'fa-car', 'fa-gas-pump', 'fa-graduation-cap', 'fa-gift', 'fa-shield-halved', 'fa-screwdriver-wrench', 'fa-phone', 'fa-house', 'fa-briefcase', 'fa-cart-shopping', 'fa-truck', 'fa-bolt', 'fa-star'];
    public const COLORS = ['brand' => 'Mavi', 'emerald' => 'Yeşil', 'amber' => 'Sarı', 'rose' => 'Kırmızı', 'violet' => 'Mor', 'sky' => 'Açık mavi', 'orange' => 'Turuncu', 'slate' => 'Gri'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('expense_categories'));
        static::deleted(fn () => Cache::forget('expense_categories'));
    }

    /** slug => [label, icon, color, employee_based] (sıralı, önbellekli) */
    public static function map(): array
    {
        return Cache::remember('expense_categories', 300, fn () => static::query()->orderBy('sort')->orderBy('id')->get()
            ->mapWithKeys(fn ($c) => [$c->slug => ['label' => $c->label, 'icon' => $c->icon, 'color' => $c->color, 'employee_based' => $c->employee_based]])
            ->all());
    }

    public static function makeSlug(string $label): string
    {
        $base = Str::slug(str_replace(['ı', 'İ', 'ş', 'Ş', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç'], ['i', 'i', 's', 's', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c'], $label), '_') ?: 'kategori';
        $slug = $base; $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'_'.$i++;
        }

        return $slug;
    }
}
