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

    /** Ayarlar sayfası açılmasa bile bakım işlerine ulaşılabilen sade sayfa. */
    public function index()
    {
        return view('system.index');
    }

    /** Veritabanı güncellemelerini uygular ve önbelleği temizler. */
    public function migrate(): RedirectResponse
    {
        try {
            $before = count(self::status()['pending']);
            Artisan::call('migrate', ['--force' => true]);
            Artisan::output();
            $this->flushCaches();

            // Ekrana teknik ayrıntı (dosya adı, hata metni) yazma; ayrıntı log'a düşer.
            return redirect()->route('settings.edit', ['tab' => 'system'])
                ->with('success', $before > 0 ? 'Güncellemeler uygulandı.' : 'Her şey zaten güncel.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('settings.edit', ['tab' => 'system'])->with('error', 'Güncelleme tamamlanamadı, lütfen tekrar deneyin.');
        }
    }

    /** Sadece görünüm/önbellek tazeleme. */
    public function clearCache(): RedirectResponse
    {
        try {
            $this->flushCaches();

            return redirect()->route('settings.edit', ['tab' => 'system'])->with('success', 'Sayfalar yenilendi.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('settings.edit', ['tab' => 'system'])->with('error', 'Yenilenemedi, lütfen tekrar deneyin.');
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
