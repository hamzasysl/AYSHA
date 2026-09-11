<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function valid(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ayşe', 'last_name' => 'Yılmaz', 'tc_no' => '12345678901', 'phone' => '0532 111 22 33',
            'position' => 'Temizlik Personeli', 'hire_date' => '2025-01-15', 'status' => 'active', 'salary' => '25.500,00',
            'bank_name' => 'Ziraat Bankası', 'iban' => 'tr12 0001 0002 0003 0004 0005 06', 'account_holder' => 'Ayşe Yılmaz',
        ], $overrides);
    }

    public function test_index_lists_and_filters_employees(): void
    {
        Employee::factory()->count(3)->create(['status' => 'active']);
        Employee::factory()->create(['first_name' => 'Pasif', 'last_name' => 'Kişi', 'status' => 'passive']);

        $this->actingAs($this->user)->get('/personel')->assertOk()->assertSee('Pasif Kişi');
        $this->actingAs($this->user)->get('/personel?status=active')->assertOk()->assertDontSee('Pasif Kişi');
        $this->actingAs($this->user)->get('/personel?'.http_build_query(['q' => 'pasif kişi']))->assertOk()->assertSee('Pasif Kişi');
        $this->actingAs($this->user)->get('/personel?q=olmayan')->assertOk()->assertSee('Kayıt bulunamadı');
    }

    public function test_store_normalizes_salary_and_iban(): void
    {
        $this->actingAs($this->user)->post('/personel', $this->valid())->assertRedirect();

        $e = Employee::first();
        $this->assertSame('25500.00', (string) $e->salary);
        $this->assertSame('TR120001000200030004000506', $e->iban);
        $this->assertSame('TR12 0001 0002 0003 0004 0005 06', $e->formatted_iban);
        $this->assertSame('Ayşe Yılmaz', $e->full_name);
        $this->assertSame('AY', $e->initials);
        $this->assertSame('+90 (532) 111 22 33', $e->phone);
    }

    public function test_validation_rejects_bad_iban_and_tc(): void
    {
        $this->actingAs($this->user)->from('/personel/olustur')
            ->post('/personel', $this->valid(['iban' => 'TR123', 'tc_no' => '123']))
            ->assertRedirect('/personel/olustur')
            ->assertSessionHasErrors(['iban', 'tc_no']);

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_tc_no_must_be_unique(): void
    {
        Employee::factory()->create(['tc_no' => '12345678901']);

        $this->actingAs($this->user)->post('/personel', $this->valid())->assertSessionHasErrors('tc_no');
    }

    public function test_show_edit_update_and_delete(): void
    {
        $e = Employee::factory()->create();

        $this->actingAs($this->user)->get("/personel/{$e->id}")->assertOk()->assertSee($e->full_name);
        $this->actingAs($this->user)->get("/personel/{$e->id}/duzenle")->assertOk();

        $this->actingAs($this->user)->put("/personel/{$e->id}", $this->valid(['first_name' => 'Yeni', 'status' => 'passive', 'tc_no' => $e->tc_no]))
            ->assertRedirect("/personel/{$e->id}");
        $this->assertSame('Yeni', $e->fresh()->first_name);
        $this->assertSame('passive', $e->fresh()->status);

        $this->actingAs($this->user)->delete("/personel/{$e->id}")->assertRedirect('/personel');
        $this->assertSoftDeleted($e);
    }

    public function test_notes_can_be_added_and_removed(): void
    {
        $e = Employee::factory()->create();

        $this->actingAs($this->user)->post("/notlar/employees/{$e->id}", ['content' => 'İzin talebi var'])
            ->assertRedirect("/personel/{$e->id}?tab=notes");
        $note = $e->notes()->first();

        // AJAX: JSON döner, düzenlenebilir
        $this->actingAs($this->user)->postJson("/notlar/employees/{$e->id}", ['content' => 'İkinci not'])
            ->assertOk()->assertJsonPath('note.content', 'İkinci not')->assertJsonPath('count', 2);
        $this->actingAs($this->user)->patchJson("/notlar/{$note->id}", ['content' => 'İzin talebi onaylandı'])
            ->assertOk()->assertJsonPath('note.content', 'İzin talebi onaylandı');
        $this->assertSame('İzin talebi onaylandı', $note->fresh()->content);
        $this->actingAs($this->user)->deleteJson("/notlar/".$e->notes()->where('content', 'İkinci not')->first()->id)->assertOk()->assertJsonPath('count', 1);
        $this->assertSame('İzin talebi var', $note->content);
        $this->assertSame($this->user->id, $note->user_id);

        $this->actingAs($this->user)->delete("/notlar/{$note->id}")->assertRedirect();
        $this->assertDatabaseCount('notes', 0);

        $this->actingAs($this->user)->post('/notlar/users/1', ['content' => 'x'])->assertNotFound();
    }
}
