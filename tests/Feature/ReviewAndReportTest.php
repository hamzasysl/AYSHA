<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewAndReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_review_crud_is_monthly(): void
    {
        $e = Employee::factory()->create();

        // Varsayılan dönem: geçen ay
        [$py, $pm] = PerformanceReview::defaultPeriod();
        $this->actingAs($this->user)->get('/performans/olustur?employee_id='.$e->id)->assertOk()
            ->assertSee('<option value="'.$pm.'" selected', false);

        $this->actingAs($this->user)->post('/performans', [
            'employee_id' => $e->id, 'review_date' => '2026-09-02', 'period_year' => 2026, 'period_month' => 8,
            'attendance' => 5, 'quality' => 4, 'attitude' => 4, 'score' => 9, 'strengths' => 'Titiz',
        ])->assertRedirect('/performans?year=2026&month=8');

        $r = PerformanceReview::first();
        $this->assertSame(9, $r->score);
        $this->assertSame('Ağustos 2026', $r->period_label);
        $this->assertSame($this->user->id, $r->user_id);

        // Aynı personel + aynı ay ikinci kez girilemez
        $this->actingAs($this->user)->post('/performans', [
            'employee_id' => $e->id, 'review_date' => '2026-09-03', 'period_year' => 2026, 'period_month' => 8,
            'attendance' => 3, 'quality' => 3, 'attitude' => 3, 'score' => 6,
        ])->assertSessionHasErrors('period_month');
        $this->assertSame(1, PerformanceReview::count());

        // Var olan dönem için create -> edit'e yönlendirir
        $this->actingAs($this->user)->get('/performans/olustur?employee_id='.$e->id.'&year=2026&month=8')->assertRedirect("/performans/{$r->id}/duzenle");

        // Başka ay serbest
        $this->actingAs($this->user)->post('/performans', [
            'employee_id' => $e->id, 'review_date' => '2026-08-01', 'period_year' => 2026, 'period_month' => 7,
            'attendance' => 3, 'quality' => 3, 'attitude' => 3, 'score' => 6,
        ])->assertRedirect('/performans?year=2026&month=7');
        $this->assertSame(2, PerformanceReview::count());

        // Dönem sayfası: değerlendirilen ve bekleyen personel
        $other = Employee::factory()->create(['first_name' => 'Bekleyen', 'last_name' => 'Kişi']);
        $page = $this->actingAs($this->user)->get('/performans?year=2026&month=8')->assertOk();
        $page->assertSee($e->full_name)->assertSee('Bekleyen Kişi')->assertSee('1 / 2 tamamlandı')->assertSee('Henüz değerlendirilmedi');

        // Personel geçmişi ay ay
        $this->actingAs($this->user)->get('/performans?year=2026&month=8&employee_id='.$e->id)->assertOk()->assertSee('Temmuz 2026')->assertSee('Ağustos 2026');

        $this->actingAs($this->user)->get("/performans/{$r->id}/duzenle")->assertOk();
        $this->actingAs($this->user)->put("/performans/{$r->id}", ['employee_id' => $e->id, 'review_date' => '2026-09-02', 'period_year' => 2026, 'period_month' => 8, 'attendance' => 3, 'quality' => 3, 'attitude' => 3, 'score' => 6])->assertRedirect();
        $this->assertSame(6, $r->fresh()->score);

        $this->actingAs($this->user)->delete("/performans/{$r->id}")->assertRedirect();
        $this->assertDatabaseCount('performance_reviews', 1);
    }

    public function test_dashboard_and_reports_render(): void
    {
        $e = Employee::factory()->create(['first_name' => 'Zeynep', 'last_name' => 'Kaya', 'salary' => 30000]);
        SalaryPayment::factory()->create(['employee_id' => $e->id, 'period_year' => now()->year, 'period_month' => now()->month, 'base_salary' => 30000]);
        PerformanceReview::factory()->create(['employee_id' => $e->id, 'score' => 9, 'review_date' => now()->toDateString(), 'period_year' => now()->year, 'period_month' => now()->month]);

        $this->actingAs($this->user)->get('/genel-bakis')->assertOk()->assertSee('Zeynep Kaya')->assertSee('Bekleyen Ödeme');
        $this->actingAs($this->user)->get('/raporlar?year='.now()->year)->assertOk()->assertSee('Zeynep Kaya')->assertSee('30.000,00');
        $this->actingAs($this->user)->get('/raporlar?year='.(now()->year - 1))->assertOk();
    }
}
