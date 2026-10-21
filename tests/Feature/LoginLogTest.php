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

    public function test_settings_lists_every_users_logins_in_one_place(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin', 'name' => 'Ayşenur Miran', 'username' => 'ayse', 'email' => 'ayse@ttb.test']);
        $other = \App\Models\User::factory()->create(['role' => 'editor', 'name' => 'Batuhan Şençağ', 'username' => 'batuhan', 'email' => 'batuhan@ttb.test']);

        // Başka bir bilgisayardan başka bir kullanıcının girişi
        $this->withServerVariables(['REMOTE_ADDR' => '88.241.10.5'])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0'])
            ->post('/giris', ['login' => 'batuhan', 'password' => 'password'])->assertRedirect();
        $this->post('/cikis');

        // Hatalı deneme
        $this->post('/giris', ['login' => 'batuhan', 'password' => 'yanlis'])->assertSessionHasErrors('login');

        $r = $this->actingAs($admin)->get('/ayarlar?tab=logins')->assertOk();
        $r->assertSee('Oturum Geçmişi')
            ->assertSee('Batuhan Şençağ')      // başkasının girişi de görünür
            ->assertSee('88.241.10.5')
            ->assertSee('Hatalı şifre');

        $this->assertSame(2, \App\Models\LoginLog::count());
        $this->assertSame($other->id, \App\Models\LoginLog::where('successful', true)->first()->user_id);
    }
}
