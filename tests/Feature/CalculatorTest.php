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
}
