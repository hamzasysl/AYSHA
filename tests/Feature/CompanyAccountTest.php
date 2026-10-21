<?php

namespace Tests\Feature;

use App\Models\CompanyAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_accounts_are_managed_from_settings(): void
    {
        $this->actingAs($this->admin)->post('/ayarlar/hesaplar', [
            'label' => 'KDV dahil tahsilat', 'bank_name' => 'Garanti BBVA', 'account_holder' => 'Dura Clean Global',
            'iban' => 'TR33 0006 1005 1978 6457 8413 26', 'account_no' => '6290915', 'branch_code' => '544',
        ])->assertRedirect('/ayarlar?tab=company')->assertSessionHas('success');

        $a = CompanyAccount::first();
        $this->assertSame('TR330006100519786457841326', $a->iban);          // boşluklar temizlenir
        $this->assertSame('TR33 0006 1005 1978 6457 8413 26', $a->iban_pretty);

        $this->actingAs($this->admin)->get('/ayarlar?tab=company')->assertOk()
            ->assertSee('KDV dahil tahsilat')->assertSee('TR33 0006 1005 1978 6457 8413 26')
            ->assertDontSee('Banka (Garanti maaş dosyası)');

        $this->actingAs($this->admin)->delete("/ayarlar/hesaplar/{$a->id}")->assertRedirect();
        $this->assertSame(0, CompanyAccount::count());
    }

    public function test_only_one_payroll_account_and_bank_file_uses_it(): void
    {
        $first = CompanyAccount::create(['label' => 'Eski maaş', 'corp_code' => '111111', 'branch_code' => '1', 'account_no' => '11', 'is_payroll' => true]);
        $this->actingAs($this->admin)->post('/ayarlar/hesaplar', [
            'label' => 'Yeni maaş hesabı', 'corp_code' => '615399', 'branch_code' => '544', 'account_no' => '6290915', 'is_payroll' => '1',
        ])->assertRedirect();

        $this->assertFalse($first->fresh()->is_payroll);
        $this->assertSame('Yeni maaş hesabı', CompanyAccount::payroll()->label);

        $wb = \App\Support\GarantiPayrollFile::build(collect(), 'Ekim maaş', now());
        $ws = $wb->getActiveSheet();
        $this->assertSame('615399', (string) $ws->getCell('B1')->getValue());
        $this->assertSame('544', (string) $ws->getCell('B2')->getValue());
        $this->assertSame('6290915', (string) $ws->getCell('B3')->getValue());
    }

    public function test_invalid_iban_is_rejected_and_only_managers_can_edit(): void
    {
        $this->actingAs($this->admin)->post('/ayarlar/hesaplar', ['label' => 'X', 'iban' => 'ABC123'])
            ->assertSessionHasErrors('iban');

        $editor = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editor)->post('/ayarlar/hesaplar', ['label' => 'X'])->assertForbidden();
    }
}
