<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['cache.default' => 'array', 'session.driver' => 'array']);
$checks = 0;
function loaderCheck(bool $value, string $message): void
{
    global $checks;
    if (! $value) {
        throw new RuntimeException($message);
    }
    $checks++;
}
$html = Illuminate\Support\Facades\Blade::render('<x-talastas-loader id="fixtureLoader" variant="page" :active="false" />', [], deleteCachedView: true);
libxml_use_internal_errors(true);
$document = new DOMDocument;
$document->loadHTML('<?xml encoding="UTF-8">'.$html);
$xpath = new DOMXPath($document);
loaderCheck($xpath->query('//*[@id="fixtureLoader" and @hidden and @aria-hidden="true" and @role="status"]')->length === 1, 'Inactive page overlay is hidden and has an accessible status role');
$image = $xpath->query('//img')->item(0);
loaderCheck(str_ends_with($image->getAttribute('src'), '/images/logo_ict_mapping.png') && $image->getAttribute('alt') === '', 'Official icon-only asset is reused without a redundant spoken wordmark');
loaderCheck($xpath->query('//*[contains(@class,"talastas-loader-ring")]')->length === 1, 'One loading ring surrounds the stationary logo');
loaderCheck($xpath->query('//*[contains(@class,"talastas-loader-dots")]')->length === 0, 'No decorative loading dots');
loaderCheck(trim($xpath->query('//*[contains(@class,"talastas-loader-label")]')->item(0)->textContent) === 'Loading', 'Visible loading label contains no ellipsis or wordmark');
loaderCheck(str_contains($html, 'Loading content'), 'Screen-reader status is provided');
$compact = Illuminate\Support\Facades\Blade::render('<x-talastas-loader variant="compact" label="Updating institutions" />', [], deleteCachedView: true);
loaderCheck(str_contains($compact, 'talastas-loader-compact') && str_contains($compact, 'Updating institutions'), 'Compact local indicator preserves its accessible operation label');
$inline = Illuminate\Support\Facades\Blade::render('<x-talastas-loader />', [], deleteCachedView: true);
loaderCheck(str_contains($inline, 'talastas-loader-inline') && !str_contains($inline, 'talastas-loader-overlay'), 'Inline variant does not block the viewport');
$partial = view('partials.global-loader')->render();
loaderCheck(substr_count($partial, 'id="globalLoader"') === 1 && str_contains($partial, 'showGlobalLoader'), 'Existing global partial renders one reusable loader and preserves its public API');
echo "PASS: $checks branded-loader component checks (Blade rendering; no requests or database changes).\n";
