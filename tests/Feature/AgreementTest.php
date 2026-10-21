<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementEntry;
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

    public function test_entries_log_payments_and_extras_per_month(): void
    {
        $a = Agreement::factory()->create(['m2' => null, 'm2_rate' => null, 'amount' => 10000, 'vat_rate' => 20]);

        foreach ([
            ['2026-10-05', 'payment', 'Ekim ödemesi', '12.000,00'],
            ['2026-10-18', 'income', 'Cam silme', '2.000,00'],
            ['2026-10-20', 'expense', 'Malzeme', '1.500,00'],
            ['2026-09-05', 'payment', 'Eylül ödemesi', '12.000,00'],
        ] as [$entry_date, $kind, $label, $amount]) {
            $this->actingAs($this->user)
                ->post("/anlasmalar/{$a->id}/kayit", compact('entry_date', 'kind', 'label', 'amount'))
                ->assertSessionHasNoErrors()->assertRedirect();
        }

        $a->refresh();
        $this->assertSame(24000.0, $a->paid());
        $this->assertSame(12000.0, $a->paid(2026, 10));
        $this->assertSame(2000.0, $a->extraIncome(2026, 10));
        $this->assertSame(1500.0, $a->extraExpense(2026, 10));
        $this->assertSame(12500.0, $a->balance(2026, 10));

        // Anlaşma bedeli ekstralardan etkilenmez: sadece tutar + KDV
        $this->assertSame(10000.0, $a->net_total);
        $this->assertSame(12000.0, $a->gross_total);

        $entry = AgreementEntry::where('label', 'Malzeme')->first();
        $this->actingAs($this->user)->delete("/anlasmalar/kayit/{$entry->id}")->assertRedirect();
        $this->assertSame(0.0, $a->fresh()->extraExpense());
    }

    public function test_index_and_show_render_with_notes(): void
    {
        $a = Agreement::factory()->create(['name' => 'Aselsan Genel Müdürlük', 'm2' => 450, 'm2_rate' => 100]);
        $this->actingAs($this->user)->post("/notlar/agreements/{$a->id}", ['content' => 'Sözleşme yenilenecek'])->assertRedirect();
        $this->actingAs($this->user)->post("/anlasmalar/{$a->id}/kayit", ['entry_date' => '2026-10-05', 'kind' => 'payment', 'label' => 'Ekim ödemesi', 'amount' => '54.000,00']);

        $this->actingAs($this->user)->get('/anlasmalar?year=2026&month=10')->assertOk()
            ->assertSee('Aselsan Genel Müdürlük')->assertSee('54.000,00 ₺')->assertSee('Şirket');
        $this->actingAs($this->user)->get("/anlasmalar/{$a->id}")->assertOk()
            ->assertSee('Sözleşme yenilenecek')->assertSee('450 m²')->assertSee('Ekim ödemesi')->assertSee('Ekim 2026');
    }

    public function test_deleting_agreement_removes_entries_and_notes(): void
    {
        $a = Agreement::factory()->create();
        $this->actingAs($this->user)->post("/notlar/agreements/{$a->id}", ['content' => 'not'])->assertRedirect();
        $this->actingAs($this->user)->post("/anlasmalar/{$a->id}/kayit", ['entry_date' => '2026-10-05', 'kind' => 'payment', 'amount' => '100']);

        $this->actingAs($this->user)->delete("/anlasmalar/{$a->id}")->assertRedirect('/anlasmalar');
        $this->assertSame(0, Agreement::count());
        $this->assertSame(0, AgreementEntry::count());
        $this->assertSame(0, \App\Models\Note::count());
    }

    public function test_viewer_can_read_but_not_write(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $a = Agreement::factory()->create();

        $this->actingAs($viewer)->get('/anlasmalar')->assertOk()->assertDontSee('Yeni Anlaşma');
        $this->actingAs($viewer)->get("/anlasmalar/{$a->id}")->assertOk();
        $this->actingAs($viewer)->post('/anlasmalar', ['name' => 'X', 'type' => 'recurring', 'status' => 'active', 'amount' => '1'])->assertForbidden();
        $this->actingAs($viewer)->post("/anlasmalar/{$a->id}/kayit", ['entry_date' => '2026-10-05', 'kind' => 'payment', 'amount' => '1'])->assertForbidden();
        $this->actingAs($viewer)->delete("/anlasmalar/{$a->id}")->assertForbidden();
    }

    public function test_manual_amount_overrides_m2_calculation(): void
    {
        $u = $this->user;

        // m² × fiyat 45.000 ederdi; elle 40.000 girilince o kalır
        $this->actingAs($u)->post('/anlasmalar', [
            'name' => 'Pazarlıklı Şirket', 'type' => 'recurring', 'status' => 'active',
            'm2' => '450', 'm2_rate' => '100', 'amount' => '40.000,00', 'vat_rate' => '20',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $a = Agreement::where('name', 'Pazarlıklı Şirket')->first();
        $this->assertSame('40000.00', (string) $a->amount);
        $this->assertSame(48000.0, $a->gross_total);

        // Düzenlemede de elle girilen tutar korunur
        $this->actingAs($u)->patch("/anlasmalar/{$a->id}", [
            'name' => 'Pazarlıklı Şirket', 'type' => 'recurring', 'status' => 'active',
            'm2' => '450', 'm2_rate' => '100', 'amount' => '38.500,00', 'vat_rate' => '20',
        ])->assertRedirect();
        $this->assertSame('38500.00', (string) $a->fresh()->amount);

        // Tutar boş bırakılırsa m²'den hesaplanır
        $this->actingAs($u)->patch("/anlasmalar/{$a->id}", [
            'name' => 'Pazarlıklı Şirket', 'type' => 'recurring', 'status' => 'active',
            'm2' => '450', 'm2_rate' => '100', 'amount' => '', 'vat_rate' => '20',
        ])->assertRedirect();
        $this->assertSame('45000.00', (string) $a->fresh()->amount);
    }

    public function test_entry_status_can_be_pending_and_changed(): void
    {
        $a = Agreement::factory()->create(['m2' => null, 'm2_rate' => null, 'amount' => 10000, 'vat_rate' => 20]);

        $this->actingAs($this->user)->post("/anlasmalar/{$a->id}/kayit", [
            'entry_date' => '2026-10-05', 'kind' => 'payment', 'status' => 'pending', 'label' => 'Ekim ödemesi', 'amount' => '12.000,00',
        ])->assertSessionHasNoErrors()->assertRedirect();

        // Bekleyen kayıt tahsil edilmiş sayılmaz
        $this->assertSame(0.0, $a->fresh()->paid(2026, 10));
        $this->assertSame(12000.0, $a->fresh()->expected(2026, 10));

        $entry = AgreementEntry::first();
        $this->actingAs($this->user)->post("/anlasmalar/kayit/{$entry->id}/durum", ['status' => 'done'])
            ->assertRedirect()->assertSessionHas('success', 'Ödeme → Yapıldı');

        $this->assertSame(12000.0, $a->fresh()->paid(2026, 10));
        $this->assertSame(0.0, $a->fresh()->expected(2026, 10));

        $this->actingAs($this->user)->get("/anlasmalar/{$a->id}")->assertOk()->assertSee('Ödeme bekleniyor');
    }
}
