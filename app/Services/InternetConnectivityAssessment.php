<?php

namespace App\Services;

class InternetConnectivityAssessment
{
    public const METHOD = 'advertised-speed-shortfall-v1';

    public static function reasonLabel(string $reason): string
    {
        return match ($reason) {
            'plan_price_only' => 'Plan records a price, not a speed.',
            'plan_ambiguous' => 'Advertised download speed is unidentified.',
            'plan_not_recorded' => 'Plan is not recorded.',
            'invalid_advertised_speed' => 'Advertised download speed must be positive.',
            'download_not_recorded' => 'Download speed is not recorded.',
            'advertised_speed_conflict' => 'Recorded advertised download conflicts with the plan speed.',
            'connection_state_conflict' => 'Internet status, count, or connection records conflict.',
            'service_not_recorded' => 'Internet service status is not recorded.',
            'invalid_download' => 'Download measurement is invalid.',
            'missing_connections' => 'Some reported connections have no recorded details.',
            'connection_count_conflict' => 'Reported connection count does not match the records.',
            'connections_not_recorded' => 'Internet connection details are not recorded.',
            default => 'Connectivity data is incomplete.',
        };
    }

    /** Extract only one explicit bit-rate value; never interpret a bare number as Mbps. */
    public function classifyPlan(?string $plan): array
    {
        $plan = trim($plan ?? '');
        $result = ['plan_classification' => 'not_recorded', 'advertised_download_mbps' => null,
            'plan_reason' => 'plan_not_recorded'];
        if ($plan === '') {
            return $result;
        }

        preg_match_all('/(?<![\p{L}\d.,])((?:\d{1,3}(?:,\d{3})+|\d+)(?:\.\d+)?)\s*(kbps|mbps|gbps|kbit\/s|mbit\/s|gbit\/s)\b/iu', $plan, $matches, PREG_OFFSET_CAPTURE);
        if (count($matches[0]) === 1) {
            $offset = $matches[0][0][1];
            $unit = $matches[2][0][0];
            $range = preg_match('~\d\s*(?:(?:[kmg]bps|[kmg]bit/s)\s*)?(?:[-–—/]|to\b)\s*\d|\bor\s+\d~iu', $plan);
            $negative = preg_match('/[-−]\s*$/u', substr($plan, 0, $offset));
            $priceSpeed = preg_match('/(?:₱|\$|\bPHP|\bUSD)\s*$/iu', substr($plan, 0, $offset));
            $uploadOnly = preg_match('/\b(upload|upstream|up(?!\s+to\b))\b/i', $plan);
            // Uppercase B with lowercase ps denotes bytes per second, not bits.
            $bytes = in_array($unit, ['KBps', 'MBps', 'GBps'], true);
            if (!$range && !$negative && !$priceSpeed && !$uploadOnly && !$bytes) {
                $speed = (float) str_replace(',', '', $matches[1][0][0]);
                $speed *= match (strtolower($unit)[0]) { 'g' => 1000, 'k' => 0.001, default => 1 };
                if (is_finite($speed) && $speed > 0) {
                    return ['plan_classification' => 'identifiable', 'advertised_download_mbps' => $speed, 'plan_reason' => null];
                }
                return ['plan_classification' => 'ambiguous', 'advertised_download_mbps' => null,
                    'plan_reason' => 'invalid_advertised_speed'];
            }
        }

        $priceOnly = count($matches[0]) === 0 && preg_match('/\d/', $plan)
            && preg_match('/₱|\bPHP(?=\b|\d)|\bUSD(?=\b|\d)|\$|\bpesos?\b/iu', $plan);
        return ['plan_classification' => $priceOnly ? 'price_only' : 'ambiguous',
            'advertised_download_mbps' => null, 'plan_reason' => $priceOnly ? 'plan_price_only' : 'plan_ambiguous'];
    }

