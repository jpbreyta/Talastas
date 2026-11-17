<?php

// Render fixture models without connecting to the application database or AI services.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections' => ['sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
]], 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge();
auth()->setUser(new App\Models\User(['role' => 'admin']));
$request = Illuminate\Http\Request::create('/institutions/1');
$app->instance('request', $request);

use App\Models\IctInventory;
use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use Illuminate\Support\Facades\Blade;

$checks = 0;
function detailCheck(bool $condition, string $message): void
{
    global $checks;
    if (! $condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}
$narrative = "Original finding. Preserve every detail, including 0.0%.\nSecond original paragraph <unaltered>.";
$scores = ['gap_score' => null, 'infrastructure_deficit_score' => 0, 'connectivity_divide_score' => null,
    'technological_obsolescence_score' => 0, 'inventory_inputs' => ['rows' => [
        ['label' => 'Fixture <equipment>', 'active_count' => 10, 'defective_active_count' => 0,
            'defective_count' => 0, 'retired_count' => 0],
    ]], 'unavailable_reasons' => ['connectivity_divide_score' => 'Fixture measured upload is not recorded.'],
    'invalid_inputs' => []];
$analytics = new InstitutionAnalytics(['processing_status' => 'completed', 'processing_stage' => 'completed',
    'infrastructure_narrative' => $narrative, 'policy_recommendation' => 'Existing recommendation unchanged.',
    'analysis_date' => '2026-11-13 09:30:00', ...array_intersect_key($scores, array_flip([
        'gap_score', 'infrastructure_deficit_score', 'connectivity_divide_score', 'technological_obsolescence_score',
    ])), 'recommendations_detail' => ['assessment' => $scores, 'provider' => 'groq',
        'model' => 'fixture-model', 'decision_support_version' => 'fixture-version', 'score_source' => 'python',
        'calculation_scope' => ['label' => 'Hardware + Network'], 'connectivity_assessment' => [
            'reported_connection_count' => 1, 'valid_connection_count' => 0,
            'messages' => ['Fixture measured upload is not recorded.'],
        ], 'priorities' => [
            ['priority' => 'First saved priority', 'rationale' => 'First saved rationale.', 'urgency' => 'immediate'],
            ['priority' => 'Second saved priority', 'rationale' => 'Second saved rationale.', 'urgency' => 'short_term'],
            ['priority' => 'Third saved priority', 'rationale' => 'Third saved rationale.', 'urgency' => 'medium_term'],
        ]]]);
function renderPlanning(InstitutionAnalytics $analytics): string
{
    return view('institutions._planning-assessment', ['analytics' => $analytics,
        'recommendationDetails' => $analytics->recommendations_detail])->render();
}
$html = renderPlanning($analytics);
detailCheck(str_contains($html, 'Connectivity Findings') && str_contains($html, 'Infrastructure Findings') && str_contains($html, 'Data Completeness'), 'Stored assessment has labeled sections');
libxml_use_internal_errors(true);
$document = new DOMDocument;
$document->loadHTML('<?xml encoding="UTF-8">'.$html);
$xpath = new DOMXPath($document);
detailCheck($xpath->query('//details[@class="institution-original-assessment" and not(@open)]')->length === 1, 'Detailed findings start collapsed');
detailCheck($xpath->query('//li[not(ancestor::details)]')->length === 0, 'Component breakdown and detailed warning lists stay out of the default view');
detailCheck($xpath->query('//details//section[@aria-label="Inventory component breakdown"]//li')->length === 1, 'Existing inventory component breakdown remains inside the disclosure');
detailCheck(str_contains($html, 'View detailed findings') && str_contains($html, 'Hide detailed findings'), 'Native disclosure includes both state labels');
detailCheck(str_contains($html, e($narrative)), 'Full original narrative must remain escaped and unchanged');
detailCheck(str_contains($html, 'Infrastructure Deficit:') && str_contains($html, '0.0%'), 'Confirmed zero percentages remain zero');
detailCheck(str_contains($html, 'Connectivity Divide:') && str_contains($html, 'Not calculated'), 'Missing derived metrics are unavailable');
detailCheck(substr_count($html, 'Fixture measured upload is not recorded.') === 1, 'Repeated structured warnings are deduplicated');
detailCheck(str_contains($html, 'Fixture &lt;equipment&gt;'), 'Structured labels are escaped');
$analytics->processing_stage = 'outdated';
$html = renderPlanning($analytics);
detailCheck(!str_contains($html, 'Connectivity Findings') && str_contains($html, e($narrative)), 'Outdated snapshot cannot be presented as current structured findings');
$analytics->processing_stage = 'completed';
$analytics->infrastructure_deficit_score = 50;
$html = renderPlanning($analytics);
detailCheck(!str_contains($html, 'Connectivity Findings'), 'Mismatched snapshot falls back to exact narrative');
$analytics->infrastructure_deficit_score = 0;
$details = $analytics->recommendations_detail;
unset($details['assessment']);
$analytics->recommendations_detail = $details;
$html = renderPlanning($analytics);
detailCheck(str_contains($html, e($narrative)) && !str_contains($html, 'Connectivity Findings'), 'Legacy text remains intact without sentence/keyword parsing');
$details['assessment'] = $scores;
$analytics->recommendations_detail = $details;

$institution = new Institution(['name' => 'Fixture Institution', 'type' => 'School', 'latitude' => 13.94, 'longitude' => 121.16]);
$institution->id = 1;
$institution->setRelation('institutionAnalytics', $analytics);
$institution->setRelation('barangay', null);
$institution->setRelation('auditRecords', collect());
$institution->setRelation('internetConnections', collect());
$zero = new IctInventory(['component_type' => 'hardware', 'asset_name' => 'Fixture inventory', 'asset_id' => 1,
    'total_count' => 0, 'active_count' => 0, 'retired_count' => 0, 'functional_count' => 0,
    'functional_count_recorded' => true, 'defective_count' => 0, 'defective_active_count' => 0]);
$zero->setRelation('component', null);
$zero->asset_id = 1;
$institution->setRelation('ictInventory', collect([$zero]));
$source = str_replace("@extends('layouts.app')", '', file_get_contents(__DIR__.'/../resources/views/institutions/show.blade.php'))."\n@yield('content')";
$render = fn () => Blade::render($source, ['institution' => $institution], deleteCachedView: true);
$html = $render();
detailCheck(str_contains($html, 'Recorded Units: 0') && str_contains($html, 'Functional Units: 0'), 'Summary cards preserve recorded zeros');
detailCheck(preg_match('/<details[^>]*class="institution-report-details[^>]*>/', $html) === 1, 'Report Details is collapsed by default');
foreach (['Groq', 'fixture-model', 'fixture-version', 'Hardware + Network', 'Nov 13, 2026 9:30 AM'] as $value) {
    detailCheck(str_contains($html, $value), 'Stored metadata preserved: '.$value);
}
detailCheck(str_contains($html, 'Generated: Nov 13, 2026 9:30 AM'), 'Generation date remains visible outside collapsed details');
detailCheck(strpos($html, 'First saved priority') < strpos($html, 'Second saved priority') && strpos($html, 'Second saved priority') < strpos($html, 'Third saved priority'), 'Saved priority order is unchanged');
detailCheck(str_contains($html, 'First saved rationale.') && str_contains($html, 'Existing recommendation unchanged.'), 'Saved recommendation text is unchanged');
$zero->total_count = null;
$zero->functional_count_recorded = false;
$html = $render();
detailCheck(str_contains($html, 'Recorded Units: Not recorded') && str_contains($html, 'Functional Units: Not recorded'), 'Null totals and historical unrecorded Functional counts never become zero');
detailCheck(str_contains($html, 'Partially recorded') && str_contains($html, 'missing or invalid'), 'Compact availability warning preserves its meaning');
$institution->setRelation('ictInventory', collect());
$html = $render();
detailCheck(str_contains($html, 'Recorded Units: Not recorded'), 'Empty inventory is unknown, not confirmed zero');
detailCheck(str_contains($html, 'https://www.google.com/maps?q=13.94,121.16') && str_contains($html, 'fetchInstitutionOsmBoundary'), 'Existing location links and boundary control are preserved');
echo "PASS: $checks institution details rendering checks (fixture models; no live database or AI calls).\n";
