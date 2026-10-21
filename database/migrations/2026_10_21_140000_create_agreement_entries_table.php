<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Anlaşma hareketleri: hangi tarihte ne kadar ödeme alındı, ekstra gelir / gider oldu.
 * Eski ekstra ve tahsilat tabloları buraya taşınıp kaldırılır (tahsilat takibi istenmedi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->enum('kind', ['payment', 'income', 'expense'])->default('payment');
            $table->string('label')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
            $table->index(['agreement_id', 'entry_date']);
        });

        if (Schema::hasTable('agreement_payments')) {
            foreach (DB::table('agreement_payments')->get() as $p) {
                DB::table('agreement_entries')->insert([
                    'agreement_id' => $p->agreement_id,
                    'entry_date' => $p->paid_at ?: sprintf('%04d-%02d-01', $p->period_year, $p->period_month ?: 1),
                    'kind' => 'payment',
                    'label' => $p->note,
                    'amount' => $p->amount,
                    'created_at' => $p->created_at, 'updated_at' => $p->updated_at,
                ]);
            }
        }

        if (Schema::hasTable('agreement_extras')) {
            foreach (DB::table('agreement_extras')->get() as $x) {
                DB::table('agreement_entries')->insert([
                    'agreement_id' => $x->agreement_id,
                    'entry_date' => substr((string) $x->created_at, 0, 10),
                    'kind' => $x->kind === 'charge' ? 'income' : 'expense',
                    'label' => $x->label,
                    'amount' => $x->amount,
                    'created_at' => $x->created_at, 'updated_at' => $x->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('agreement_payments');
        Schema::dropIfExists('agreement_extras');
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_entries');
    }
};
