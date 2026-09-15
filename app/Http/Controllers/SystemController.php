<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Sunucuda terminal olmadığı için güncelleme sonrası bakım işleri (veritabanı
 * güncellemesi, önbellek temizliği) Ayarlar > Sistem sekmesinden yapılır.
 */
class SystemController extends Controller
{
    /** Ayarlar sayfasındaki Sistem sekmesi için durum bilgisi. */
    public static function status(): array
    {
        $pending = [];
        $error = null;
        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles([database_path('migrations')]);
            $ran = $migrator->getRepository()->repositoryExists() ? $migrator->getRepository()->getRan() : [];
            $pending = array_values(array_diff(array_keys($files), $ran));
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'db' => config('database.connections.'.config('database.default').'.database'),
            'pending' => $pending,
            'error' => $error,
            'debug' => (bool) config('app.debug'),
            'env' => config('app.env'),
        ];
    }

    /** Veritabanı güncellemelerini uygular ve önbelleği temizler. */
    public function migrate(): RedirectResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $out = trim(preg_replace('/\s+/', ' ', Artisan::output()));
            $this->flushCaches();

            return redirect()->route('settings.edit', ['tab' => 'system'])
                ->with('success', 'Veritabanı güncellemesi tamamlandı. '.($out !== '' ? mb_substr($out, 0, 300) : 'Yeni güncelleme yoktu.'));
        } catch (\Throwable $e) {
            return redirect()->route('settings.edit', ['tab' => 'system'])->with('error', 'Güncelleme başarısız: '.$e->getMessage());
        }
    }

    /** Sadece önbellek temizliği. */
    public function clearCache(): RedirectResponse
    {
        try {
            $this->flushCaches();

            return redirect()->route('settings.edit', ['tab' => 'system'])->with('success', 'Önbellek temizlendi.');
        } catch (\Throwable $e) {
            return redirect()->route('settings.edit', ['tab' => 'system'])->with('error', 'Temizlenemedi: '.$e->getMessage());
        }
    }

    private function flushCaches(): void
    {
        try {
            Cache::flush();
        } catch (\Throwable) {
            // önbellek sürücüsü sorun çıkarırsa görmezden gel
        }
        foreach (['config:clear', 'view:clear', 'route:clear'] as $cmd) {
            try {
                Artisan::call($cmd);
            } catch (\Throwable) {
            }
        }
    }
}
