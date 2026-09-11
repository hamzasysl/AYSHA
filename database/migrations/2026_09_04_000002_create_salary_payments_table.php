<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);      // prim
            $table->decimal('deduction', 12, 2)->default(0);  // kesinti
            $table->decimal('advance', 12, 2)->default(0);    // avans (maaştan düşülür)
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0); // fazla ödemeden geri alınan
            $table->date('refund_at')->nullable();
            $table->enum('status', ['pending', 'partial', 'paid'])->default('pending');
            $table->enum('payment_method', ['transfer', 'cash'])->nullable();
            $table->date('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period_year', 'period_month']);
            $table->index(['period_year', 'period_month']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
