<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Şirket adı Dura Clean Global olarak değişti; görünen ad ayarları eklendi. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $set = function (string $key, string $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        };

        $current = DB::table('settings')->where('key', 'company_name')->value('value');
        if ($current === null || trim((string) $current) === '' || trim((string) $current) === 'TTB Turizm') {
            $set('company_name', 'Dura Clean Global');
        }

        if (DB::table('settings')->where('key', 'app_tagline')->doesntExist()) {
            $set('app_tagline', 'Personel Yönetimi');
        }
    }

    public function down(): void
    {
        // Ad geri alınmaz; Ayarlar > Şirket & Marka'dan değiştirilebilir.
    }
};
