<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('list_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);         // position | bank | leave_type
            $table->string('slug', 60);         // leave_type için kayıtlarda saklanan anahtar; diğerlerinde etiketle aynı
            $table->string('label', 80);
            $table->json('meta')->nullable();   // leave_type: {deduct: bool, icon: 'fa-...'}
            $table->boolean('is_system')->default(false);
            $table->unsignedSmallInteger('sort')->default(100);
            $table->timestamps();
            $table->unique(['type', 'slug']);
        });

        $now = now();
        $rows = [];
        foreach (['Temizlik Personeli', 'Ekip Lideri', 'Şoför', 'Süpervizör', 'Ofis Personeli', 'Genel Koordinatör'] as $i => $p) {
            $rows[] = ['type' => 'position', 'slug' => $p, 'label' => $p, 'meta' => null, 'is_system' => false, 'sort' => ($i + 1) * 10, 'created_at' => $now, 'updated_at' => $now];
        }
        foreach (['Garanti BBVA', 'Ziraat Bankası', 'İş Bankası', 'Yapı Kredi', 'Akbank', 'Halkbank', 'VakıfBank', 'QNB', 'DenizBank', 'Kuveyt Türk', 'Papara', 'Enpara'] as $i => $b) {
            $rows[] = ['type' => 'bank', 'slug' => $b, 'label' => $b, 'meta' => null, 'is_system' => false, 'sort' => ($i + 1) * 10, 'created_at' => $now, 'updated_at' => $now];
        }
        $leave = [
            ['annual', 'Yıllık izin', true, 'fa-umbrella-beach', true],
            ['unpaid', 'Ücretsiz izin', false, 'fa-calendar-minus', false],
            ['marriage', 'Evlilik', false, 'fa-ring', false],
            ['birth', 'Doğum', false, 'fa-baby', false],
            ['death', 'Vefat', false, 'fa-hands-praying', false],
            ['sick', 'Raporlu', false, 'fa-file-medical', true],
            ['absence', 'Devamsızlık (gelmedi)', false, 'fa-user-xmark', true],
        ];
        foreach ($leave as $i => [$slug, $label, $deduct, $icon, $system]) {
            $rows[] = ['type' => 'leave_type', 'slug' => $slug, 'label' => $label, 'meta' => json_encode(['deduct' => $deduct, 'icon' => $icon]), 'is_system' => $system, 'sort' => ($i + 1) * 10, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('list_items')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('list_items');
    }
};
