<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;

class TemporaryUsersSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Temporary System Administrator
        |--------------------------------------------------------------------------
        */
        $admin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'System Administrator',
                'password' => 'AdminTest12345',
                'role' => 'admin',
            ]
        );

        // Admin does not need institution assignments.
        $admin->institutions()->sync([]);


        /*
        |--------------------------------------------------------------------------
        | Temporary ICT Coordinator / Editor
        |--------------------------------------------------------------------------
        */
        $editor = User::updateOrCreate(
            ['email' => 'editor@test.com'],
            [
                'name' => 'ICT Coordinator Test',
                'password' => 'EditorTest12345',
                'role' => 'editor',
            ]
        );

        /*
         * TEMPORARY:
         * Assign the editor to the first two institutions.
         *
         * You can replace this later with specific institution IDs.
         */
        $editorInstitutionIds = Institution::query()
            ->orderBy('id')
            ->limit(2)
            ->pluck('id');

        $editor->institutions()->sync($editorInstitutionIds);


        /*
        |--------------------------------------------------------------------------
        | Temporary LGU City Planner / Viewer
        |--------------------------------------------------------------------------
        */
        $viewer = User::updateOrCreate(
            ['email' => 'viewer@test.com'],
            [
                'name' => 'LGU City Planner Test',
                'password' => 'ViewerTest12345',
                'role' => 'viewer',
            ]
        );

        // Viewer has city-wide read-only access, so no assignment is required.
        $viewer->institutions()->sync([]);
    }
}
