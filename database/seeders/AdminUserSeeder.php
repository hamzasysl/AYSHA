<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@aysha.test')],
            [
                'name' => 'Yönetici',
                'username' => env('ADMIN_USERNAME', 'admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ]
        );
    }
}
