<?php

namespace App\Helpers;

use App\Models\IctInventory;
use App\Models\Institution;

class GapScoreCalculator
{
    /**
     * Compute gap score using the standardized algorithm
     * Objective 3.1: Gap-scoring algorithm for objective scoring of needs
     */
    public static function computeScore(Institution $inst): float
    {
        $score = 0;

        // 1. Internet Speed (Weight: 30 points)
        if (! $inst->internet_speed || $inst->internet_speed == 0) {
            $score += 30;
        } elseif ($inst->internet_speed < 10) {
            $score += 25;
        } elseif ($inst->internet_speed < 25) {
            $score += 20;
        } elseif ($inst->internet_speed < 50) {
            $score += 10;
        } else {
            $score += 0;
        }

        // 2. Computer Lab (Weight: 30 points)
        try {
            $computers = IctInventory::where('institution_id', $inst->id)
                ->where('category', 'computer')
                ->where('status', 'active')
                ->count();
        } catch (\Exception $e) {
            $computers = 0; // Default if table doesn't exist
        }
        if ($computers == 0) {
            $score += 30;
        } elseif ($computers < 10) {
            $score += 25;
        } elseif ($computers < 20) {
            $score += 15;
        } elseif ($computers < 30) {
            $score += 8;
        } else {
            $score += 0;
        }

        // 3. Smart Display & Projectors (Total: 15 points)
        try {
            $smartTvs = IctInventory::where('institution_id', $inst->id)
                ->where('category', 'smart_tv')
                ->where('status', 'active')
                ->count();
        } catch (\Exception $e) {
            $smartTvs = 0;
        }
        if ($smartTvs == 0) {
            $score += 10;
        }
        try {
            $projectors = IctInventory::where('institution_id', $inst->id)
                ->where('category', 'projector')
                ->where('status', 'active')
                ->count();
        } catch (\Exception $e) {
            $projectors = 0;
        }
        if ($projectors == 0) {
            $score += 5;
        }

        // 4. Public Address System (Weight: 10 points)
        try {
            $paSystems = IctInventory::where('institution_id', $inst->id)
                ->where('category', 'pa_system')
                ->where('status', 'active')
                ->count();
        } catch (\Exception $e) {
            $paSystems = 0;
        }
        if ($paSystems == 0) {
            $score += 10;
        }

        // 5. Student-Computer Ratio (Weight: 15 points)
        if ($inst->student_population && $computers > 0) {
            $ratio = $inst->student_population / $computers;
            if ($ratio > 40) {
                $score += 15;
            } elseif ($ratio > 25) {
                $score += 10;
            } elseif ($ratio > 15) {
                $score += 5;
            } else {
                $score += 0;
            }
        } else {
            $score += 10;
        }

        // 6. Maintenance Status (Bias: +5 points if maintenance needed)
        if ($inst->needs_maintenance) {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Compute data completeness score (0-100) for quality flagging
     * Scope: System relies on manual data entry, so completeness indicates reliability
     */
    public static function computeDataCompleteness(Institution $inst): int
    {
        $requiredFields = [
            'name', 'type', 'barangay', 'latitude', 'longitude',
            'has_computer_lab', 'has_smart_tv', 'has_public_address_system',
        ];

        $optionalFields = [
            'student_population', 'staff_population', 'internet_speed',
            'number_of_computers', 'has_projected_whiteboard', 'needs_maintenance',
        ];

        $requiredScore = 0;
        foreach ($requiredFields as $field) {
            if (! empty($inst->$field) || $inst->$field === false) { // false is valid for booleans
                $requiredScore += 20; // 7 fields * 20 = 140, but we'll cap later
            }
        }

        $optionalScore = 0;
        $optionalCount = count($optionalFields);
        foreach ($optionalFields as $field) {
            if (! empty($inst->$field) || $inst->$field === false || $inst->$field === 0) {
                $optionalScore += (60 / $optionalCount); // optional worth 40% of total
            }
        }

        $total = $requiredScore + $optionalScore;

        return min(100, (int) round($total));
    }

    public static function getLevel(float $score): string
    {
        return $score >= 80 ? 'critical' : ($score >= 50 ? 'warning' : 'adequate');
    }
}
