<?php

// Read-only review of configured roles and editor assignments. No credentials or account details are printed.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $report = [
        'role_counts' => App\Models\User::query()->select('role')->selectRaw('COUNT(*) AS account_count')->groupBy('role')->get()->toArray(),
        'unknown_role_count' => App\Models\User::query()->where(function ($q) { $q->whereNotIn('role', ['admin', 'editor', 'viewer'])->orWhereNull('role'); })->count(),
        'editors_without_assignments' => App\Models\User::query()->where('role', 'editor')->doesntHave('institutions')->count(),
        'non_editors_with_assignments' => App\Models\User::query()->where('role', '!=', 'editor')->has('institutions')->count(),
    ];
    echo json_encode($report, JSON_PRETTY_PRINT).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, "Account review could not connect to the configured database. Check database availability and run this script again.\n");
    exit(1);
}
