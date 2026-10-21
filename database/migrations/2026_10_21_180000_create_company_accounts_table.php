<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Şirketin banka hesapları: hangi tahsilat hangi hesaba geliyor (KDV dahil / hariç vb.). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('label');                      // kullanım amacı: "KDV dahil tahsilat" gibi
            $table->string('bank_name')->nullable();      // hangi banka
            $table->string('account_holder')->nullable(); // hesap sahibi / ünvan
            $table->string('iban', 34)->nullable();
            $table->string('account_no', 30)->nullable();
            $table->string('branch_code', 10)->nullable();
            $table->string('corp_code', 20)->nullable();  // Garanti kurum kodu (maaş dosyası)
            $table->boolean('is_payroll')->default(false); // maaş dosyası bu hesapla üretilir
            $table->string('note')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Ayarlardaki Garanti maaş bilgileri hesap kaydına taşınır
        if (Schema::hasTable('settings')) {
            $get = fn (string $k) => DB::table('settings')->where('key', $k)->value('value');
            $corp = $get('bank_corp_code');
            $branch = $get('bank_branch_code');
            $account = $get('bank_account');

            if (trim((string) $corp) !== '' || trim((string) $branch) !== '' || trim((string) $account) !== '') {
                DB::table('company_accounts')->insert([
                    'label' => 'Maaş ödeme hesabı',
                    'bank_name' => 'Garanti BBVA',
                    'account_holder' => $get('company_name'),
                    'account_no' => $account,
                    'branch_code' => $branch,
                    'corp_code' => $corp,
                    'is_payroll' => true,
                    'sort' => 10,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_accounts');
    }
};
