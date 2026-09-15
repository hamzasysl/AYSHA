<?php

use App\Models\Expense;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Ödenmiş yol/yemek kayıtlarını gerçek ödeme şekline çeker: yol elden nakit, yemek yemek kartına yüklenir. */
return new class extends Migration
{
    public function up(): void
    {
        // enum('transfer','cash') idi; 'meal_card' eklenince yetmiyor.
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('payment_method', 20)->nullable()->change();
        });

        foreach (Expense::DEFAULT_METHODS as $category => $method) {
            DB::table('expenses')
                ->where('category', $category)
                ->where('status', 'paid')
                ->where(fn ($q) => $q->whereNull('payment_method')->orWhere('payment_method', 'transfer'))
                ->update(['payment_method' => $method]);
        }

        // Bekleyen kayıtlarda yöntem tutulmaz (ödendiye çekilince kategoriye göre atanır).
        DB::table('expenses')->where('status', 'pending')->update(['payment_method' => null]);
    }

    public function down(): void
    {
        DB::table('expenses')->whereIn('payment_method', ['cash', 'meal_card'])->update(['payment_method' => 'transfer']);
    }
};
