<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('tr');

        // Yetkiler: edit (ekle/düzenle), delete (sil), manage (ayarlar + kullanıcılar)
        Gate::define('edit', fn ($user) => $user->canEdit());
        Gate::define('delete', fn ($user) => $user->canDelete());
        Gate::define('manage', fn ($user) => $user->isManager());

        // /personel/olustur, /personel/5/duzenle
        Route::resourceVerbs(['create' => 'olustur', 'edit' => 'duzenle']);

        // @money(1234.5) -> 1.234,50 ₺
        Blade::directive('money', fn ($expr) => "<?php echo number_format((float) ({$expr}), 2, ',', '.') . ' ₺'; ?>");

        // @date($carbon) -> 04.09.2026 (null güvenli)
        Blade::directive('date', fn ($expr) => "<?php echo ({$expr}) ? \\Illuminate\\Support\\Carbon::parse({$expr})->format('d.m.Y') : '—'; ?>");
    }
}
