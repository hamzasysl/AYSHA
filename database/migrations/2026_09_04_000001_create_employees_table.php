<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('tc_no', 11)->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('position')->default('Temizlik Personeli');
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('status', ['active', 'passive'])->default('active');
            $table->decimal('salary', 12, 2)->default(0);
            $table->boolean('is_retired')->default(false); // emekli (SGK'lı çalışan değil)
            $table->decimal('meal_allowance', 12, 2)->nullable();   // aylık yemek ücreti (boşsa ayarlardaki varsayılan)
            $table->decimal('travel_allowance', 12, 2)->nullable(); // aylık yol ücreti
            $table->string('bank_name')->nullable();
            $table->string('bank_code', 10)->nullable();   // 62 = Garanti
            $table->string('branch_code', 10)->nullable(); // şube kodu
            $table->string('account_no', 30)->nullable();  // hesap no
            $table->string('iban', 34)->nullable();
            $table->string('account_holder')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
