<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_only_read(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $e = Employee::factory()->create();
        $x = Expense::factory()->create(['employee_id' => $e->id]);

        $this->actingAs($viewer)->get('/genel-bakis')->assertOk();
        $this->actingAs($viewer)->get('/personel')->assertOk()->assertDontSee('Yeni Personel');
        $this->actingAs($viewer)->get("/personel/{$e->id}")->assertOk()->assertDontSee('Personeli Sil');
        $this->actingAs($viewer)->get('/muhasebe?year='.now()->year.'&month='.now()->month)->assertOk()->assertDontSee('Yeni Kayıt');
        $this->actingAs($viewer)->get('/raporlar')->assertOk();
        $this->actingAs($viewer)->get('/izinler')->assertOk()->assertDontSee('Yeni İzin');

        $this->actingAs($viewer)->get('/personel/olustur')->assertForbidden();
        $this->actingAs($viewer)->post('/personel', ['first_name' => 'X'])->assertForbidden();
        $this->actingAs($viewer)->put("/personel/{$e->id}", [])->assertForbidden();
        $this->actingAs($viewer)->delete("/personel/{$e->id}")->assertForbidden();
        $this->actingAs($viewer)->post("/muhasebe/{$x->id}/durum", ['status' => 'paid'])->assertForbidden();
        $this->actingAs($viewer)->postJson("/notlar/employees/{$e->id}", ['content' => 'x'])->assertForbidden();
        $this->actingAs($viewer)->put('/ayarlar', [])->assertForbidden();
        $this->actingAs($viewer)->post('/ayarlar/kullanicilar', [])->assertForbidden();

        // Kendi hesabı: şifre değiştirebilir
        $this->actingAs($viewer)->get('/ayarlar')->assertOk()->assertDontSee('Kullanıcılar')->assertSee('Hesap');
        $this->actingAs($viewer)->put('/ayarlar/hesap', ['name' => 'Ben', 'username' => $viewer->username, 'email' => $viewer->email])->assertRedirect('/ayarlar?tab=account');
    }

    public function test_editor_can_edit_but_not_delete_or_manage(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $e = Employee::factory()->create();
        $x = Expense::factory()->create(['employee_id' => $e->id, 'status' => 'pending']);

        $this->actingAs($editor)->get('/personel/olustur')->assertOk();
        $this->actingAs($editor)->post("/muhasebe/{$x->id}/durum", ['status' => 'paid'])->assertRedirect();
        $this->assertSame('paid', $x->fresh()->status);
        $this->actingAs($editor)->postJson("/notlar/employees/{$e->id}", ['content' => 'Not'])->assertOk();

        $this->actingAs($editor)->delete("/muhasebe/{$x->id}")->assertForbidden();
        $this->actingAs($editor)->delete("/personel/{$e->id}")->assertForbidden();
        $this->actingAs($editor)->put('/ayarlar', [])->assertForbidden();
        $this->actingAs($editor)->get('/ayarlar')->assertOk()->assertDontSee('Kullanıcılar');
        $this->actingAs($editor)->get("/personel/{$e->id}")->assertOk()->assertSee('Düzenle')->assertDontSee('Personeli Sil');
    }

    public function test_owner_has_full_access(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = Employee::factory()->create();
        $this->actingAs($owner)->delete("/personel/{$e->id}")->assertRedirect('/personel');
        $this->actingAs($owner)->get('/ayarlar?tab=users')->assertOk()->assertSee('Kullanıcılar');
    }
}
