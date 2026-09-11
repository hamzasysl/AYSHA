<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_business_days_and_next_workday(): void
    {
        // 2026-09-07 Pazartesi .. 2026-09-13 Pazar => 5 iş günü, iş başı 14.09 Pazartesi
        $this->assertSame(5, Leave::businessDays(Carbon::parse('2026-09-07'), Carbon::parse('2026-09-13')));
        $this->assertSame('2026-09-14', Leave::nextWorkday(Carbon::parse('2026-09-13'))->toDateString());
        $this->assertSame('2026-09-14', Leave::nextWorkday(Carbon::parse('2026-09-11'))->toDateString()); // Cuma -> Pazartesi
    }

    public function test_annual_leave_deducts_and_warns_when_exhausted(): void
    {
        $e = Employee::factory()->create(['annual_leave_days' => 14]);

        $post = fn (array $o = []) => $this->actingAs($this->user)->post('/izinler', array_merge([
            'employee_id' => $e->id, 'type' => 'annual', 'leave_year' => 2026, 'start_date' => '2026-09-07', 'end_date' => '2026-09-18',
            'days' => '10', 'deduct_annual' => '1',
        ], $o));

        $post()->assertRedirect('/izinler?year=2026');
        $l = Leave::first();
        $this->assertSame('2026-09-21', $l->return_date->toDateString()); // otomatik iş başı
        $this->assertSame(['entitlement' => 14.0, 'used' => 10.0, 'remaining' => 4.0], $e->leaveBalance(2026));

        // Kalan 4, istenen 5 -> uyarı, kayıt yok
        $post(['start_date' => '2026-10-05', 'end_date' => '2026-10-09', 'days' => '5'])->assertRedirect()->assertSessionHas('leave_warning');
        $this->assertSame(1, Leave::count());

        // Yine de ver -> kaydedilir, bakiye eksiye düşer
        $post(['start_date' => '2026-10-05', 'end_date' => '2026-10-09', 'days' => '5', 'force' => '1'])->assertRedirect('/izinler?year=2026');
        $this->assertSame(-1.0, $e->fresh()->leaveBalance(2026)['remaining']);

        // Ücretsiz izin / rapor / devamsızlık düşmez
        $post(['type' => 'sick', 'deduct_annual' => '0', 'start_date' => '2026-11-02', 'end_date' => '2026-11-03', 'days' => '2'])->assertRedirect('/izinler?year=2026');
        $post(['type' => 'absence', 'deduct_annual' => '0', 'start_date' => '2026-11-10', 'end_date' => '2026-11-10', 'days' => '1'])->assertRedirect('/izinler?year=2026');
        $this->assertSame(-1.0, $e->fresh()->leaveBalance(2026)['remaining']);
        $this->assertSame(4, Leave::count());

        $page = $this->actingAs($this->user)->get('/izinler?year=2026')->assertOk();
        $page->assertSee($e->full_name)->assertSee('Raporlu')->assertSee('Devamsızlık');
        $this->actingAs($this->user)->get('/izinler?year=2026&type=sick')->assertOk()->assertSee('Raporlu')->assertDontSee('Devamsızlık (gelmedi)</span>', false);
    }

    public function test_edit_update_delete_print_and_employee_tab(): void
    {
        $e = Employee::factory()->create(['annual_leave_days' => 10, 'first_name' => 'Gülver', 'last_name' => 'Çubukçu']);
        $l = Leave::factory()->create(['employee_id' => $e->id, 'leave_year' => 2026, 'start_date' => '2026-09-07', 'end_date' => '2026-09-11', 'days' => 5, 'deduct_annual' => true]);

        $this->actingAs($this->user)->get("/izinler/{$l->id}/duzenle")->assertOk();
        // Aynı kaydı 8 güne çıkar: kalan 5 + kendi 5 = 10 >= 8, uyarı yok
        $this->actingAs($this->user)->put("/izinler/{$l->id}", ['employee_id' => $e->id, 'type' => 'annual', 'leave_year' => 2026, 'start_date' => '2026-09-07', 'end_date' => '2026-09-16', 'days' => '8', 'deduct_annual' => '1'])
            ->assertRedirect('/izinler?year=2026');
        $this->assertSame(2.0, $e->fresh()->leaveBalance(2026)['remaining']);
        // 11 güne çıkar: aşar -> uyarı
        $this->actingAs($this->user)->put("/izinler/{$l->id}", ['employee_id' => $e->id, 'type' => 'annual', 'leave_year' => 2026, 'start_date' => '2026-09-07', 'end_date' => '2026-09-21', 'days' => '11', 'deduct_annual' => '1'])
            ->assertSessionHas('leave_warning');

        $this->actingAs($this->user)->get("/izinler/{$l->id}/yazdir")->assertOk()->assertSee('TTB GRUP İZİN FORMU')->assertSee('Gülver Çubukçu')->assertSee('8 iş günü');
        $this->actingAs($this->user)->get("/personel/{$e->id}?tab=leaves")->assertOk()->assertSee('İzinler (1)')->assertSee('Düşüldü');

        $this->actingAs($this->user)->from('/izinler')->delete("/izinler/{$l->id}")->assertRedirect('/izinler');
        $this->assertSame(10.0, $e->fresh()->leaveBalance(2026)['remaining']);
    }

    public function test_validation(): void
    {
        $e = Employee::factory()->create();
        $this->actingAs($this->user)->post('/izinler', ['employee_id' => $e->id, 'type' => 'x', 'leave_year' => 2026, 'start_date' => '2026-09-10', 'end_date' => '2026-09-09', 'days' => '0'])
            ->assertSessionHasErrors(['type', 'end_date', 'days']);
    }
}
