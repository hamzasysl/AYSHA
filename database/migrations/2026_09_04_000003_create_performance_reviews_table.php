<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // değerlendiren
            $table->date('review_date');
            $table->unsignedTinyInteger('attendance');    // devam / dakiklik 1-5
            $table->unsignedTinyInteger('quality');       // iş kalitesi 1-5
            $table->unsignedTinyInteger('attitude');      // tutum / müşteri iletişimi 1-5
            $table->unsignedTinyInteger('score');         // genel puan 1-10
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('review_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
