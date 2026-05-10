<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Analyze institution infrastructure using heuristic scoring
     * (Gemini API integration optional - falls back to heuristic if no API key)
     */
    public function analyzeInfrastructure(Institution $institution): array
    {
        // If Gemini API key is configured, attempt to use it
        if ($this->apiKey) {
            $result = $this->tryGeminiAnalysis($institution);
            if ($result) {
                return $result;
            }
        }

        // Fallback to heuristic analysis
        return $this->heuristicAnalysis($institution);
    }

    /**
     * Attempt to get analysis from Gemini API
     */
    private function tryGeminiAnalysis(Institution $institution): ?array
    {
        try {
            $prompt = $this->buildPrompt($institution);

            $response = Http::post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->apiKey,
                [
                    'contents' => [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 1024,
                    ]
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $result = $this->parseResponse($text, $institution);
                if ($result) {
                    return $result;
                }
            }
        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Build structured prompt for Gemini
     */
    private function buildPrompt(Institution $institution): string
    {
        return "
Analyze the ICT infrastructure gap for this institution in Lipa City:

Name: {$institution->name}
Type: {$institution->sector_type}
Barangay: {$institution->barangay_name}
Students: {$institution->student_population ?? 'N/A'}
Staff: {$institution->staff_population ?? 'N/A'}
Internet Speed: " . ($institution->internet_speed ? $institution->internet_speed . ' Mbps' : 'Not available') . "
Has Computer Lab: " . ($institution->has_computer_lab ? 'Yes' : 'No') . "
Number of Computers: {$institution->number_of_computers ?? 0}
Has Smart TV: " . ($institution->has_smart_tv ? 'Yes' : 'No') . "
Has PA System: " . ($institution->has_public_address_system ? 'Yes' : 'No') . "

Provide analysis in this exact format:
gap_score: [0-100 number]
infrastructure_narrative: [2-3 sentence description]
policy_recommendation: [specific recommendations]
";
    }

    /**
     * Parse Gemini response
     */
    private function parseResponse(string $text, Institution $institution): ?array
    {
        $gapScore = null;
        $narrative = '';
        $recommendation = '';

        if (!preg_match('/gap_score:\s*(\d+(?:\.\d+)?)/i', $text, $m)) return null;
        $gapScore = (float) $m[1];

        if (!preg_match('/infrastructure_narrative:\s*(.+?)(?=policy_recommendation:|$)/is', $text, $m)) return null;
        $narrative = trim($m[1]);

        if (!preg_match('/policy_recommendation:\s*(.+)/is', $text, $m)) return null;
        $recommendation = trim($m[1]);

        return [
            'gap_score' => min(100, max(0, $gapScore)),
            'infrastructure_narrative' => $narrative,
            'policy_recommendation' => $recommendation,
            'recommendations_detail' => ['source' => 'gemini'],
        ];
    }

    /**
     * Heuristic-based analysis (always available)
     */
    private function heuristicAnalysis(Institution $institution): array
    {
        $score = $this->calculateHeuristicScore($institution);
        
        return [
            'gap_score' => $score,
            'infrastructure_narrative' => $this->buildNarrative($institution, $score),
            'policy_recommendation' => $this->generateRecommendations($institution, $score),
            'recommendations_detail' => ['method' => 'heuristic', 'factors' => $this->getScoreFactors($institution)],
        ];
    }

    /**
     * Calculate heuristic gap score
     */
    private function calculateHeuristicScore(Institution $institution): float
    {
        $score = 0;

        // Internet speed (0-30 points)
        if (!$institution->internet_speed || $institution->internet_speed < 10) {
            $score += 30;
        } elseif ($institution->internet_speed < 25) {
            $score += 20;
        } elseif ($institution->internet_speed < 50) {
            $score += 10;
        } else {
            $score += 0;
        }

        // Computer lab (0-30 points)
        if (!$institution->has_computer_lab) {
            $score += 30;
        } elseif ($institution->number_of_computers < 10) {
            $score += 20;
        } elseif ($institution->number_of_computers < 20) {
            $score += 10;
        } else {
            $score += 0;
        }

        // Smart technology (0-20 points)
        if (!$institution->has_smart_tv) $score += 10;
        if (!$institution->has_public_address_system) $score += 10;

        // Student-computer ratio (0-20 points)
        if ($institution->student_population && $institution->number_of_computers) {
            $ratio = $institution->student_population / max($institution->number_of_computers, 1);
            if ($ratio > 30) $score += 20;
            elseif ($ratio > 15) $score += 10;
            else $score += 0;
        } else {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * Get individual score factors for detailed breakdown
     */
    private function getScoreFactors(Institution $institution): array
    {
        $factors = [];

        if (!$institution->internet_speed || $institution->internet_speed < 25) {
            $factors['internet'] = 'critical';
        } elseif ($institution->internet_speed < 50) {
            $factors['internet'] = 'moderate';
        } else {
            $factors['internet'] = 'adequate';
        }

        $factors['computer_lab'] = $institution->has_computer_lab ? 'available' : 'missing';
        $factors['smart_tv'] = $institution->has_smart_tv ? 'available' : 'missing';
        $factors['pa_system'] = $institution->has_public_address_system ? 'available' : 'missing';

        return $factors;
    }

    /**
     * Build narrative based on heuristic analysis
     */
    private function buildNarrative(Institution $institution, float $score): string
    {
        $issues = [];

        if (!$institution->internet_speed || $institution->internet_speed < 25) {
            $issues[] = "insufficient internet connectivity";
        } elseif ($institution->internet_speed < 50) {
            $issues[] = "moderate internet speed that may not support high-demand applications";
        }

        if (!$institution->has_computer_lab) {
            $issues[] = "lack of dedicated computer laboratory";
        } elseif ($institution->number_of_computers < 20) {
            $issues[] = "insufficient computer units for the student population";
        }

        if (!$institution->has_smart_tv) {
            $issues[] = "absence of smart display technology";
        }

        if (!$institution->has_public_address_system) {
            $issues[] = "no public address system for emergencies";
        }

        if (empty($issues)) {
            return "Infrastructure is adequately equipped with minor room for enhancement.";
        }

        $summary = "Primary gaps include " . implode(', ', array_slice($issues, 0, -1));
        if (count($issues) > 1) {
            $summary .= ", and " . end($issues);
        }

        return $summary . ".";
    }

    /**
     * Generate policy recommendations
     */
    private function generateRecommendations(Institution $institution, float $score): string
    {
        $recommendations = [];

        if ($score >= 70) {
            $recommendations[] = "Urgent infrastructure upgrade required: Install minimum 50 Mbps dedicated internet connection";
            $recommendations[] = "Establish computer lab with at least 20 working stations";
        } elseif ($score >= 40) {
            $recommendations[] = "Upgrade internet bandwidth to 25-50 Mbps";
            $recommendations[] = "Add 10-15 additional computer units";
        } else {
            $recommendations[] = "Maintain current equipment while planning phased upgrades";
        }

        if (!$institution->has_smart_tv) {
            $recommendations[] = "Procure 2-3 smart TVs for interactive learning spaces";
        }

        if (!$institution->has_public_address_system) {
            $recommendations[] = "Install public address system for campus-wide communication";
        }

        return implode('. ', $recommendations) . '.';
    }
}
