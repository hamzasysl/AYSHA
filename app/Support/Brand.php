<?php

namespace App\Support;

use App\Models\Setting;

/** Uygulamanın görünen adı. Tamamı Ayarlar > Şirket & Marka sekmesinden değişir. */
class Brand
{
    /** Şirket adı: sol menü, giriş ekranı, sekme başlığı ve banka dosyası. */
    public static function name(): string
    {
        return trim((string) Setting::get('company_name')) ?: 'AYSHA';
    }

    /** Adın altındaki küçük yazı. */
    public static function tagline(): string
    {
        return trim((string) Setting::get('app_tagline'));
    }

    /** Tarayıcı sekmesi başlığı. */
    public static function title(): string
    {
        return trim(self::name().' '.self::tagline());
    }

    /** İzin formunun üst başlığı. */
    public static function leaveFormTitle(): string
    {
        return trim((string) Setting::get('leave_form_title')) ?: self::upper(self::name()).' İZİN FORMU';
    }

    /** Türkçe büyük harf: i → İ. */
    private static function upper(string $v): string
    {
        return mb_strtoupper(str_replace(['i', 'ı'], ['İ', 'I'], $v), 'UTF-8');
    }
}
