<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgreementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_amount_comes_from_m2_and_vat_totals_are_computed(): void
    {
        $a = Agreement::factory()->create(['m2' => 450, 'm2_rate' => 100, 'amount' => 0, 'vat_rate' => 20]);

        $this->assertSame('45000.00', (string) $a->amount);
        $this->assertSame(45000.0, $a->net_total);
        $this->assertSame(9000.0, $a->vat_amount);
        $this->assertSame(54000.0, $a->gross_total);
    }

    public function test_extras_change_totals_and_profit(): void
    {
        $a = Agreement::factory()->create(['m2' => null, 'm2_rate' => null, 'amount' => 10000, 'vat_rate' => 20]);

        $this->actingAs($this->user)->post("/anlasmalar/{$a->id}/ekstra", ['label' => 'Cam silme', 'kind' => 'charge', 'amount' => '2.000,00', 'recurring' => 1])->assertRedirect();
        $this->actingAs($this->user)->post("/anlasmalar/{$a->id}/ekstra", ['label' => 'Malzeme', 'kind' => 'cost', 'amount' => '1.500,00', 'recurring' => 1])->assertRedirect();
        $this->actingAs($this->user)->post("/anlasmalar/{$a->id}/ekstra", ['label' => 'İlk temizlik', 'kind' => 'charge', 'amount' => '5.000,00'])->assertRedirect();

        $a->refresh();
        $this->assertSame(2000.0, $a->extra_charges);      // tek seferlik olan sürekli anlaşmanın aylığına girmez
        $this->assertSame(1500.0, $a->extra_costs);
        $this->assertSame(12000.0, $a->net_total);
        $this->assertSame(14400.0, $a->gross_total);
        $this->assertSame(10500.0, $a->profit);
    }

    public function test_payments_are_tracked_per_month(): void
    {
        $a = Agreement::factory()->create(['amount' => 10000, 'm2' => null, 'm2_rate' => null, 'vat_rate' => 20]);

        $this->actingAs($this->user)->post("/anlasmalar/{$a->id}/tahsilat", [
            'period_year' => 2026, 'period_month' => 10, 'amount' => '12.000,00', 'status' => 'paid', 'method' => 'transfer',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $p = AgreementPayment::first();
        $this->assertSame('12000.00', (string) $p->amount);
        $this->assertSame('paid', $p->status);
        $this->assertNotNull($p->paid_at);
        $this->assertSame('Ekim 2026', $p->period_label);

        // Aynı dönem tekrar gönderilince yeni kayıt açmaz, günceller
        $this->actingAs($this->user)->post("/anlasmalar/{$a->id}/tahsilat", [
            'period_year' => 2026, 'period_month' => 10, 'amount' => '11.000,00', 'status' => 'pending',
        ])->assertRedirect();
        $this->assertSame(1, AgreementPayment::count());
        $this->assertNull(AgreementPayment::first()->paid_at);

        $this->actingAs($this->user)->post("/anlasmalar/tahsilat/{$p->id}/durum", ['status' => 'paid'])->assertRedirect();
        $this->assertSame('paid', $p->fresh()->status);
    }

    public function test_generate_creates_pending_rows_for_active_recurring_only_and_is_idempotent(): void
    {
        Agreement::factory()->count(2)->create(['amount' => 10000, 'm2' => null, 'm2_rate' => null]);
        Agreement::factory()->create(['type' => 'one_time', 'amount' => 5000, 'm2' => null, 'm2_rate' => null]);
        Agreement::factory()->create(['status' => 'passive', 'amount' => 5000, 'm2' => null, 'm2_rate' => null]);

        $this->actingAs($this->user)->post('/anlasmalar/tahsilat-olustur', ['year' => 2026, 'month' => 10])
            ->assertRedirect('/anlasmalar?year=2026&month=10')->assertSessionHas('success');
        $this->assertSame(2, AgreementPayment::count());

        $this->actingAs($this->user)->post('/anlasmalar/tahsilat-olustur', ['year' => 2026, 'month' => 10]);
        $this->assertSame(2, AgreementPayment::count());
    }

    public function test_index_and_show_render_with_notes(): void
    {
        $a = Agreement::factory()->create(['name' => 'Aselsan Genel Müdürlük', 'm2' => 450, 'm2_rate' => 100]);
        $this->actingAs($this->user)->post("/notlar/agreements/{$a->id}", ['content' => 'Sözleşme yenilenecek'])->assertRedirect();

        $this->actingAs($this->user)->get('/anlasmalar')->assertOk()
            ->assertSee('Aselsan Genel Müdürlük')->assertSee('54.000,00 ₺');
        $this->actingAs($this->user)->get("/anlasmalar/{$a->id}")->assertOk()
            ->assertSee('Sözleşme yenilenecek')->assertSee('450 m²');
    }

    public function test_deleting_agreement_removes_children_and_notes(): void
    {
        $a = Agreement::factory()->create();
        $this->actingAs($this->user)->post("/notlar/agreements/{$a->id}", ['content' => 'not'])->assertRedirect();
        $this->actingAs($this->user)->post("/anlasmalar/{$a->id}/ekstra", ['label' => 'x', 'kind' => 'cost', 'amount' => '100']);
        $this->actingAs($this->user)->post("/anlasmalar/{$a->id}/tahsilat", ['period_year' => 2026, 'period_month' => 10, 'amount' => '100', 'status' => 'pending']);

        $this->actingAs($this->user)->delete("/anlasmalar/{$a->id}")->assertRedirect('/anlasmalar');
        $this->assertSame(0, Agreement::count());
        $this->assertSame(0, AgreementPayment::count());
        $this->assertSame(0, \App\Models\Note::count());
    }

    public function test_viewer_can_read_but_not_write(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $a = Agreement::factory()->create();

        $this->actingAs($viewer)->get('/anlasmalar')->assertOk()->assertDontSee('Yeni Anlaşma');
        $this->actingAs($viewer)->get("/anlasmalar/{$a->id}")->assertOk();
        $this->actingAs($viewer)->post('/anlasmalar', ['name' => 'X', 'type' => 'recurring', 'status' => 'active', 'amount' => '1'])->assertForbidden();
        $this->actingAs($viewer)->delete("/anlasmalar/{$a->id}")->assertForbidden();
    }
}
