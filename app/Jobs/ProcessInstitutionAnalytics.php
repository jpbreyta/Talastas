<?php

namespace App\Jobs;

use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ProcessInstitutionAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $institution;

    public function __construct(Institution $institution)
    {
        $this->institution = $institution;
    }

    public function handle()
    {
        // 1. Gather Data (Data Understanding)
        $payload = [
            'id' => $this->institution->id,
            'name' => $this->institution->name,
            'internet_speed' => $this->institution->internet_speed,
            'student_population' => $this->institution->student_population,
            'needs_maintenance' => (bool) $this->institution->needs_maintenance,
            'has_computer_lab' => (bool) $this->institution->has_computer_lab,
            'active_computers_count' => $this->institution->number_of_computers,
            // ... map other fields ...
        ];

        // 2. Call Python API
        $response = Http::post('http://127.0.0.1:8000/analyze', $payload);

        if ($response->successful()) {
            $data = $response->json();

            // 3. Save Results
            InstitutionAnalytics::updateOrCreate(
                ['institution_id' => $this->institution->id],
                [
                    'gap_score' => $data['gap_score'],
                    'infrastructure_narrative' => $data['infrastructure_narrative'],
                    'policy_recommendation' => $data['policy_recommendation'],
                    'analysis_date' => now(),
                ]
            );
        }
    }
}