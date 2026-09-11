<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('deduction', 12, 2)->default(0)->after('amount'); // kesinti (örn. gelmediği günlerin yemek/yol parası)
            $table->string('deduction_note')->nullable()->after('deduction');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('username'); // admin (tam yetki) | owner (patron)
        });
    }

    public function down(): void
    {
        Schema::table('expenses', fn (Blueprint $t) => $t->dropColumn(['deduction', 'deduction_note']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
    }
};
