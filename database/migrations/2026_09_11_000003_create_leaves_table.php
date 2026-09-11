<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedSmallInteger('annual_leave_days')->default(14)->after('travel_allowance'); // yıllık izin hakkı (gün)
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // annual, unpaid, marriage, birth, death, sick, absence
            $table->unsignedSmallInteger('leave_year'); // iznin ait olduğu yıl
            $table->date('start_date');
            $table->date('end_date');
            $table->date('return_date')->nullable(); // iş başı tarihi
            $table->decimal('days', 5, 1); // iş günü
            $table->boolean('deduct_annual')->default(false); // yıllık izinden düşülsün mü
            $table->string('requested_by')->nullable(); // izni talep eden
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'leave_year']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn('annual_leave_days'));
    }
};
