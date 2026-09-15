<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_generate_creates_pending_rows_for_active_employees_only_and_is_idempotent(): void
    {
        Employee::factory()->count(3)->create(['salary' => 25000]);
        Employee::factory()->passive()->create();

        $this->actingAs($this->user)->post('/maas/olustur', ['year' => 2026, 'month' => 9])
            ->assertRedirect('/muhasebe?category=salary&year=2026&month=9')
            ->assertSessionHas('success');

        $this->assertDatabaseCount('salary_payments', 3);
        $this->assertSame(3, SalaryPayment::where('status', 'pending')->where('net_amount', 25000)->count());

        // Yeni personel eklenince tekrar çalıştırmak sadece eksikleri ekler.
        Employee::factory()->create(['salary' => 30000]);
        $this->actingAs($this->user)->post('/maas/olustur', ['year' => 2026, 'month' => 9]);
        $this->assertDatabaseCount('salary_payments', 4);
    }

    public function test_net_amount_and_status_are_computed_on_save(): void
    {
        $p = SalaryPayment::factory()->create(['base_salary' => 20000, 'bonus' => 1000, 'advance' => 500, 'deduction' => 250]);
        $this->assertSame('20250.00', (string) $p->net_amount);
        $this->assertSame('pending', $p->status);
        $this->assertNull($p->paid_at);

        $p->update(['paid_amount' => 10000]);
        $this->assertSame('partial', $p->fresh()->status);
        $this->assertNotNull($p->fresh()->paid_at);
        $this->assertSame(10250.0, $p->fresh()->remaining);

        $p->update(['paid_amount' => 20250]);
        $this->assertSame('paid', $p->fresh()->status);
    }

    public function test_mark_paid_and_update_via_http(): void
    {
        $p = SalaryPayment::factory()->create(['base_salary' => 25000]);

        $this->actingAs($this->user)->from('/maas')->post("/maas/{$p->id}/odendi", ['payment_method' => 'cash'])
            ->assertRedirect('/maas');
        $p->refresh();
        $this->assertSame('paid', $p->status);
        $this->assertSame('cash', $p->payment_method);
        $this->assertSame('25000.00', (string) $p->paid_amount);

        $this->actingAs($this->user)->from('/maas')->patch("/maas/{$p->id}", [
            'base_salary' => '25.000,00', 'bonus' => '1.500', 'advance' => '', 'deduction' => '0', 'paid_amount' => '13.250,00',
            'payment_method' => 'transfer', 'paid_at' => '2026-09-05', 'note' => 'Yarısı ödendi',
        ])->assertRedirect('/maas');
        $p->refresh();
        $this->assertSame('26500.00', (string) $p->net_amount);
        $this->assertSame('partial', $p->status);
        $this->assertSame('Yarısı ödendi', $p->note);
    }

    public function test_overpayment_is_tracked_and_refund_can_be_marked(): void
    {
        $p = SalaryPayment::factory()->create(['base_salary' => 6485]);

        // Elden 6.500 verildi: 15 TL fazla, iade bekleniyor
        $this->actingAs($this->user)->from('/maas')->patch("/maas/{$p->id}", [
            'base_salary' => '6.485,00', 'paid_amount' => '6.500,00', 'payment_method' => 'cash',
        ])->assertRedirect('/maas');
        $p->refresh();
        $this->assertSame('paid', $p->status);
        $this->assertSame(15.0, $p->overpaid);
        $this->assertSame(15.0, $p->refund_pending);

        $this->actingAs($this->user)->get('/muhasebe?category=salary&year='.$p->period_year.'&month='.$p->period_month)->assertOk()->assertSee('iade bekleniyor');

        $this->actingAs($this->user)->from('/maas')->post("/maas/{$p->id}/iade")->assertRedirect('/maas');
        $p->refresh();
        $this->assertSame('15.00', (string) $p->refund_amount);
        $this->assertSame(0.0, $p->refund_pending);
        $this->assertNotNull($p->refund_at);

        // İade, fazla ödemeyi aşamaz; fazla ödeme kalkınca iade sıfırlanır
        $this->actingAs($this->user)->patch("/maas/{$p->id}", ['base_salary' => '6.500,00', 'paid_amount' => '6.500,00', 'refund_amount' => '15']);
        $p->refresh();
        $this->assertSame(0.0, $p->overpaid);
        $this->assertSame('0.00', (string) $p->refund_amount);
        $this->assertNull($p->refund_at);

        $this->actingAs($this->user)->post("/maas/{$p->id}/iade")->assertStatus(422);
    }

    public function test_changing_employee_salary_does_not_touch_past_payroll_rows(): void
    {
        $e = Employee::factory()->create(['salary' => 25000]);
        $this->actingAs($this->user)->post('/maas/olustur', ['year' => 2026, 'month' => 8]);
        $this->assertSame('25000.00', (string) SalaryPayment::forPeriod(2026, 8)->first()->base_salary);

        // Zam: Eylül'den itibaren 28.000
        $e->update(['salary' => 28000]);
        $this->actingAs($this->user)->post('/maas/olustur', ['year' => 2026, 'month' => 9]);

        $this->assertSame('25000.00', (string) SalaryPayment::forPeriod(2026, 8)->first()->base_salary); // Ağustos aynı kaldı
        $this->assertSame('28000.00', (string) SalaryPayment::forPeriod(2026, 9)->first()->base_salary); // Eylül yeni maaş
    }

    public function test_status_dropdown_on_payroll_row(): void
    {
        $p = SalaryPayment::factory()->create(['base_salary' => 25000]);
        $this->actingAs($this->user)->from('/muhasebe')->post("/maas/{$p->id}/durum", ['status' => 'paid'])->assertRedirect('/muhasebe');
        $this->assertSame('paid', $p->fresh()->status);
        $this->assertSame('25000.00', (string) $p->fresh()->paid_amount);
        $this->actingAs($this->user)->post("/maas/{$p->id}/durum", ['status' => 'pending']);
        $this->assertSame('pending', $p->fresh()->status);
        $this->assertSame('0.00', (string) $p->fresh()->paid_amount);
    }

    public function test_mark_all_paid_for_period(): void
    {
        $employees = Employee::factory()->count(3)->create(['salary' => 20000]);
        foreach ($employees as $e) {
            SalaryPayment::factory()->create(['employee_id' => $e->id, 'period_year' => 2026, 'period_month' => 8, 'base_salary' => 20000]);
        }

        $this->actingAs($this->user)->from('/muhasebe?category=salary&year=2026&month=8')
            ->post('/maas/tumunu-ode', ['year' => 2026, 'month' => 8, 'payment_method' => 'transfer'])
            ->assertRedirect();

        $this->assertSame(3, SalaryPayment::forPeriod(2026, 8)->where('status', 'paid')->count());
    }

    public function test_old_payments_url_redirects_into_accounting(): void
    {
        $this->actingAs($this->user)->get('/maas?year=2026&month=9&status=paid')->assertRedirect('/muhasebe?category=salary&year=2026&month=9&status=paid');
    }

    public function test_payments_page_renders_with_and_without_records(): void
    {
        $this->actingAs($this->user)->get('/muhasebe?category=salary&year=2026&month=9')->assertOk()->assertSee('bordrosunu oluştur');

        $e = Employee::factory()->create(['first_name' => 'Fatma', 'last_name' => 'Demir', 'salary' => 22000]);
        SalaryPayment::factory()->create(['employee_id' => $e->id, 'period_year' => 2026, 'period_month' => 9, 'base_salary' => 22000]);

        $this->actingAs($this->user)->get('/muhasebe?category=salary&year=2026&month=9')->assertOk()
            ->assertSee('Fatma Demir')->assertSee('22.000,00 ₺')->assertSee($e->formatted_iban);
        $this->actingAs($this->user)->get('/muhasebe?category=salary&year=2026&month=9&status=paid')->assertOk()->assertDontSee('Fatma Demir');
    }

    public function test_bulk_actions_on_payroll_rows(): void
    {
        $employees = Employee::factory()->count(3)->create(['salary' => 25000]);
        $this->actingAs($this->user)->post('/maas/olustur', ['year' => 2026, 'month' => 9])->assertRedirect();
        $ids = SalaryPayment::pluck('id')->all();

        // Toplu ödendi
        $this->actingAs($this->user)->post('/maas/toplu-durum', ['ids' => $ids, 'status' => 'paid'])
            ->assertRedirect()->assertSessionHas('success', '3 maaş kaydı "Ödendi" olarak işaretlendi.');
        $this->assertSame(3, SalaryPayment::where('status', 'paid')->count());

        // Toplu düzenleme: sadece dolu alanlar
        $this->actingAs($this->user)->post('/maas/toplu-duzenle', [
            'ids' => $ids, 'bonus' => '1.500,00', 'payment_method' => 'cash', 'advance' => '', 'deduction' => '', 'paid_amount' => '',
        ])->assertSessionHasNoErrors()->assertRedirect()->assertSessionHas('success', '3 maaş kaydı güncellendi.');

        foreach (SalaryPayment::all() as $p) {
            $this->assertSame('1500.00', (string) $p->bonus);
            $this->assertSame('cash', $p->payment_method);
            $this->assertSame('25000.00', (string) $p->base_salary);   // dokunulmadı
            $this->assertSame('partial', $p->status);                  // prim eklenince net arttı
        }

        // Toplu silme
        $this->actingAs($this->user)->post('/maas/toplu-sil', ['ids' => $ids])
            ->assertRedirect()->assertSessionHas('success', '3 maaş kaydı silindi.');
        $this->assertSame(0, SalaryPayment::count());
    }

    public function test_viewer_cannot_use_payroll_bulk_actions(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        Employee::factory()->create(['salary' => 25000]);
        $this->actingAs($this->user)->post('/maas/olustur', ['year' => 2026, 'month' => 9]);
        $ids = SalaryPayment::pluck('id')->all();

        $this->actingAs($viewer)->post('/maas/toplu-durum', ['ids' => $ids, 'status' => 'paid'])->assertForbidden();
        $this->actingAs($viewer)->post('/maas/toplu-duzenle', ['ids' => $ids, 'bonus' => '100'])->assertForbidden();
        $this->actingAs($viewer)->post('/maas/toplu-sil', ['ids' => $ids])->assertForbidden();
        $this->assertSame(1, SalaryPayment::count());
    }
}
