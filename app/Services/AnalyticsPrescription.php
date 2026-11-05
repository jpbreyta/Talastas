<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AnalyticsPrescription
{
    public function build(Collection $records): array
    {
        $complete = $records->filter(fn ($row) => $row->gap_score !== null);
        $dimensions = collect([
            'Infrastructure Deficit' => 'infrastructure_deficit_score',
            'Connectivity Divide' => 'connectivity_divide_score',
            'Technological Obsolescence Proxy' => 'technological_obsolescence_score',
        ])->map(fn ($field, $label) => ['label' => $label, 'score' => $records->whereNotNull($field)->avg($field)])
            ->filter(fn ($item) => $item['score'] !== null)->sortByDesc('score');
        $concern = $dimensions->first();
        $planningAction = match ($concern['label'] ?? null) {
            'Connectivity Divide' => 'Prioritize verifying Internet performance against identifiable advertised download speeds at institutions with measured shortfalls, then assess service improvements using the verified results.',
            'Infrastructure Deficit' => 'Prioritize inspecting recorded defective active ICT units and assessing repair options before proposing replacements.',
            'Technological Obsolescence Proxy' => 'Prioritize validating retired ICT inventory and reviewing replacement needs against current operational requirements; retirement alone does not establish equipment age.',
            default => 'Complete inventory and connectivity evidence before selecting an investment priority.',
        };
        $actions = $records->sortByDesc('gap_score')->flatMap(function ($row) {
            $details = $row->recommendations_detail ?? [];
            $priorities = $row->ai_priorities ?: ($details['priorities'] ?? []);
            if (!is_array($priorities)) {
                $priorities = [];
            }
            if ($priorities === [] && filled($row->policy_recommendation)) {
                $priorities = [['action' => $row->policy_recommendation]];
            }
            return collect($priorities)->map(fn ($item) => is_array($item) ? array_merge($item, ['action' => $item['action'] ?? $item['priority'] ?? null]) : [])
                ->filter(fn ($item) => is_string($item['action'] ?? null) && trim($item['action']) !== '')
                ->map(fn ($item) => [
                    'institution' => $row->institution?->name ?? 'Institution unavailable',
                    'action' => $item['action'],
                    'urgency' => $item['urgency'] ?? 'for review',
                    'source' => ($details['ai_status'] ?? '') === 'generated' ? 'AI recommendation' : 'Stored recommendation / fallback',
                ]);
        })->take(5)->values();

        return [
            'record_count' => $records->count(),
            'complete_count' => $complete->count(),
            'average_gap' => $complete->isEmpty() ? null : round($complete->avg('gap_score'), 2),
            'dimensions' => $dimensions->values()->all(),
            'summary' => $complete->isEmpty()
                ? 'No complete ICT gap assessments are available for this selection. Complete inventory and connectivity evidence before prioritizing investments.'
                : sprintf('%d of %d selected analytics records have complete scores. The mean ICT gap is %.1f%%.%s', $complete->count(), $records->count(), $complete->avg('gap_score'), $concern ? sprintf(' The largest measured driver is %s (%.1f%%).', $concern['label'], $concern['score']) : ''),
            'actions' => $actions->all(),
            'next_step' => $records->count() > $complete->count()
                ? $planningAction . ' Validate incomplete inventory and bandwidth measurements before finalizing the investment plan.'
                : $planningAction . ' Confirm the institution actions with focal persons, assign responsible offices, and obtain cost estimates.',
        ];
    }
}
