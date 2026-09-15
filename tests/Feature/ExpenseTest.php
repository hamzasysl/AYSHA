<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_renders_with_category_and_employee_filters(): void
    {
        $e = Employee::factory()->create(['first_name' => 'Hülya', 'last_name' => 'Aksoy']);
        Expense::factory()->create(['employee_id' => $e->id, 'category' => 'meal', 'amount' => 2500, 'expense_date' => '2026-09-03']);
        Expense::factory()->create(['employee_id' => null, 'category' => 'cleaning', 'amount' => 8000, 'description' => 'Deterjan', 'expense_date' => '2026-09-10']);
        Expense::factory()->create(['employee_id' => $e->id, 'category' => 'travel', 'amount' => 1500, 'expense_date' => '2026-08-30']); // önceki ay

        $r = $this->actingAs($this->user)->get('/muhasebe?year=2026&month=9')->assertOk();
        $r->assertSee('Hülya Aksoy')->assertSee('Deterjan')->assertSee('10.500,00 ₺')->assertSee('Toplam Giden');

        $this->actingAs($this->user)->get('/muhasebe?year=2026&month=9&category=cleaning')->assertOk()->assertSee('Deterjan')->assertSee('Toplam (1 kayıt)');
        $this->actingAs($this->user)->get('/muhasebe?year=2026&month=8&employee_id='.$e->id)->assertOk()->assertSee('1.500,00 ₺');
    }

    public function test_store_with_initial_note_and_turkish_amount(): void
    {
        $e = Employee::factory()->create();

        $this->actingAs($this->user)->post('/muhasebe', [
            'employee_id' => $e->id, 'category' => 'meal', 'expense_date' => '2026-09-04', 'description' => 'Eylül yemek',
            'amount' => '2.750,50', 'status' => 'paid', 'payment_method' => 'cash', 'note' => 'Elden verildi',
        ])->assertRedirect('/muhasebe?year=2026&month=9');

        $x = Expense::first();
        $this->assertSame('2750.50', (string) $x->amount);
        $this->assertSame('paid', $x->status);
        $this->assertNotNull($x->paid_at);
        $this->assertSame('Elden verildi', $x->notes->first()->content);
        $this->assertSame($this->user->id, $x->notes->first()->user_id);

        $this->actingAs($this->user)->post('/muhasebe', ['category' => 'bogus', 'expense_date' => 'x', 'amount' => '0', 'status' => 'paid'])
            ->assertSessionHasErrors(['category', 'expense_date', 'amount']);
    }

    public function test_bulk_store_creates_one_row_per_employee(): void
    {
        $ids = Employee::factory()->count(3)->create()->pluck('id')->all();

        $this->actingAs($this->user)->post('/muhasebe/toplu', [
            'employee_ids' => $ids, 'category' => 'travel', 'expense_date' => '2026-09-01', 'amount' => '1.500', 'status' => 'pending', 'description' => 'Eylül yol',
        ])->assertRedirect('/muhasebe?year=2026&month=9');

        $this->assertSame(3, Expense::where('category', 'travel')->where('amount', 1500)->count());
    }

    public function test_update_mark_paid_delete_and_notes(): void
    {
        $x = Expense::factory()->create(['status' => 'pending']);

        $this->actingAs($this->user)->from('/muhasebe')->post("/muhasebe/{$x->id}/odendi", ['payment_method' => 'transfer'])->assertRedirect('/muhasebe');
        $this->assertSame('paid', $x->fresh()->status);

        $this->actingAs($this->user)->from('/muhasebe')->patch("/muhasebe/{$x->id}", [
            'employee_id' => '', 'category' => 'other', 'expense_date' => '2026-09-02', 'amount' => '900', 'status' => 'pending', 'payment_method' => '', 'paid_at' => '',
        ])->assertRedirect('/muhasebe');
        $x->refresh();
        $this->assertNull($x->employee_id);
        $this->assertSame('pending', $x->status);
        $this->assertNull($x->paid_at);

        $this->actingAs($this->user)->post("/notlar/expenses/{$x->id}", ['content' => 'Fatura bekleniyor', 'redirect' => '/muhasebe?year=2026&month=9'])
            ->assertRedirect('/muhasebe?year=2026&month=9');
        $this->assertSame(1, $x->notes()->count());

        $note = $x->notes()->first();
        $this->actingAs($this->user)->delete("/notlar/{$note->id}", ['redirect' => 'https://evil.example/'])->assertRedirect(); // dış yönlendirme yok sayılır
        $this->assertSame(0, $x->notes()->count());

        $this->actingAs($this->user)->from('/muhasebe')->delete("/muhasebe/{$x->id}")->assertRedirect('/muhasebe');
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_expense_overpayment_and_refund(): void
    {
        $e = Employee::factory()->create();

        // Yemek ücreti 2.485, elden 2.500 verildi
        $this->actingAs($this->user)->post('/muhasebe', [
            'employee_id' => $e->id, 'category' => 'meal', 'expense_date' => '2026-09-04', 'amount' => '2.485', 'paid_amount' => '2.500', 'status' => 'paid', 'payment_method' => 'cash',
        ])->assertRedirect();
        $x = Expense::first();
        $this->assertSame(15.0, $x->overpaid);
        $this->assertSame(15.0, $x->refund_pending);
        $this->actingAs($this->user)->get('/muhasebe?year=2026&month=9')->assertOk()->assertSee('iade bekleniyor');

        $this->actingAs($this->user)->from('/muhasebe')->post("/muhasebe/{$x->id}/iade")->assertRedirect('/muhasebe');
        $this->assertSame(0.0, $x->fresh()->refund_pending);
        $this->actingAs($this->user)->get('/muhasebe?year=2026&month=9')->assertOk()->assertSee('iade alındı');

        // Bekliyor'a çekilince ödenen ve iade sıfırlanır
        $this->actingAs($this->user)->patch("/muhasebe/{$x->id}", ['category' => 'meal', 'expense_date' => '2026-09-04', 'amount' => '2.485', 'status' => 'pending']);
        $x->refresh();
        $this->assertNull($x->paid_amount);
        $this->assertSame(0.0, $x->overpaid);
    }

    public function test_status_dropdown_toggles_paid_and_pending(): void
    {
        $x = Expense::factory()->create(['status' => 'pending']);
        $this->actingAs($this->user)->from('/muhasebe')->post("/muhasebe/{$x->id}/durum", ['status' => 'paid'])->assertRedirect('/muhasebe');
        $x->refresh();
        $this->assertSame('paid', $x->status);
        $this->assertNotNull($x->paid_at);
        $this->actingAs($this->user)->post("/muhasebe/{$x->id}/durum", ['status' => 'pending']);
        $this->assertSame('pending', $x->fresh()->status);
        $this->actingAs($this->user)->post("/muhasebe/{$x->id}/durum", ['status' => 'x'])->assertSessionHasErrors('status');
    }

    public function test_deduction_reduces_net_and_paid_amount(): void
    {
        $e = Employee::factory()->create();
        $this->actingAs($this->user)->post('/muhasebe', [
            'employee_id' => $e->id, 'category' => 'meal', 'expense_date' => '2026-09-04', 'amount' => '7.800', 'deduction' => '1.200', 'deduction_note' => '3 gün gelmedi', 'status' => 'paid', 'payment_method' => 'transfer',
        ])->assertRedirect();
        $x = Expense::first();
        $this->assertSame(6600.0, $x->net_amount);
        $this->assertSame('6600.00', (string) $x->paid_amount);
        $this->assertSame(0.0, $x->overpaid);
        $this->actingAs($this->user)->get('/muhasebe?year=2026&month=9')->assertOk()->assertSee('6.600,00 ₺')->assertSee('3 gün gelmedi');

        // Kesinti tutarı aşamaz
        $this->actingAs($this->user)->post('/muhasebe', ['employee_id' => $e->id, 'category' => 'travel', 'expense_date' => '2026-09-04', 'amount' => '100', 'deduction' => '150', 'status' => 'pending'])
            ->assertSessionHasErrors('deduction');
    }

    public function test_store_creates_one_record_per_selected_employee(): void
    {
        $ids = Employee::factory()->count(3)->create()->pluck('id')->all();
        $this->actingAs($this->user)->post('/muhasebe', [
            'employee_ids' => $ids, 'category' => 'extra', 'expense_date' => '2026-09-05', 'description' => 'Sağlık raporu ücreti', 'amount' => '400', 'status' => 'paid', 'payment_method' => 'cash', 'note' => 'Elden verildi',
        ])->assertRedirect('/muhasebe?year=2026&month=9')->assertSessionHas('success', '3 personele Ekstra Ödeme kaydı eklendi.');
        $this->assertSame(3, Expense::where('category', 'extra')->where('amount', 400)->count());
        $this->assertSame(3, \App\Models\Note::count());
        $this->actingAs($this->user)->get('/raporlar?year=2026&tab=employees')->assertOk()->assertSee('Ekstra')->assertSee('400,00');
    }

    public function test_employee_show_lists_expenses_tab(): void
    {
        $e = Employee::factory()->create();
        Expense::factory()->create(['employee_id' => $e->id, 'category' => 'travel', 'amount' => 1750, 'description' => 'Yol parası Eylül']);

        $this->actingAs($this->user)->get("/personel/{$e->id}?tab=expenses")->assertOk()->assertSee('Yol parası Eylül')->assertSee('1.750,00 ₺');
    }

    public function test_payment_method_defaults_per_category(): void
    {
        $e = Employee::factory()->create();
        $travel = Expense::factory()->create(['employee_id' => $e->id, 'category' => 'travel', 'amount' => 3628, 'status' => 'pending']);
        $meal = Expense::factory()->create(['employee_id' => $e->id, 'category' => 'meal', 'amount' => 7800, 'status' => 'pending']);
        $other = Expense::factory()->create(['employee_id' => null, 'category' => 'cleaning', 'amount' => 900, 'status' => 'pending']);

        foreach ([$travel, $meal, $other] as $x) {
            $this->actingAs($this->user)->post("/muhasebe/{$x->id}/durum", ['status' => 'paid'])->assertRedirect();
        }

        $this->assertSame('cash', $travel->fresh()->payment_method);
        $this->assertSame('meal_card', $meal->fresh()->payment_method);
        $this->assertSame('transfer', $other->fresh()->payment_method);

        // Bekliyora dönünce yöntem temizlenir
        $this->actingAs($this->user)->post("/muhasebe/{$travel->id}/durum", ['status' => 'pending'])->assertRedirect();
        $this->assertNull($travel->fresh()->payment_method);
    }

    public function test_deleting_expense_removes_its_notes(): void
    {
        $x = Expense::factory()->create(['category' => 'cleaning', 'employee_id' => null]);
        $this->actingAs($this->user)->post("/notlar/expenses/{$x->id}", ['content' => 'Fiş kayıp'])->assertRedirect();
        $this->assertSame(1, \App\Models\Note::count());

        $this->actingAs($this->user)->delete("/muhasebe/{$x->id}")->assertRedirect();
        $this->assertSame(0, \App\Models\Note::count());
    }

    public function test_dashboard_skips_notes_and_reviews_of_deleted_employees(): void
    {
        $e = Employee::factory()->create(['first_name' => 'Kayıp', 'last_name' => 'Personel']);
        $this->actingAs($this->user)->post("/notlar/employees/{$e->id}", ['content' => 'Silinecek personelin notu'])->assertRedirect();
        \App\Models\PerformanceReview::factory()->create(['employee_id' => $e->id]);
        $e->delete();

        $this->actingAs($this->user)->get('/genel-bakis')->assertOk()
            ->assertDontSee('Silinecek personelin notu')
            ->assertDontSee('Kayıp Personel');
    }
}
