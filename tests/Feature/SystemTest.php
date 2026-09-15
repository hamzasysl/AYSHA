<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_tab_shows_status_and_runs_maintenance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/ayarlar?tab=system')->assertOk()
            ->assertSee('Güncelleme sonrası')
            ->assertSee('Veritabanı güncel')
            ->assertSee('Sunucu bilgileri');

        $this->actingAs($admin)->post('/ayarlar/sistem/guncelle')
            ->assertRedirect('/ayarlar?tab=system')->assertSessionHas('success');

        $this->actingAs($admin)->post('/ayarlar/sistem/onbellek')
            ->assertRedirect('/ayarlar?tab=system')->assertSessionHas('success');
    }

    public function test_only_managers_can_run_maintenance(): void
    {
        foreach (['editor', 'viewer'] as $role) {
            $u = User::factory()->create(['role' => $role]);
            $this->actingAs($u)->post('/ayarlar/sistem/guncelle')->assertForbidden();
            $this->actingAs($u)->post('/ayarlar/sistem/onbellek')->assertForbidden();
            $this->actingAs($u)->get('/ayarlar')->assertOk()->assertDontSee('Güncelleme sonrası');
        }
    }

    public function test_settings_page_works_before_login_log_migration_runs(): void
    {
        // Sunucuya yeni sürüm yüklenip migration çalıştırılmadığında ayarlar sayfası
        // (yani güncelleme düğmesinin bulunduğu sayfa) açılabilmeli.
        \Illuminate\Support\Facades\Schema::drop('login_logs');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/ayarlar')->assertOk()->assertSee('Kullanıcılar');
        $this->post('/cikis');
        $this->post('/giris', ['login' => $admin->email, 'password' => 'password'])->assertRedirect();
    }
}
