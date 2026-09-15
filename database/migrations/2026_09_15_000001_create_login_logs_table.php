<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('login', 255)->nullable();     // denenen kullanıcı adı / e-posta
            $table->boolean('successful')->default(true);
            $table->string('ip', 45)->nullable();
            $table->string('device', 40)->nullable();     // Masaüstü / Telefon / Tablet
            $table->string('platform', 40)->nullable();   // Windows / macOS / iPhone ...
            $table->string('browser', 40)->nullable();    // Chrome / Safari ...
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
