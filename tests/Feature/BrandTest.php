<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Setting;
use App\Models\User;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_brand_is_dura_clean_global(): void
    {
        $this->assertSame('Dura Clean Global', Brand::name());
        $this->assertSame('Personel Yönetimi', Brand::tagline());
        $this->assertSame('DURA CLEAN GLOBAL İZİN FORMU', Brand::leaveFormTitle());

        $this->get('/giris')->assertOk()->assertSee('Dura Clean Global')->assertDontSee('TTB Turizm');
        $this->actingAs(User::factory()->create())->get('/genel-bakis')->assertOk()->assertSee('Dura Clean Global');
    }

    public function test_brand_is_editable_from_settings_everywhere(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/ayarlar', [
            'meal_allowance' => '7.800,00', 'meal_allowance_retired' => '7.800,00',
            'travel_allowance' => '3.628,00', 'travel_allowance_retired' => '3.628,00',
            'company_name' => 'Yeni Firma', 'app_tagline' => 'Saha Yönetimi', 'leave_form_title' => 'YENİ FİRMA İZİN BELGESİ',
            '_tab' => 'company',
        ])->assertRedirect('/ayarlar?tab=company')->assertSessionHas('success');

        $this->assertSame('Yeni Firma', Setting::get('company_name'));

        // Sol menü, sekme başlığı ve giriş ekranı
        $r = $this->actingAs($admin)->get('/genel-bakis')->assertOk();
        $r->assertSee('Yeni Firma')->assertSee('Saha Yönetimi')
            ->assertSee('<title>Genel Bakış · Yeni Firma Saha Yönetimi</title>', false);
        $this->post('/cikis');
        $this->get('/giris')->assertOk()->assertSee('Yeni Firma')->assertSee('Saha Yönetimi');

        // İzin formu başlığı
        $e = Employee::factory()->create();
        $leave = Leave::create(['employee_id' => $e->id, 'type' => 'annual', 'leave_year' => 2026, 'start_date' => '2026-10-01', 'end_date' => '2026-10-03', 'days' => 3]);
        $this->actingAs($admin)->get("/izinler/{$leave->id}/yazdir")->assertOk()->assertSee('YENİ FİRMA İZİN BELGESİ');
    }
}
