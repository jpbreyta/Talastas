<?php

namespace App\Providers;

use App\Models\AuditRecord;
use App\Models\IctInventory;
use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use App\Observers\AuditRecordObserver;
use App\Observers\IctInventoryObserver;
use App\Observers\InstitutionAnalyticsObserver;
use App\Observers\InstitutionObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ([Institution::class, IctInventory::class, AuditRecord::class, \App\Models\User::class] as $modelClass) {
            foreach (['created', 'updated', 'deleted'] as $action) {
                $modelClass::$action(function ($model) use ($action): void {
                    $actor = auth()->user();
                    if (!$actor || !\Illuminate\Support\Facades\Schema::hasTable('user_activity_logs')) return;
                    $fields = array_values(array_diff(array_keys($action === 'updated' ? $model->getChanges() : $model->getAttributes()), ['password','remember_token','updated_at','created_at']));
                    if ($action === 'updated' && $fields === []) return;
                    $record = ['user_id'=>$actor->id,'user_name'=>$actor->name,'action'=>$action,'record_type'=>class_basename($model),'record_id'=>(string)$model->getKey(),'changed_fields'=>implode(', ', $fields),'occurred_at'=>now()];
                    if ($model instanceof Institution && in_array($action, ['created', 'updated'], true)) {
                        $capture = request()->attributes->get('institution_location_capture', []);
                        foreach (['location_source', 'actor_latitude', 'actor_longitude', 'gps_accuracy', 'gps_verified'] as $key) {
                            $record[$key] = $capture[$key] ?? null;
                        }
                    }
                    \Illuminate\Support\Facades\DB::afterCommit(fn() => \Illuminate\Support\Facades\DB::table('user_activity_logs')->insert($record));
                });
            }
        }

        Institution::observe(
            InstitutionObserver::class
        );

        IctInventory::observe(
            IctInventoryObserver::class
        );

        \App\Models\InstitutionInternetConnection::observe(
            \App\Observers\InstitutionInternetConnectionObserver::class
        );

        InstitutionAnalytics::observe(
            InstitutionAnalyticsObserver::class
        );

        AuditRecord::observe(
            AuditRecordObserver::class
        );

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
