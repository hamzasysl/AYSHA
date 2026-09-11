<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Üretim için sadece yönetici hesabı. Demo verisi için: php artisan db:seed --class=DemoSeeder */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
    }
}