    public function prepareConnection(array $response): array
    {
        $parsed = $this->classifyPlan($response['plan'] ?? null);
        $explicit = $response['advertised_download_mbps'] ?? null;
        $recorded = $explicit !== null && $explicit !== '';
        $result = ['connection_number' => $response['connection_number'] ?? '?', ...$parsed,
            'advertised_speed_source' => $parsed['plan_classification'] === 'identifiable' ? 'plan' : null];
        if ($recorded) {
            if (!$this->validSpeed($explicit, true)) {
                $result = array_replace($result, ['plan_classification' => 'ambiguous', 'plan_reason' => 'invalid_advertised_speed',
                    'advertised_download_mbps' => null, 'advertised_speed_source' => 'recorded']);
            } elseif ($parsed['plan_classification'] === 'identifiable'
                && abs((float) $explicit - $parsed['advertised_download_mbps']) > 0.000001) {
                $result = array_replace($result, ['plan_classification' => 'ambiguous', 'plan_reason' => 'advertised_speed_conflict',
                    'advertised_download_mbps' => null, 'advertised_speed_source' => 'conflict']);
            } else {
                $result = array_replace($result, ['plan_classification' => 'identifiable', 'plan_reason' => null,
                    'advertised_download_mbps' => (float) $explicit, 'advertised_speed_source' => 'recorded']);
            }
        }
        foreach (['download_speed_mbps', 'upload_speed_mbps', 'latency_ms', 'throughput_mbps'] as $field) {
            $value = $response[$field] ?? null;
            $result[$field] = $value === '' ? null : (is_numeric($value) && is_finite((float) $value) ? (float) $value : $value);
        }
        return $result;
    }

    private function validSpeed(mixed $value, bool $positive = false): bool
    {
        return !is_bool($value) && is_numeric($value) && is_finite((float) $value)
            && ($positive ? (float) $value > 0 : (float) $value >= 0) && (float) $value <= 1000000;
    }

    /** Encoding may be incomplete; contradictions are never accepted. */
    public function stateErrors(mixed $status, mixed $reported, array $connections): array
    {
        $errors = [];
        $count = count($connections);
        $quantity = InventoryCounts::quantity($reported);
        if ($status !== null && !is_bool($status)) {
            $errors['has_internet_connection'] = 'Select Yes, No, or Not recorded for Internet service.';
        }
        if ($reported !== null && (InventoryCounts::quantity($reported) === null || $reported > 255)) {
            $errors['reported_internet_connection_count'] = 'Reported connection count must be a whole number from 0 to 255.';
        }
        if (($status === false && ($count > 0 || ($quantity !== null && $quantity > 0)))
            || ($status === true && $quantity === 0)) {
            $errors['has_internet_connection'] = 'Internet service status conflicts with the reported count or saved connections.';
        }
        if ($quantity !== null && $quantity < $count) {
            $errors['reported_internet_connection_count'] = 'Reported connection count cannot be less than the number of saved connections.';
        }
        $numbers = array_column($connections, 'connection_number');
        if (count(array_unique($numbers)) !== $count || count(array_filter($numbers,
            fn ($number) => InventoryCounts::quantity($number) !== null && $number >= 1 && $number <= 255)) !== $count) {
            $errors['internet_connections'] = 'Connection numbers must be unique whole numbers from 1 to 255.';
        }
        return $errors;
    }

