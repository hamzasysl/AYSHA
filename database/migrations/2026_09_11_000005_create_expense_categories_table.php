<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('label', 60);
            $table->string('icon', 40)->default('fa-receipt');
            $table->string('color', 20)->default('slate');
            $table->boolean('employee_based')->default(true); // personele bağlı ödeme mi (raporlarda Ekstra) yoksa genel gider mi
            $table->boolean('is_system')->default(false);     // yemek/yol gibi silinemeyen
            $table->unsignedSmallInteger('sort')->default(100);
            $table->timestamps();
        });

        $now = now();
        DB::table('expense_categories')->insert([
            ['slug' => 'meal', 'label' => 'Yemek Ücreti', 'icon' => 'fa-utensils', 'color' => 'amber', 'employee_based' => true, 'is_system' => true, 'sort' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'travel', 'label' => 'Yol Parası', 'icon' => 'fa-bus', 'color' => 'sky', 'employee_based' => true, 'is_system' => true, 'sort' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'health', 'label' => 'Sağlık Raporu', 'icon' => 'fa-file-medical', 'color' => 'rose', 'employee_based' => true, 'is_system' => false, 'sort' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'extra', 'label' => 'Ekstra Ödeme', 'icon' => 'fa-hand-holding-dollar', 'color' => 'violet', 'employee_based' => true, 'is_system' => false, 'sort' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'cleaning', 'label' => 'Temizlik Gideri', 'icon' => 'fa-spray-can-sparkles', 'color' => 'emerald', 'employee_based' => false, 'is_system' => false, 'sort' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'other', 'label' => 'Diğer', 'icon' => 'fa-receipt', 'color' => 'slate', 'employee_based' => false, 'is_system' => true, 'sort' => 90, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
