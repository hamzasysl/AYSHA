<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 30); // cleaning, meal, travel, other
            $table->date('expense_date');
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->nullable(); // boşsa ödendiğinde tutara eşit
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->date('refund_at')->nullable();
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->enum('payment_method', ['transfer', 'cash'])->nullable();
            $table->date('paid_at')->nullable();
            $table->timestamps();

            $table->index('expense_date');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