    public function assess(array $payload): array
    {
        // Legacy API contract only. Normal jobs always supply individual connection records.
        if (!array_key_exists('internet_connections', $payload) || $payload['internet_connections'] === null) {
            $status = $payload['has_internet_connection'] ?? null;
            if (!array_key_exists('reported_internet_connection_count', $payload)) {
                $payload['reported_internet_connection_count'] = $status === false ? 0 : ($status === true ? 1 : null);
            }
            $payload['internet_connections'] = $status === true ? [[
                'connection_number' => 1, 'plan_classification' => 'identifiable',
                'advertised_download_mbps' => $payload['bandwidth_allocation_mbps'] ?? null,
                'download_speed_mbps' => $payload['download_speed_mbps'] ?? null,
            ]] : [];
        }
        $connections = $payload['internet_connections'];
        $reported = $payload['reported_internet_connection_count'] ?? null;
        $status = $payload['has_internet_connection'] ?? null;
        $count = count($connections);
        $errors = $this->stateErrors($status, $reported, $connections);
        $assessment = ['method' => self::METHOD, 'status' => 'incomplete', 'score' => null,
            'reported_connection_count' => $reported, 'recorded_connection_count' => $count,
            'valid_connection_count' => 0, 'missing_connection_count' => max((is_numeric($reported) ? (int) $reported : 0) - $count, 0),
            'plan_counts' => ['identifiable' => 0, 'price_only' => 0, 'ambiguous' => 0, 'not_recorded' => 0],
            'reason_codes' => [], 'connections' => [], 'messages' => array_values($errors), 'invalid_inputs' => $errors];
        if ($errors !== []) {
            $assessment['reason_codes'][] = 'connection_state_conflict';
        }
        if ($errors === [] && $status === false && $reported !== null && (int) $reported === 0 && $count === 0) {
            return array_replace($assessment, ['status' => 'no_service', 'score' => 100.0]);
        }
        if ($status === null) {
            $assessment['messages'][] = 'Record Internet service status.';
            $assessment['reason_codes'][] = 'service_not_recorded';
        }
        if ($reported === null) {
            $assessment['messages'][] = 'Record the number of Internet connections.';
            $assessment['reason_codes'][] = 'connections_not_recorded';
        } elseif (is_numeric($reported) && $reported > $count) {
            $assessment['messages'][] = 'Some reported connections have no recorded details.';
            $assessment['reason_codes'][] = 'missing_connections';
        }
        foreach ($connections as $connection) {
            // Raw responses are supported locally; the Python contract receives these prepared inputs.
            if (array_key_exists('plan', $connection)) {
                $connection = $this->prepareConnection($connection);
            }
            $number = $connection['connection_number'] ?? '?';
            $classification = $connection['plan_classification'] ?? 'not_recorded';
            if (!array_key_exists($classification, $assessment['plan_counts'])) {
                $classification = 'ambiguous';
            }
            $assessment['plan_counts'][$classification]++;
            $reasons = [];
            $advertised = $connection['advertised_download_mbps'] ?? null;
            $download = $connection['download_speed_mbps'] ?? null;
            if (($connection['plan_reason'] ?? null) === 'advertised_speed_conflict') {
                $reasons[] = 'advertised_speed_conflict';
                $message = 'Recorded advertised download conflicts with the plan speed for connection '.$number.'.';
                $assessment['messages'][] = $message;
                $assessment['invalid_inputs']['internet_connections.'.$number.'.advertised_download_mbps'] = $message;
            } elseif ($classification !== 'identifiable' || !$this->validSpeed($advertised, true)) {
                $reasons[] = $connection['plan_reason'] ?? 'invalid_advertised_speed';
                $assessment['messages'][] = 'Enter advertised download speed in Mbps for connection '.$number.'.';
                if (($advertised !== null && $advertised !== '' && !$this->validSpeed($advertised, true))
                    || ($connection['plan_reason'] ?? null) === 'invalid_advertised_speed') {
                    $assessment['invalid_inputs']['internet_connections.'.$number.'.advertised_download_mbps'] = 'Advertised download must be a positive finite value in Mbps.';
                }
            }
            if (!$this->validSpeed($download)) {
                $reasons[] = $download === null || $download === '' ? 'download_not_recorded' : 'invalid_download';
                $assessment['messages'][] = 'Record a valid measured download speed for connection '.$number.'.';
                if ($download !== null && $download !== '') {
                    $assessment['invalid_inputs']['internet_connections.'.$number.'.download_speed_mbps'] = 'Measured download must be a nonnegative finite value in Mbps.';
                }
            }
            $score = $reasons === [] ? round(max((float) $advertised - (float) $download, 0) / (float) $advertised * 100, 2) : null;
            if ($score !== null) {
                $assessment['valid_connection_count']++;
            }
            $assessment['connections'][] = [...$connection, 'shortfall_percent' => $score, 'reason_codes' => $reasons];
            $assessment['reason_codes'] = array_values(array_unique([...$assessment['reason_codes'], ...$reasons]));
        }
        if ($count > 0 && $status === true && $errors === [] && $assessment['reason_codes'] === []
            && $assessment['valid_connection_count'] === (int) $reported) {
            $assessment['status'] = 'complete';
            $assessment['score'] = round(array_sum(array_column($assessment['connections'], 'shortfall_percent')) / $count, 2);
        }
        return $assessment;
    }
}
