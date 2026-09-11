<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/genel-bakis')->assertRedirect('/giris');
        $this->get('/')->assertRedirect('/genel-bakis');
        $this->get('/giris')->assertOk()->assertSee('Giriş Yap');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'secret123', 'username' => 'ayse']);

        $this->post('/giris', ['login' => $user->email, 'password' => 'secret123'])->assertRedirect('/genel-bakis');
        $this->assertAuthenticatedAs($user);
        $this->post('/cikis');

        // Kullanıcı adıyla da (büyük/küçük harf duyarsız)
        $this->post('/giris', ['login' => 'AYSE', 'password' => 'secret123'])->assertRedirect('/genel-bakis');
        $this->assertAuthenticatedAs($user);

        $this->post('/cikis')->assertRedirect('/giris');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected_in_turkish(): void
    {
        $user = User::factory()->create();

        $this->from('/giris')->post('/giris', ['login' => $user->email, 'password' => 'wrong'])
            ->assertRedirect('/giris')
            ->assertSessionHasErrors(['login' => 'Kullanıcı adı / e-posta veya şifre hatalı.']);
        $this->assertGuest();
    }
}
