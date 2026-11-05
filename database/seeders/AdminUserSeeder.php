<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = trim((string) config('seeding.admin.email'));
        $adminPassword = (string) config('seeding.admin.password');

        if ($adminPassword === '') {
            $this->command?->warn(
                'Admin user was not seeded because ADMIN_SEED_PASSWORD is not configured.'
            );

            return;
        }

        if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->command?->warn('Admin user was not seeded because ADMIN_SEED_EMAIL is not configured with a valid email address.');

            return;
        }

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'LGU Administrator',
                'password' => $adminPassword,
                'role' => 'admin',
            ]
        );
    }
}
