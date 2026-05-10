<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'LGU Administrator',
            'email' => 'admin@lipacity.gov.ph',
            'password' => Hash::make('LGU@2025!'),
            'role' => 'admin',
        ]);
    }
}
