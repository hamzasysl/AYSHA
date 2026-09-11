<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Ayarlar'dan yönetilen sabit listeler: görevler, bankalar, izin türleri. */
class ListItem extends Model
{
    protected $fillable = ['type', 'slug', 'label', 'meta', 'is_system', 'sort'];

    public const TYPES = [
        'position' => ['label' => 'Görevler', 'desc' => 'Personel kartındaki görev seçenekleri', 'icon' => 'fa-briefcase'],
        'bank' => ['label' => 'Bankalar', 'desc' => 'Personel kartındaki banka listesi', 'icon' => 'fa-building-columns'],
        'leave_type' => ['label' => 'İzin Türleri', 'desc' => 'İzin kaydında seçilen nedenler ve yıllık izinden düşme kuralı', 'icon' => 'fa-calendar-days'],
    ];

    protected function casts(): array
    {
        return ['meta' => 'array', 'is_system' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn ($m) => Cache::forget('list_items.'.$m->type));
        static::deleted(fn ($m) => Cache::forget('list_items.'.$m->type));
    }

    /** Verilen tipin sıralı öğeleri (önbellekli). */
    public static function of(string $type): \Illuminate\Support\Collection
    {
        // Önbellekte model değil düz dizi tutulur (dosya önbelleğinde sınıf çözümleme sorunu yaşanmasın)
        $rows = Cache::remember('list_items.'.$type, 300, fn () => static::where('type', $type)->orderBy('sort')->orderBy('id')->get()
            ->map(fn ($i) => ['id' => $i->id, 'slug' => $i->slug, 'label' => $i->label, 'meta' => $i->meta ?? [], 'is_system' => (bool) $i->is_system])->all());

        return collect($rows);
    }

    /** Sadece etiketler (görev, banka). */
    public static function labels(string $type): array
    {
        return static::of($type)->pluck('label')->all();
    }

    public static function makeSlug(string $type, string $label): string
    {
        if ($type !== 'leave_type') {
            return $label;
        }
        $base = Str::slug(str_replace(['ı', 'İ', 'ş', 'Ş', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç'], ['i', 'i', 's', 's', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c'], $label), '_') ?: 'tur';
        $slug = $base; $i = 2;
        while (static::where('type', $type)->where('slug', $slug)->exists()) {
            $slug = $base.'_'.$i++;
        }

        return $slug;
    }
}
