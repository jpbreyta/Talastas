<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Validation\ValidationException;

class InternetConnectionChanges
{
    /** Pure merge/validation, also used by isolated tests. Omission never means deletion. */
    public function prepare(array $current, array $input, ?bool $status, ?int $reported): array
    {
        $status = array_key_exists('has_internet_connection', $input)
            ? (($input['has_internet_connection'] === null || $input['has_internet_connection'] === '') ? null : (bool) $input['has_internet_connection']) : $status;
        $reported = array_key_exists('number_of_connections', $input)
            ? InventoryCounts::quantity($input['number_of_connections']) : $reported;
        $rows = $current;
        $removed = $input['removed_connection_numbers'] ?? [];
        foreach ($removed as $number) {
            if (!isset($current[$number])) {
                throw ValidationException::withMessages(['q10.removed_connection_numbers' => 'A connection selected for removal no longer exists. Reload the form.']);
            }
            if (array_key_exists($number, $input['connections'] ?? [])) {
                throw ValidationException::withMessages(['q10.connections' => 'A removed connection cannot also be updated.']);
            }
            unset($rows[$number]);
        }
        foreach ($input['connections'] ?? [] as $number => $values) {
            if (!isset($rows[$number]) && !collect($values)->contains(fn ($value) => $value !== null && $value !== '')) {
                continue;
            }
            $rows[$number] = array_replace($rows[$number] ?? ['connection_number' => (int) $number], $values);
        }
        $assessment = new InternetConnectivityAssessment;
        $errors = [];
        foreach ($assessment->stateErrors($status, $reported, array_values($rows)) as $key => $message) {
            $errors['q10.'.($key === 'reported_internet_connection_count' ? 'number_of_connections' : $key)] = $message;
        }
        foreach ($rows as $number => $row) {
            $prepared = $assessment->prepareConnection($row);
            if (in_array($prepared['plan_reason'], ['advertised_speed_conflict', 'invalid_advertised_speed'], true)
                && isset($row['advertised_download_mbps'])) {
                $errors['q10.connections.'.$number.'.advertised_download_mbps'] = $assessment::reasonLabel($prepared['plan_reason']);
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
        return ['status' => $status, 'reported' => $reported, 'rows' => $rows, 'removed' => $removed];
    }

    /** Caller holds the institution lock inside its inventory transaction. */
    public function save(Institution $institution, array $input): void
    {
        $current = $institution->internetConnections()->lockForUpdate()->get()->keyBy('connection_number');
        $changes = $this->prepare($current->map->getAttributes()->all(), $input,
            $institution->has_internet_connection, $institution->q10_number_of_connections);
        foreach ($changes['removed'] as $number) {
            $current[$number]->delete();
        }
        foreach ($changes['rows'] as $number => $values) {
            $connection = $current->get($number) ?? $institution->internetConnections()->make(['connection_number' => (int) $number]);
            $connection->fill(array_intersect_key($values, array_flip($connection->getFillable())));
            if ($connection->isDirty()) {
                $connection->save();
            }
        }
        $institution->has_internet_connection = $changes['status'];
        $institution->q10_number_of_connections = $changes['reported'];
        if ($institution->isDirty()) {
            $institution->save();
        }
    }
}
