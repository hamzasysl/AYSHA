<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\SalaryPayment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class SettingsAndAllowanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Cache::forget('settings.all');
    }

    public function test_defaults_and_settings_page(): void
    {
        $this->assertSame(7800.0, Setting::amount('meal_allowance'));
        $this->assertSame(3628.0, Setting::amount('travel_allowance'));

        $this->actingAs($this->user)->get('/ayarlar')->assertOk()->assertSee('7.800,00')->assertSee('3.628,00');

        $this->actingAs($this->user)->put('/ayarlar', [
            'meal_allowance' => '8.000,00', 'meal_allowance_retired' => '6.500', 'travel_allowance' => '3.628', 'travel_allowance_retired' => '2.000',
            'company_name' => 'AYSHA', 'bank_corp_code' => '615399', 'bank_branch_code' => '544', 'bank_account' => '6290915',
        ])->assertRedirect('/ayarlar?tab=allowances');

        $this->assertSame(8000.0, Setting::amount('meal_allowance'));
        $this->assertSame(6500.0, Setting::amount('meal_allowance_retired'));
        $this->assertSame('615399', Setting::get('bank_corp_code'));
    }

    public function test_account_settings_and_password_change(): void
    {
        $this->actingAs($this->user)->put('/ayarlar/hesap', ['name' => 'Ayşenur Miran', 'username' => 'Ayse', 'email' => 'ayse@example.com'])
            ->assertRedirect('/ayarlar?tab=account');
        $u = $this->user->fresh();
        $this->assertSame('ayse', $u->username);
        $this->assertSame('ayse@example.com', $u->email);

        // Yanlış mevcut şifre
        $this->actingAs($this->user)->put('/ayarlar/hesap', ['name' => 'A', 'username' => 'ayse', 'email' => 'ayse@example.com', 'current_password' => 'yanlis', 'password' => 'yeni12345', 'password_confirmation' => 'yeni12345'])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($this->user)->put('/ayarlar/hesap', ['name' => 'A', 'username' => 'ayse', 'email' => 'ayse@example.com', 'current_password' => 'password', 'password' => 'yeni12345', 'password_confirmation' => 'yeni12345'])
            ->assertRedirect('/ayarlar?tab=account');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('yeni12345', $this->user->fresh()->password));
    }

    public function test_user_management(): void
    {
        $this->actingAs($this->user)->post('/ayarlar/kullanicilar', ['name' => 'Batuhan Şençağ', 'username' => 'Batuhan', 'email' => 'batuhan@example.com', 'role' => 'owner', 'password' => 'batuhan123'])
            ->assertRedirect('/ayarlar?tab=users')->assertSessionHas('shown_password');
        $b = User::where('username', 'batuhan')->first();
        $this->assertSame('owner', $b->role);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('batuhan123', $b->password));

        $this->actingAs($this->user)->get('/ayarlar?tab=users')->assertOk()->assertSee('Batuhan Şençağ')->assertSee('Patron');

        // Aynı kullanıcı adı olmaz
        $this->actingAs($this->user)->post('/ayarlar/kullanicilar', ['name' => 'X', 'username' => 'batuhan', 'email' => 'x@example.com', 'role' => 'admin', 'password' => '123456'])->assertSessionHasErrors('username');

        // Düzenle + yeni şifre
        $this->actingAs($this->user)->put("/ayarlar/kullanicilar/{$b->id}", ['name' => 'Batuhan Ş.', 'username' => 'batuhan', 'email' => 'batuhan@example.com', 'role' => 'admin', 'password' => 'yeni123'])
            ->assertRedirect('/ayarlar?tab=users');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('yeni123', $b->fresh()->password));
        $this->assertSame('admin', $b->fresh()->role);

        // Kendini silemez, başkasını silebilir
        $this->actingAs($this->user)->delete("/ayarlar/kullanicilar/{$this->user->id}")->assertStatus(422);
        $this->actingAs($this->user)->delete("/ayarlar/kullanicilar/{$b->id}")->assertRedirect('/ayarlar?tab=users');
        $this->assertNull(User::find($b->id));
    }

    public function test_expense_category_management(): void
    {
        $this->assertArrayHasKey('health', Expense::categories());
        $this->assertSame('Sağlık Raporu', Expense::categories()['health']['label']);

        $this->actingAs($this->user)->post('/ayarlar/kategoriler', ['label' => 'Üniforma', 'icon' => 'fa-shirt', 'color' => 'orange', 'employee_based' => '1'])
            ->assertRedirect('/ayarlar?tab=categories');
        $cat = \App\Models\ExpenseCategory::where('label', 'Üniforma')->first();
        $this->assertSame('uniforma', $cat->slug);
        $this->assertArrayHasKey('uniforma', Expense::categories());

        // Yeni kategoriyle kayıt açılabilir ve raporda Ekstra'ya girer
        $e = Employee::factory()->create();
        $this->actingAs($this->user)->post('/muhasebe', ['employee_ids' => [$e->id], 'category' => 'uniforma', 'expense_date' => '2026-09-05', 'amount' => '900', 'status' => 'paid'])->assertRedirect();
        $this->actingAs($this->user)->get('/raporlar?year=2026&tab=employees')->assertOk()->assertSee('900,00');

        // Kayıt varken silinemez, düzenlenebilir, sistem kategorisi silinemez
        $this->actingAs($this->user)->delete("/ayarlar/kategoriler/{$cat->id}")->assertRedirect('/ayarlar?tab=categories')->assertSessionHas('error');
        $this->actingAs($this->user)->put("/ayarlar/kategoriler/{$cat->id}", ['label' => 'Üniforma Gideri', 'icon' => 'fa-shirt', 'color' => 'orange', 'employee_based' => '0'])->assertRedirect();
        $this->assertFalse($cat->fresh()->employee_based);
        $meal = \App\Models\ExpenseCategory::where('slug', 'meal')->first();
        $this->actingAs($this->user)->delete("/ayarlar/kategoriler/{$meal->id}")->assertStatus(422);

        $this->actingAs($this->user)->get('/ayarlar?tab=categories')->assertOk()->assertSee('Gider Kategorileri')->assertSee('Sağlık Raporu');
    }

    public function test_managed_lists_positions_banks_leave_types(): void
    {
        $this->assertContains('Genel Koordinatör', Employee::positions());
        $this->assertArrayHasKey('sick', \App\Models\Leave::types());

        // Görev ekle, yeniden adlandır (personel kartı da güncellenir), sil
        $this->actingAs($this->user)->post('/ayarlar/listeler/position', ['label' => 'Bahçıvan'])->assertRedirect('/ayarlar?tab=lists&list=position');
        $this->assertContains('Bahçıvan', Employee::positions());
        $e = Employee::factory()->create(['position' => 'Bahçıvan']);
        $item = \App\Models\ListItem::where('type', 'position')->where('label', 'Bahçıvan')->first();
        $this->actingAs($this->user)->put("/ayarlar/listeler/{$item->id}", ['label' => 'Bahçe Görevlisi'])->assertRedirect();
        $this->assertSame('Bahçe Görevlisi', $e->fresh()->position);
        $this->actingAs($this->user)->delete("/ayarlar/listeler/{$item->id}")->assertRedirect()->assertSessionHas('error'); // kullanımda
        $e->forceDelete();
        $this->actingAs($this->user)->delete("/ayarlar/listeler/{$item->id}")->assertRedirect()->assertSessionHas('success');

        // İzin türü ekle: düşer, ikonlu; kayıt açılabilir
        $this->actingAs($this->user)->post('/ayarlar/listeler/leave_type', ['label' => 'Babalık izni', 'icon' => 'fa-baby', 'deduct' => '0'])->assertRedirect();
        $types = \App\Models\Leave::types();
        $this->assertArrayHasKey('babalik_izni', $types);
        $this->assertFalse($types['babalik_izni']['deduct']);
        $emp = Employee::factory()->create();
        $this->actingAs($this->user)->post('/izinler', ['employee_id' => $emp->id, 'type' => 'babalik_izni', 'leave_year' => 2026, 'start_date' => '2026-09-07', 'end_date' => '2026-09-11', 'days' => '5', 'deduct_annual' => '0'])->assertRedirect('/izinler?year=2026');
        $this->actingAs($this->user)->get('/izinler?year=2026')->assertOk()->assertSee('Babalık izni');

        // Sistem izin türü silinemez
        $annual = \App\Models\ListItem::where('type', 'leave_type')->where('slug', 'annual')->first();
        $this->actingAs($this->user)->delete("/ayarlar/listeler/{$annual->id}")->assertStatus(422);

        // Banka
        $this->actingAs($this->user)->post('/ayarlar/listeler/bank', ['label' => 'Fibabanka'])->assertRedirect();
        $this->assertContains('Fibabanka', \App\Models\ListItem::labels('bank'));
        $this->actingAs($this->user)->get('/ayarlar?tab=lists&list=bank')->assertOk()->assertSee('Fibabanka')->assertSee('Listeler');
    }

    public function test_retired_flag_and_effective_allowances(): void
    {
        Setting::set('meal_allowance_retired', 6000);
        Setting::set('travel_allowance_retired', 2500);

        $std = Employee::factory()->create(['is_retired' => false, 'meal_allowance' => null, 'travel_allowance' => null]);
        $ret = Employee::factory()->create(['is_retired' => true, 'meal_allowance' => null, 'travel_allowance' => null]);
        $custom = Employee::factory()->create(['is_retired' => true, 'meal_allowance' => 5000, 'travel_allowance' => null]);

        $this->assertSame(7800.0, $std->effective_meal_allowance);
        $this->assertSame(3628.0, $std->effective_travel_allowance);
        $this->assertSame(6000.0, $ret->effective_meal_allowance);
        $this->assertSame(2500.0, $ret->effective_travel_allowance);
        $this->assertSame(5000.0, $custom->effective_meal_allowance);
        $this->assertSame(2500.0, $custom->effective_travel_allowance);

        // Formdan emekli + özel yemek tutarı
        $this->actingAs($this->user)->post('/personel', [
            'first_name' => 'Kıymet', 'last_name' => 'Çolak', 'position' => 'Temizlik Personeli', 'status' => 'active', 'salary' => '23.225,50',
            'is_retired' => '1', 'meal_allowance' => '7.000', 'travel_allowance' => '', 'bank_code' => '62', 'branch_code' => '1012', 'account_no' => '6614027',
        ])->assertRedirect();
        $e = Employee::where('last_name', 'Çolak')->first();
        $this->assertTrue($e->is_retired);
        $this->assertSame('7000.00', (string) $e->meal_allowance);
        $this->assertNull($e->travel_allowance);

        // Standart tutar aynen gönderilirse özel değer olarak saklanmaz (ayarları takip eder)
        $this->actingAs($this->user)->put("/personel/{$e->id}", [
            'first_name' => 'Kıymet', 'last_name' => 'Çolak', 'position' => 'Temizlik Personeli', 'status' => 'active', 'salary' => '23.225,50',
            'is_retired' => '1', 'meal_allowance' => '6.000,00', 'travel_allowance' => '2.500,00', 'bank_code' => '62', 'branch_code' => '1012', 'account_no' => '6614027',
        ]);
        $e->refresh();
        $this->assertNull($e->meal_allowance);
        $this->assertNull($e->travel_allowance);
        $this->actingAs($this->user)->get("/personel/{$e->id}/duzenle")->assertOk()->assertSee('value="6.000,00"', false);
        $this->assertSame('1012', $e->branch_code);

        $this->actingAs($this->user)->get('/personel')->assertOk()->assertSee('Emekli')->assertSee('Normal');
        $this->actingAs($this->user)->get('/personel?type=retired')->assertOk()->assertSee('Kıymet Çolak');
        $this->actingAs($this->user)->get('/personel?type=normal')->assertOk()->assertDontSee('Kıymet Çolak');
    }

    public function test_generate_monthly_allowances_for_active_employees(): void
    {
        Setting::set('meal_allowance_retired', 6000);
        Employee::factory()->count(2)->create(['is_retired' => false, 'meal_allowance' => null, 'travel_allowance' => null]);
        Employee::factory()->create(['is_retired' => true, 'meal_allowance' => null, 'travel_allowance' => null]);
        Employee::factory()->passive()->create();

        $this->actingAs($this->user)->post('/muhasebe/yemek-yol-olustur', ['year' => 2026, 'month' => 9, 'status' => 'pending'])
            ->assertRedirect('/muhasebe?year=2026&month=9')->assertSessionHas('success');

        $this->assertSame(6, Expense::count()); // 3 aktif x (yemek + yol)
        $this->assertSame(2, Expense::where('category', 'meal')->where('amount', 7800)->count());
        $this->assertSame(1, Expense::where('category', 'meal')->where('amount', 6000)->count());
        $this->assertSame(3, Expense::where('category', 'travel')->where('amount', 3628)->count());
        $this->assertStringContainsString('(emekli)', Expense::where('amount', 6000)->first()->description);

        // Tekrar çalıştırınca çift kayıt açmaz
        $this->actingAs($this->user)->post('/muhasebe/yemek-yol-olustur', ['year' => 2026, 'month' => 9]);
        $this->assertSame(6, Expense::count());
    }

    public function test_garanti_bank_file_export(): void
    {
        Setting::set('bank_corp_code', '615399');
        Setting::set('bank_branch_code', '544');
        Setting::set('bank_account', '6290915');

        $e1 = Employee::factory()->create(['first_name' => 'Dursun', 'last_name' => 'Çangal', 'tc_no' => '21877265378', 'bank_code' => '62', 'branch_code' => '544', 'account_no' => '6800625', 'iban' => 'TR100006200054400006800625', 'salary' => 28075.5]);
        $e2 = Employee::factory()->create(['first_name' => 'Kıymet', 'last_name' => 'Çolak', 'salary' => 23225.5]);
        $e3 = Employee::factory()->create(['salary' => 20000]);
        SalaryPayment::factory()->create(['employee_id' => $e1->id, 'period_year' => 2026, 'period_month' => 7, 'base_salary' => 28075.5]);
        SalaryPayment::factory()->create(['employee_id' => $e2->id, 'period_year' => 2026, 'period_month' => 7, 'base_salary' => 23225.5, 'paid_amount' => 10000]);
        SalaryPayment::factory()->create(['employee_id' => $e3->id, 'period_year' => 2026, 'period_month' => 7, 'base_salary' => 20000, 'paid_amount' => 20000]);

        $res = $this->actingAs($this->user)->get('/maas/banka-dosyasi?year=2026&month=7&scope=remaining&payment_date=2026-08-11');
        $res->assertOk()->assertHeader('content-disposition', 'attachment; filename=2026-07-garanti-maas-dosyasi.xlsx');

        $tmp = tempnam(sys_get_temp_dir(), 'garanti').'.xlsx';
        file_put_contents($tmp, $res->streamedContent());
        $ws = IOFactory::load($tmp)->getActiveSheet();

        $this->assertSame('Kurum Kodu', $ws->getCell('A1')->getValue());
        $this->assertSame('615399', $ws->getCell('B1')->getValue());
        $this->assertSame('11082026', $ws->getCell('B7')->getValue());
        $this->assertSame('TEMMUZ 2026 MAAŞ', $ws->getCell('B9')->getValue());
        $this->assertSame('İsim', $ws->getCell('A12')->getValue());
        $this->assertSame('DURSUN ÇANGAL', $ws->getCell('A13')->getValue());
        $this->assertSame('21877265378', $ws->getCell('B13')->getValue());
        $this->assertSame('TR100006200054400006800625', $ws->getCell('F13')->getValue());
        $this->assertEquals(28075.5, $ws->getCell('G13')->getValue());
        $this->assertSame('KIYMET ÇOLAK', $ws->getCell('A14')->getValue());
        $this->assertEquals(13225.5, $ws->getCell('G14')->getValue()); // kalan
        $this->assertNull($ws->getCell('A15')->getValue()); // tamamı ödenen dahil değil
        unlink($tmp);

        $this->actingAs($this->user)->from('/maas')->get('/maas/banka-dosyasi?year=2026&month=1')->assertRedirect('/maas')->assertSessionHas('error');
    }

    public function test_import_garanti_command(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        $wb = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $ws = $wb->getActiveSheet();
        $ws->fromArray(['Kurum Kodu', '615399'], null, 'A1');
        $ws->fromArray(['İsim', 'TCKN (Opsiyonel)', 'Banka Kodu', 'Şube Kodu', 'Hesap', 'IBAN (Boşluksuz 26 Karakter)', 'Tutar'], null, 'A12');
        $ws->fromArray(['GÜLDANE GÜRBULAK', '14790018886', '62', 544, 6800629, 'TR960006200054400006800629', 28075.5], null, 'A13');
        $ws->fromArray(['İBRAHİM SURHA', null, '62', 28, 6847772, 'TR830006200002800006847772'], null, 'A14');
        $ws->setCellValue('G14', 0); // fromArray 0'ı boş sayar, banka dosyasındaki gibi açıkça yaz
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($wb))->save($tmp);

        $this->artisan('aysha:import-garanti', ['file' => $tmp, '--period' => '2026-07'])->assertSuccessful();

        $g = Employee::where('tc_no', '14790018886')->first();
        $this->assertSame('Güldane', $g->first_name);
        $this->assertSame('Gürbulak', $g->last_name);
        $this->assertSame('544', $g->branch_code);
        $this->assertSame('28075.50', (string) $g->salary);
        $p = SalaryPayment::forPeriod(2026, 7)->where('employee_id', $g->id)->first();
        $this->assertSame('paid', $p->status);
        $this->assertSame('2026-07-11', $p->paid_at->toDateString());

        $i = Employee::where('iban', 'TR830006200002800006847772')->first();
        $this->assertSame('İbrahim', $i->first_name);
        $this->assertSame('pending', SalaryPayment::forPeriod(2026, 7)->where('employee_id', $i->id)->first()->status);

        // İkinci çalıştırma: günceller, çoğaltmaz
        $this->artisan('aysha:import-garanti', ['file' => $tmp])->assertSuccessful();
        $this->assertSame(2, Employee::count());
        unlink($tmp);
    }
}
