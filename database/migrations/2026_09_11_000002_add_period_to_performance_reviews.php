<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_reviews', function (Blueprint $table) {
            $table->unsignedSmallInteger('period_year')->default(0)->after('review_date');
            $table->unsignedTinyInteger('period_month')->default(0)->after('period_year');
        });

        // Var olan kayıtlar: değerlendirme tarihinin bir önceki ayı
        foreach (DB::table('performance_reviews')->get(['id', 'review_date']) as $r) {
            $d = \Illuminate\Support\Carbon::parse($r->review_date)->startOfMonth()->subMonth();
            DB::table('performance_reviews')->where('id', $r->id)->update(['period_year' => $d->year, 'period_month' => $d->month]);
        }

        // Aynı personel + dönem için birden fazla kayıt varsa en yenisi kalsın
        $dupes = DB::table('performance_reviews')
            ->select('employee_id', 'period_year', 'period_month', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as c'))
            ->groupBy('employee_id', 'period_year', 'period_month')->having('c', '>', 1)->get();
        foreach ($dupes as $d) {
            DB::table('performance_reviews')->where('employee_id', $d->employee_id)->where('period_year', $d->period_year)
                ->where('period_month', $d->period_month)->where('id', '!=', $d->keep_id)->delete();
        }

        Schema::table('performance_reviews', function (Blueprint $table) {
            $table->unique(['employee_id', 'period_year', 'period_month'], 'reviews_employee_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('performance_reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_employee_period_unique');
            $table->dropColumn(['period_year', 'period_month']);
        });
    }
};
