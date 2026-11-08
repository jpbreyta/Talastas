<?php

namespace App\Services;

class IctGapBaselineCalculator
{
    public function calculate(array $payload): array
    {
        $fields = ['total', 'active', 'retired', 'functional', 'defective', 'defective_active'];
        $rows = $payload['inventory'] ?? [array_combine(
            array_map(fn ($field) => $field.'_count', $fields),
            array_map(fn ($field) => $payload[$field.'_applicable_inventory_count'] ?? null, $fields)
        )];
        $rows = array_map(function ($row) {
            if (array_key_exists('functional_count_recorded', $row) && $row['functional_count_recorded'] === false) {
                $row['functional_count'] = null;
            }
            return $row;
        }, $rows);
        $missing = [];
        $invalid = $payload['inventory_invalid_inputs'] ?? [];
        $errors = [];
        $inputs = [];
        $recorded = [];
        foreach ($fields as $field) {
            $column = $field.'_count';
            $values = array_map(fn ($row) => InventoryCounts::quantity($row[$column] ?? null), $rows);
            $recorded[$column] = count(array_filter($values, fn ($value) => $value !== null));
            $inputs[$column] = count($rows) > 0 && $recorded[$column] === count($rows) ? array_sum($values) : null;
        }
        foreach ($rows as $index => $row) {
            $errors[$index] = InventoryCounts::errors($row);
            foreach ($fields as $field) {
                $key = $field.'_count';
                if (($row[$key] ?? null) === null || $row[$key] === '') {
                    $missing[] = 'inventory.'.$index.'.'.$key;
                }
            }
            foreach ($errors[$index] as $field => $message) {
                $invalid['inventory.'.$index.'.'.$field] = $message;
            }
        }
        $infrastructureReason = $this->inventoryReason($rows, $errors, ['active_count', 'defective_active_count'], $payload);
        $retirementReason = $this->inventoryReason($rows, $errors, ['total_count', 'retired_count'], $payload);
        if ($infrastructureReason === null && $inputs['active_count'] === 0) {
            $infrastructureReason = 'Active count must be greater than zero.';
        }
        if ($retirementReason === null && $inputs['total_count'] === 0) {
            $retirementReason = 'Total count must be greater than zero.';
        }
        $infrastructure = $infrastructureReason === null
            ? round($inputs['defective_active_count'] / $inputs['active_count'] * 100, 2) : null;
        $retirement = $retirementReason === null
            ? round($inputs['retired_count'] / $inputs['total_count'] * 100, 2) : null;
        $connectivity = (new InternetConnectivityAssessment)->assess($payload);
        $connectivityReason = $connectivity['score'] === null
            ? ($connectivity['messages'][0] ?? 'Record complete Internet connection inputs.') : null;
        if ($connectivityReason !== null) {
            $missing[] = 'complete_internet_connection_inputs';
        }
        foreach ($connectivity['invalid_inputs'] ?? [] as $key => $message) {
            $invalid[$key] = $message;
        }
        $scores = [$infrastructure, $connectivity['score'], $retirement];
        $overall = !in_array(null, $scores, true) ? round(array_sum($scores) / 3, 2) : null;
        return [
            'gap_score' => $overall,
            'risk_level' => $overall === null ? 'insufficient_data' : 'assessed',
            'infrastructure_deficit_score' => $infrastructure,
            'connectivity_divide_score' => $connectivity['score'],
            'technological_obsolescence_score' => $retirement,
            'processing_status' => 'completed',
            'assessment_status' => $overall === null ? 'insufficient_data' : 'assessed',
            'missing_inputs' => $missing,
            'invalid_inputs' => $invalid,
            'unavailable_reasons' => array_filter([
                'infrastructure_deficit_score' => $infrastructureReason,
                'connectivity_divide_score' => $connectivityReason,
                'technological_obsolescence_score' => $retirementReason,
            ], fn ($value) => $value !== null),
            'inventory_inputs' => ['totals' => $inputs, 'recorded_row_counts' => $recorded,
                'included_rows' => count($rows), 'coverage' => $payload['inventory_coverage'] ?? 'saved_applicable_rows',
                'missing_expected_inventory' => $payload['missing_expected_inventory'] ?? []],
        ];
    }

    private function inventoryReason(array $rows, array $errors, array $required, array $payload): ?string
    {
        if (($payload['missing_expected_inventory'] ?? []) !== []) {
            return 'Record the expected applicable inventory: '.implode(', ', $payload['missing_expected_inventory']).'.';
        }
        if ($rows === []) {
            return 'Record inventory in the selected calculation scope.';
        }
        foreach ($rows as $index => $row) {
            foreach ($required as $field) {
                if (isset($errors[$index][$field])) {
                    return $errors[$index][$field];
                }
                $raw = $row[$field] ?? null;
                if ($raw === null || $raw === '') {
                    return match ($field) {
                        'active_count' => 'Record Active inventory counts.',
                        'defective_active_count' => 'Record defective units within Active inventory.',
                        'total_count' => 'Record Total inventory counts.',
                        default => 'Record Retired inventory counts.',
                    };
                }
            }
        }
        return null;
    }

    public function fromPythonResult(array $result, array $payload): array
    {
        $local = $this->calculate($payload);
        foreach (['gap_score', 'infrastructure_deficit_score', 'connectivity_divide_score', 'technological_obsolescence_score'] as $field) {
            $pythonScore = $result[$field] ?? null;
            if ($pythonScore !== $local[$field]) {
                throw new \UnexpectedValueException('Python analytics result does not match the verified local calculation for '.$field.'.');
            }
        }
        foreach (['risk_level', 'assessment_status', 'processing_status', 'missing_inputs', 'invalid_inputs', 'unavailable_reasons', 'inventory_inputs'] as $field) {
            if (($result[$field] ?? null) != $local[$field]) {
                throw new \UnexpectedValueException('Python analytics result does not match the verified local calculation for '.$field.'.');
            }
        }

        return array_replace($local, collect($result)->only([
            'gap_score', 'risk_level', 'infrastructure_deficit_score',
            'connectivity_divide_score', 'technological_obsolescence_score',
            'processing_status', 'assessment_status', 'missing_inputs',
            'invalid_inputs', 'unavailable_reasons', 'inventory_inputs',
        ])->all());
    }
}
