<?php

// Probe only random, short-lived keys. Never flush the application cache.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void { fwrite(STDERR, 'Cache capability probe failed: '.get_class($error).PHP_EOL); exit(1); });
$name = config('cache.default');
$repository = Illuminate\Support\Facades\Cache::store($name);
$store = $repository->getStore();
$driver = config('cache.stores.'.$name.'.driver');
$result = ['driver' => $driver, 'store' => get_class($store),
    'lock_provider' => $store instanceof Illuminate\Contracts\Cache\LockProvider];
if (!in_array($driver, ['database', 'redis', 'file'], true) || !$result['lock_provider']) {
    echo json_encode($result + ['suitable' => false, 'reason' => 'Persistent storage with atomic locks is required.']).PHP_EOL;
    exit(2);
}
if (($argv[1] ?? '') === '--compete') {
    $probeLock = $repository->lock($argv[2].':lock', 15);
    $acquired = $probeLock->get();
    echo json_encode(['persisted' => $repository->get($argv[2]) === 'probe', 'competing_lock_denied' => !$acquired]);
    if ($acquired) $probeLock->release();
    exit;
}
$key = 'profile-cache-probe:'.bin2hex(random_bytes(16));
$lock = $repository->lock($key.':lock', 15);
try {
    $repository->put($key, 'probe', 30);
    app('cache')->forgetDriver($name);
    $second = Illuminate\Support\Facades\Cache::store($name);
    $result['persistent_across_repositories'] = $second->get($key) === 'probe';
    $result['first_lock_acquired'] = $lock->get();
    $process = proc_open([PHP_BINARY, __FILE__, '--compete', $key], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $child = json_decode(stream_get_contents($pipes[1]), true);
    fclose($pipes[1]); fclose($pipes[2]);
    $result['cross_process_verified'] = proc_close($process) === 0 && ($child['persisted'] ?? false) && ($child['competing_lock_denied'] ?? false);
    $other = $second->lock($key.':lock', 15);
    $result['competing_lock_denied'] = !$other->get();
    if (!$result['competing_lock_denied']) $other->release();
    $lock->release();
    $result['released_lock_reacquired'] = $other->get();
    if ($result['released_lock_reacquired']) $other->release();
    $result['suitable'] = !in_array(false, array_intersect_key($result, array_flip([
        'persistent_across_repositories', 'first_lock_acquired', 'competing_lock_denied', 'released_lock_reacquired', 'cross_process_verified',
    ])), true);
    echo json_encode($result).PHP_EOL;
} finally {
    $lock->release();
    $repository->forget($key);
}
