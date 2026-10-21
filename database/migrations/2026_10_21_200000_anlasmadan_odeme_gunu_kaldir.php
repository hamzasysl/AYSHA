<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Anlaşmalarda "ödeme günü" alanı kullanılmıyor. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('agreements', 'payment_day')) {
            Schema::table('agreements', fn (Blueprint $t) => $t->dropColumn('payment_day'));
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('agreements', 'payment_day')) {
            Schema::table('agreements', fn (Blueprint $t) => $t->unsignedTinyInteger('payment_day')->nullable());
        }
    }
};
