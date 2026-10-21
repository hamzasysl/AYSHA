<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Anlaşmalar: temizlik hizmeti verilen kurumlar, ekstraları ve aylık tahsilatları. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                   // kurum adı
            $table->enum('type', ['recurring', 'one_time'])->default('recurring');
            $table->enum('status', ['active', 'passive'])->default('active');
            $table->string('contact_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->decimal('m2', 10, 2)->nullable();                 // alan
            $table->decimal('m2_rate', 12, 2)->nullable();            // m² başı fiyat (KDV hariç)
            $table->decimal('amount', 12, 2)->default(0);             // ana tutar, KDV hariç
            $table->decimal('vat_rate', 5, 2)->default(20);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('payment_day')->nullable();   // ayın kaçında tahsil ediliyor
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['status', 'type']);
        });

        Schema::create('agreement_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->enum('kind', ['charge', 'cost'])->default('charge'); // ekstra ücret (gelir) / ekstra gider
            $table->decimal('amount', 12, 2)->default(0);                // KDV hariç
            $table->boolean('recurring')->default(true);                 // her ay tekrar eder mi
            $table->timestamps();
        });

        Schema::create('agreement_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month')->nullable();  // tek seferliklerde boş olabilir
            $table->decimal('amount', 12, 2)->default(0);             // tahsil edilecek tutar (KDV dahil)
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->date('paid_at')->nullable();
            $table->string('method', 20)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['agreement_id', 'period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_payments');
        Schema::dropIfExists('agreement_extras');
        Schema::dropIfExists('agreements');
    }
};
