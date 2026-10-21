<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Anlaşma kayıtları "yapıldı" ya da "ödeme bekleniyor" olabilir. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreement_entries', function (Blueprint $table) {
            // Mevcut kayıtlar gerçekleşmiş hareketlerdir
            $table->enum('status', ['done', 'pending'])->default('done')->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('agreement_entries', fn (Blueprint $t) => $t->dropColumn('status'));
    }
};
