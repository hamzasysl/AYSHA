<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Silinmiş gider/izin kayıtlarından arta kalan notları temizler (panodaki 404'lerin kaynağı). */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'App\Models\Employee' => 'employees',
            'App\Models\Expense' => 'expenses',
            'App\Models\Leave' => 'leaves',
        ];

        foreach ($tables as $type => $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $ids = DB::table($table)->pluck('id')->all();

            DB::table('notes')
                ->where('notable_type', $type)
                ->when($ids !== [], fn ($q) => $q->whereNotIn('notable_id', $ids))
                ->delete();
        }

        // Tablodaki tipi hiç tanımadığımız notlar
        DB::table('notes')->whereNotIn('notable_type', array_keys($tables))->delete();
    }

    public function down(): void
    {
        // Silinen notlar geri getirilemez.
    }
};
