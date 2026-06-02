<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::updateOrCreate(
            ['email' => 'admin@kreisgg.de'],
            [
                'name' => 'IT Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
        );

        AdminUser::updateOrCreate(
            ['email' => 'viewer@kreisgg.de'],
            [
                'name' => 'IT Betrachter',
                'password' => Hash::make('password'),
                'role' => 'viewer',
            ],
        );
    }
}
