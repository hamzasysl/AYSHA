<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** Giriş denemeleri: kim, ne zaman, hangi IP ve cihazdan. */
class LoginLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'login', 'successful', 'ip', 'device', 'platform', 'browser', 'user_agent'];

    protected function casts(): array
    {
        return ['successful' => 'boolean', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** İstekten kayıt oluşturur. */
    public static function record(Request $request, ?User $user, string $login, bool $successful): ?self
    {
        // Tablo henüz oluşmadıysa giriş engellenmesin
        if (! Schema::hasTable('login_logs')) {
            return null;
        }

        $agent = (string) $request->userAgent();

        return static::create([
            'user_id' => $user?->id,
            'login' => mb_substr($login, 0, 255),
            'successful' => $successful,
            'ip' => $request->ip(),
            'device' => self::device($agent),
            'platform' => self::platform($agent),
            'browser' => self::browser($agent),
            'user_agent' => mb_substr($agent, 0, 1000),
        ]);
    }

    public static function device(string $a): string
    {
        if (preg_match('/iPad|Tablet|PlayBook|Silk/i', $a)) {
            return 'Tablet';
        }
        if (preg_match('/Mobi|iPhone|iPod|Android.*Mobile|Windows Phone/i', $a)) {
            return 'Telefon';
        }

        return 'Masaüstü';
    }

    public static function platform(string $a): string
    {
        return match (true) {
            (bool) preg_match('/iPhone/i', $a) => 'iPhone',
            (bool) preg_match('/iPad/i', $a) => 'iPad',
            (bool) preg_match('/Android/i', $a) => 'Android',
            (bool) preg_match('/Mac OS X|Macintosh/i', $a) => 'macOS',
            (bool) preg_match('/Windows/i', $a) => 'Windows',
            (bool) preg_match('/CrOS/i', $a) => 'ChromeOS',
            (bool) preg_match('/Linux/i', $a) => 'Linux',
            default => 'Bilinmiyor',
        };
    }

    public static function browser(string $a): string
    {
        return match (true) {
            (bool) preg_match('/Edg\//i', $a) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $a) => 'Opera',
            (bool) preg_match('/YaBrowser/i', $a) => 'Yandex',
            (bool) preg_match('/SamsungBrowser/i', $a) => 'Samsung',
            (bool) preg_match('/Firefox\//i', $a) => 'Firefox',
            (bool) preg_match('/Chrome\//i', $a) => 'Chrome',
            (bool) preg_match('/Safari\//i', $a) => 'Safari',
            default => 'Bilinmiyor',
        };
    }

    public function getDeviceIconAttribute(): string
    {
        return match ($this->device) {
            'Telefon' => 'fa-mobile-screen',
            'Tablet' => 'fa-tablet-screen-button',
            default => 'fa-desktop',
        };
    }

    /** "Masaüstü · Windows · Chrome" */
    public function getDeviceLabelAttribute(): string
    {
        return collect([$this->device, $this->platform, $this->browser])->filter()->reject(fn ($v) => $v === 'Bilinmiyor')->join(' · ') ?: 'Bilinmiyor';
    }
}
