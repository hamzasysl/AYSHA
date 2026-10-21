<?php

namespace Tests\Feature;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginLogTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME_WIN = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';
    private const SAFARI_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    public function test_successful_login_is_recorded_with_device_and_ip(): void
    {
        $user = User::factory()->create(['username' => 'ayse', 'password' => 'secret123']);

        $this->withServerVariables(['REMOTE_ADDR' => '88.24.10.5'])
            ->withHeaders(['User-Agent' => self::CHROME_WIN])
            ->post('/giris', ['login' => 'ayse', 'password' => 'secret123'])
            ->assertRedirect('/genel-bakis');

        $log = LoginLog::first();
        $this->assertTrue($log->successful);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('88.24.10.5', $log->ip);
        $this->assertSame('Masaüstü', $log->device);
        $this->assertSame('Windows', $log->platform);
        $this->assertSame('Chrome', $log->browser);
        $this->assertSame('Masaüstü · Windows · Chrome', $log->device_label);
    }

    public function test_failed_login_is_recorded_against_the_user(): void
    {
        $user = User::factory()->create(['username' => 'ayse', 'password' => 'secret123']);

        $this->withHeaders(['User-Agent' => self::SAFARI_IPHONE])
            ->from('/giris')->post('/giris', ['login' => 'ayse', 'password' => 'yanlis'])
            ->assertSessionHasErrors('login');

        $log = LoginLog::first();
        $this->assertFalse($log->successful);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('Telefon', $log->device);
        $this->assertSame('iPhone', $log->platform);
        $this->assertSame('Safari', $log->browser);

        // Bilinmeyen kullanıcı adı: kayıt yine tutulur, kullanıcıya bağlanmaz
        $this->post('/giris', ['login' => 'olmayan', 'password' => 'x']);
        $this->assertNull(LoginLog::where('login', 'olmayan')->first()->user_id);
    }

    public function test_history_is_visible_in_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Ayşenur Miran']);
        LoginLog::record(request()->merge([]), $admin, 'ayse', true);

        $this->actingAs($admin)->get('/ayarlar?tab=users')->assertOk()
            ->assertSee('Son giriş')
            ->assertSee('Oturum geçmişi')
            ->assertSee('Başarılı');
    }

    public function test_remembered_logins_from_other_devices_are_recorded(): void
    {
        $u = \App\Models\User::factory()->create(['username' => 'ayse', 'email' => 'ayse@ttb.test']);

        // Formdan giriş (beni hatırla işaretli)
        $this->withServerVariables(['REMOTE_ADDR' => '88.241.10.5'])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0'])
            ->post('/giris', ['login' => 'ayse', 'password' => 'password', 'remember' => '1'])->assertRedirect();
        $this->assertSame(1, \App\Models\LoginLog::count(), 'form girişi tek kayıt yazmalı');

        // "Beni hatırla" çerezinden açılan oturum giriş formundan geçmez ama aynı Login
        // olayını tetikler (SessionGuard::user() -> fireLoginEvent); dinleyici onu da yazar.
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        request()->server->set('REMOTE_ADDR', '78.180.4.9');
        request()->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Safari/605.1');
        \Illuminate\Support\Facades\Auth::guard('web')->login($u, true);

        $this->assertSame(2, \App\Models\LoginLog::count(), 'çerezden açılan oturum da kaydedilmeli');
        $son = \App\Models\LoginLog::latest('id')->first();
        $this->assertSame($u->id, $son->user_id);
        $this->assertSame('78.180.4.9', $son->ip);
        $this->assertStringContainsString('iPhone', $son->device_label);
    }

    public function test_user_history_modal_shows_every_device(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin', 'name' => 'Ayşenur Miran', 'username' => 'ayse', 'email' => 'ayse@ttb.test']);
        foreach ([['88.241.10.5', 'Windows NT 10.0'], ['78.180.4.9', 'Macintosh; Intel Mac OS X 10_15']] as [$ip, $ua]) {
            \App\Models\LoginLog::create([
                'user_id' => $admin->id, 'login' => 'ayse', 'successful' => true,
                'ip' => $ip, 'device' => 'Masaüstü', 'platform' => 'x', 'browser' => 'Chrome', 'user_agent' => $ua,
            ]);
        }

        $this->actingAs($admin)->get('/ayarlar?tab=users')->assertOk()
            ->assertSee('88.241.10.5')->assertSee('78.180.4.9')
            ->assertSee('Tüm kayıtlar')
            ->assertDontSee('Kim nereden girmiş');   // ayrı sekme kaldırıldı
    }
}
