<?php

namespace App\Services;

use Illuminate\Support\Collection;

class InventoryCounts
{
    public const AVAILABILITY_FIELDS = ['active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'];

    public static function quantity(mixed $value): ?int
    {
        return (is_int($value) && $value >= 0) || (is_string($value) && preg_match('/^[0-9]+$/D', $value) && (float) $value <= PHP_INT_MAX) ? (int) $value : null;
    }

    public static function status(array $counts): string
    {
        $recorded = collect(self::AVAILABILITY_FIELDS)
            ->filter(fn ($field) => self::quantity($counts[$field] ?? null) !== null)->count();

        $totalRecorded = self::quantity($counts['total_count'] ?? null) !== null;

        return match (true) {
            !$totalRecorded && $recorded === 0 => 'Availability Not Recorded',
            $totalRecorded && $recorded === count(self::AVAILABILITY_FIELDS) => 'Availability Recorded',
            default => 'Availability Partially Recorded',
        };
    }

    /** A complete sum is unavailable if even one source quantity is unknown. */
    public static function sum(Collection $items, string $field): ?int
    {
        return $items->isNotEmpty() && $items->every(fn ($item) => self::quantity(data_get($item, $field)) !== null)
            ? (int) $items->sum($field) : null;
    }

    /** Integer/range rules run before these consistency checks. */
    public static function errors(array $counts): array
    {
        $total = self::quantity($counts['total_count'] ?? null);
        $errors = $total === null ? ['total_count' => 'Enter Total for a recorded inventory item. Availability fields may be left blank.'] : [];
        foreach (['total_count', ...self::AVAILABILITY_FIELDS] as $field) {
            if (isset($counts[$field]) && $counts[$field] !== '' && self::quantity($counts[$field]) === null) {
                $errors[$field] = 'Must be a nonnegative integer.';
            }
        }
        foreach (self::AVAILABILITY_FIELDS as $field) {
            $value = self::quantity($counts[$field] ?? null);
            if ($total !== null && $value !== null && $value > $total) {
                $errors[$field] = ($field === 'defective_active_count' ? 'Defective within Active' : ucfirst(str_replace('_count', '', $field))).' units cannot exceed Total units.';
            }
        }
        $withinActive = self::quantity($counts['defective_active_count'] ?? null);
        foreach (['active_count', 'defective_count', 'total_count'] as $populationField) {
            if ($withinActive !== null && isset($counts[$populationField]) && $counts[$populationField] !== '' && self::quantity($counts[$populationField]) === null) {
                $errors['defective_active_count'] = 'Recorded population counts must be valid to validate Defective within Active.';
            }
        }
        foreach (['active_count' => 'Active', 'defective_count' => 'general Defective'] as $field => $label) {
            $population = self::quantity($counts[$field] ?? null);
            if ($withinActive !== null && $population !== null && $withinActive > $population) {
                $errors['defective_active_count'] = 'Defective within Active cannot exceed '.$label.'.';
            }
        }
        return $errors;
    }
}
