<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\M2Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_rules(): void
    {
        $this->assertSame(5000.0, M2Pricing::calculate(50)['total']);
        $this->assertFalse(M2Pricing::calculate(50)['small']);
        $this->assertSame(10000.0, M2Pricing::calculate(100)['total']);
        $this->assertSame(8080.0, M2Pricing::calculate(101)['total']);
        $this->assertTrue(M2Pricing::calculate(101)['large']);
        $this->assertSame(3000.0, M2Pricing::calculate(30)['total']);
        $this->assertTrue(M2Pricing::calculate(30)['small']);
    }

    public function test_page_renders_for_every_role(): void
    {
        foreach (['admin', 'viewer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get('/m2-hesaplayici')->assertOk()->assertSee('m² Hesaplayıcı')->assertSee('100 m² üstü');
        }
    }

    public function test_prices_can_be_changed_from_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/ayarlar?tab=pricing')->assertOk()->assertSee('m² Fiyatları');

        $this->actingAs($admin)->put('/ayarlar/m2-fiyatlari', [
            'm2_rate' => '120', 'm2_large_rate' => '90', 'm2_large_limit' => '150', 'm2_small_limit' => '40',
        ])->assertRedirect('/ayarlar?tab=pricing')->assertSessionHas('success');

        $this->assertSame(18000.0, M2Pricing::calculate(150)['total']);   // 150 × 120
        $this->assertSame(13590.0, M2Pricing::calculate(151)['total']);   // 151 × 90
        $this->assertTrue(M2Pricing::calculate(39)['small']);
        $this->assertFalse(M2Pricing::calculate(45)['small']);

        $this->actingAs($admin)->get('/m2-hesaplayici')->assertOk()
            ->assertSee('150 m² üstü')->assertSee('18.000,00 ₺')->assertSee('Fiyatları düzenle');

        // küçük sınır büyük sınırdan büyük olamaz
        $this->actingAs($admin)->put('/ayarlar/m2-fiyatlari', [
            'm2_rate' => '120', 'm2_large_rate' => '90', 'm2_large_limit' => '100', 'm2_small_limit' => '200',
        ])->assertSessionHasErrors('m2_small_limit');
    }

    public function test_only_managers_can_change_prices(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editor)->put('/ayarlar/m2-fiyatlari', [
            'm2_rate' => '1', 'm2_large_rate' => '1', 'm2_large_limit' => '100', 'm2_small_limit' => '50',
        ])->assertForbidden();
        $this->actingAs($editor)->get('/m2-hesaplayici')->assertOk()->assertDontSee('Fiyatları düzenle');
    }
}
