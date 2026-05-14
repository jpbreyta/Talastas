<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionsSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            [
                'name' => 'Batangas State University - Lipa Campus',
                'type' => 'School',
                'barangay_id' => \App\Models\Barangay::where('name', 'Marawoy')->first()?->id ?? 1,
                'latitude' => 13.9567000,
                'longitude' => 121.1630000,

                'student_population' => null,
                'staff_population' => null,

                'isp_provider' => 'PLDT',
                'internet_speed' => 100.00,

                'has_computer_lab' => true,
                'number_of_computers' => 100,

                'has_projected_whiteboard' => true,
                'has_smart_tv' => true,
                'has_public_address_system' => true,

                'needs_maintenance' => false,
                'notes' => 'Main pilot institution for ICT mapping.',

                'is_pilot_site' => true,
                'data_completeness_score' => 85,
                'requires_audit' => true,

                'last_audited_at' => now(),
                'audited_by' => null,
                'audit_notes' => 'Initial audit completed.',

                'is_active' => true,
            ],

            [
                'name' => 'Kolehiyo ng Lungsod ng Lipa',
                'type' => 'School',
                'barangay_id' => \App\Models\Barangay::where('name', 'Marawoy')->first()?->id ?? 1,
                'latitude' => 13.9625600,
                'longitude' => 121.1751500,

                'student_population' => null,
                'staff_population' => null,

                'isp_provider' => 'Converge',
                'internet_speed' => 50.00,

                'has_computer_lab' => true,
                'number_of_computers' => 40,

                'has_projected_whiteboard' => true,
                'has_smart_tv' => false,
                'has_public_address_system' => true,

                'needs_maintenance' => false,
                'notes' => 'Secondary pilot school.',

                'is_pilot_site' => true,
                'data_completeness_score' => 80,
                'requires_audit' => true,

                'last_audited_at' => now(),
                'audited_by' => null,
                'audit_notes' => 'Pending hardware verification.',

                'is_active' => true,
            ],

            [
                'name' => 'Lipa City Hall',
                'type' => 'Local Government Office',
                'barangay_id' => \App\Models\Barangay::where('name', 'Marawoy')->first()?->id ?? 1,
                'latitude' => 13.9573379,
                'longitude' => 121.1647025,

                'student_population' => null,
                'staff_population' => 200,

                'isp_provider' => 'PLDT Enterprise',
                'internet_speed' => 100.00,

                'has_computer_lab' => false,
                'number_of_computers' => 75,

                'has_projected_whiteboard' => true,
                'has_smart_tv' => true,
                'has_public_address_system' => true,

                'needs_maintenance' => false,
                'notes' => 'Government office with centralized ICT resources.',

                'is_pilot_site' => true,
                'data_completeness_score' => 90,
                'requires_audit' => false,

                'last_audited_at' => now(),
                'audited_by' => null,
                'audit_notes' => 'Audit passed successfully.',

                'is_active' => true,
            ],
        ];

        foreach ($institutions as $institution) {
            Institution::withoutEvents(function () use ($institution) {
                return Institution::create($institution);
            });
        }

        $this->command->info('Institutions seeded successfully.');
    }
}