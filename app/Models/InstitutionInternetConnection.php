<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionInternetConnection extends Model
{
    protected $fillable = ['connection_number', 'isp', 'plan', 'allocation', 'validity', 'context',
        'advertised_download_mbps', 'download_speed_mbps', 'upload_speed_mbps', 'latency_ms', 'throughput_mbps'];

    protected $casts = ['advertised_download_mbps' => 'decimal:6', 'connection_number' => 'integer', 'download_speed_mbps' => 'decimal:6',
        'upload_speed_mbps' => 'decimal:6', 'latency_ms' => 'decimal:6', 'throughput_mbps' => 'decimal:6'];

    protected static function booted(): void
    {
        foreach (['created', 'updated'] as $action) {
            static::$action(function (self $connection) use ($action): void {
                $actor = auth()->user();
                if (!$actor || !\Illuminate\Support\Facades\Schema::hasTable('user_activity_logs')) {
                    return;
                }
                $fields = array_diff(array_keys($action === 'updated' ? $connection->getChanges() : $connection->getAttributes()), ['created_at', 'updated_at']);
                if (!$fields) {
                    return;
                }
                $record = ['user_id' => $actor->id, 'user_name' => $actor->name, 'action' => $action,
                    'record_type' => 'InstitutionInternetConnection', 'record_id' => (string) $connection->id,
                    'changed_fields' => implode(', ', $fields), 'occurred_at' => now()];
                \Illuminate\Support\Facades\DB::afterCommit(fn () => \Illuminate\Support\Facades\DB::table('user_activity_logs')->insert($record));
            });
        }
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
