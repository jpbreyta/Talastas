<?php

// In-memory fixtures only. No application records or external services are used.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections' => ['sqlite' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
]], 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge();

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Unsafe database');
}
$checks = 0;
function checkAnalysis(bool $ok, string $message): void
{
    global $checks;
    if (! $ok) {
        throw new RuntimeException($message);
    }
    $checks++;
}
Schema::create('barangays', function (Blueprint $t) { $t->id(); $t->string('name'); });
Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); });
Schema::create('institutions', function (Blueprint $t) {
    $t->id(); $t->string('name'); $t->string('type'); $t->integer('barangay_id'); $t->boolean('is_active');
});
Schema::create('institution_user', function (Blueprint $t) { $t->integer('institution_id'); $t->integer('user_id'); });
Schema::create('institution_analytics', function (Blueprint $t) {
    $t->id(); $t->integer('institution_id');
    foreach (['gap_score', 'infrastructure_deficit_score', 'connectivity_divide_score', 'technological_obsolescence_score'] as $field) {
        $t->float($field)->nullable();
    }
    $t->string('risk_level'); $t->string('processing_status'); $t->dateTime('analysis_date');
    $t->text('recommendations_detail')->nullable(); $t->text('ai_priorities')->nullable();
    $t->text('infrastructure_narrative')->nullable(); $t->text('policy_recommendation')->nullable();
});
DB::table('barangays')->insert([['id' => 1, 'name' => 'Fixture North'], ['id' => 2, 'name' => 'Fixture South']]);
DB::table('users')->insert(['id' => 2, 'name' => 'Fixture Editor']);
$fixtureRows = [
    [1, 'Alpha', 'School', 1, true, 20, 10, 30, 20, '2026-11-01 10:00:00'],
    [2, 'Beta', 'School', 1, true, 60, 40, 80, 60, '2026-11-02 10:00:00'],
    [3, 'Gamma', 'Barangay Hall', 2, true, null, 0, null, 0, '2026-11-03 10:00:00'],
    [4, 'Delta', 'School', 2, true, 80, 80, 80, 80, '2026-10-01 10:00:00'],
    [5, 'Inactive', 'School', 2, false, 100, 100, 100, 100, '2026-11-01 10:00:00'],
];
$action = 'Existing first action '.str_repeat('preserve this recommendation ', 8);
foreach ($fixtureRows as [$id, $name, $type, $barangay, $active, $gap, $infra, $connectivity, $retirement, $date]) {
    DB::table('institutions')->insert(['id' => $id, 'name' => $name, 'type' => $type, 'barangay_id' => $barangay, 'is_active' => $active]);
    DB::table('institution_analytics')->insert(['id' => $id, 'institution_id' => $id, 'gap_score' => $gap,
        'infrastructure_deficit_score' => $infra, 'connectivity_divide_score' => $connectivity,
        'technological_obsolescence_score' => $retirement, 'risk_level' => $gap === null ? 'insufficient_data' : 'assessed',
        'processing_status' => 'completed', 'analysis_date' => $date, 'infrastructure_narrative' => "Original $name narrative.",
        'policy_recommendation' => "Original $name policy.", 'recommendations_detail' => json_encode([
            'ai_status' => 'generated', 'priorities' => [['priority' => $action, 'rationale' => 'Existing rationale.', 'urgency' => 'immediate']],
            'calculation_scope' => ['label' => 'Hardware + Network'],
            'assessment' => ['unavailable_reasons' => $gap === null ? ['connectivity_divide_score' => 'Fixture missing upload.'] : []],
            'connectivity_assessment' => ['status' => $gap === null ? 'incomplete' : 'complete',
                'valid_connection_count' => $gap === null ? 0 : 1, 'reported_connection_count' => 1,
                'recorded_connection_count' => 1, 'plan_counts' => ['identifiable' => 1, 'price_only' => 0, 'ambiguous' => 0, 'not_recorded' => 0],
                'connections' => [['connection_number' => 1, 'advertised_download_mbps' => 100, 'download_speed_mbps' => 0, 'upload_speed_mbps' => null]],
            ],
        ]),
    ]);
}
DB::table('institution_user')->insert(['institution_id' => 1, 'user_id' => 2]);
$admin = new User(['role' => 'admin', 'name' => 'Fixture Admin']); $admin->id = 1;
$editor = new User(['role' => 'editor', 'name' => 'Fixture Editor']); $editor->id = 2;
$controller = new App\Http\Controllers\AnalyticsController;
function analysisRequest(array $params = [], ?User $user = null): Request
{
    global $app, $admin;
    $user ??= $admin;
    auth()->setUser($user);
    $request = Request::create('/analytics', 'GET', $params);
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession(app('session')->driver());
    $app->instance('request', $request);
    return $request;
}
$period = ['date_from' => '2026-11-01', 'date_to' => '2026-11-30'];
$selected = $controller->index(analysisRequest($period))->getData();
checkAnalysis($selected['prescription']['record_count'] === 3 && $selected['prescription']['complete_count'] === 2, 'Completeness counts selected analytics records with non-null composites');
checkAnalysis($selected['averageGapScore'] === 40.0 && $selected['prescription']['average_gap'] === 40.0, 'Cards and prescription have identical selected means');
checkAnalysis($selected['connectivityDivide'] === 55.0, 'Dimension means still exclude null values');
checkAnalysis(count($selected['barangayScores']) === 1 && $selected['barangayScores'][0]['gap_score'] === 40.0 && $selected['barangayScores'][0]['institution_count'] === 2, 'Barangay ranking remains the mean of available selected scores');
checkAnalysis($selected['analytics']->pluck('institution_id')->all() === [2, 1, 3], 'Default order preserves descending scores with nulls last');
$named = $controller->index(analysisRequest($period + ['sort' => 'name', 'order' => 'asc']))->getData();
checkAnalysis($named['analytics']->pluck('institution_id')->all() === [1, 2, 3], 'Name sorting remains supported');
$filtered = $controller->index(analysisRequest($period + ['q' => 'Alpha', 'barangay' => 1, 'type' => 'School']))->getData();
checkAnalysis($filtered['averageGapScore'] === 20.0 && $filtered['prescription']['record_count'] === 1 && $filtered['barangayScores'][0]['gap_score'] === 20.0, 'Search and filters share one applied scope');
$assigned = $controller->index(analysisRequest($period, $editor))->getData();
checkAnalysis($assigned['averageGapScore'] === 20.0 && $assigned['prescription']['record_count'] === 1 && $assigned['barangayScores'][0]['institution_count'] === 1, 'Editor cards/chart reuse the existing assigned-institution scope');
$missing = $controller->index(analysisRequest($period + ['risk' => 'insufficient_data']))->getData();
checkAnalysis($missing['averageGapScore'] === null && $missing['prescription']['complete_count'] === 0 && $missing['prescription']['record_count'] === 1, 'Incomplete scores do not become zero composites');
$empty = $controller->index(analysisRequest($period + ['q' => 'not-present']))->getData();
checkAnalysis($empty['analytics']->isEmpty() && $empty['barangayScores']->isEmpty(), 'Empty selection remains empty');
try {
    $controller->index(analysisRequest(['date_from' => '2026-11-30', 'date_to' => '2026-11-01']));
    throw new RuntimeException('Reversed dates accepted');
} catch (Illuminate\Validation\ValidationException $error) {
    checkAnalysis(isset($error->errors()['date_to']), 'Existing server date validation rejects reversed dates');
}
$request = analysisRequest($period + ['barangay' => 1, 'type' => 'School']);
$csv = $controller->export($request);
ob_start(); ($csv->getCallback())(); $contents = ob_get_clean();
$rows = array_map('str_getcsv', array_filter(explode("\n", trim($contents))));
checkAnalysis(count($rows) === 3 && $rows[1][0] === 'Beta' && $rows[2][0] === 'Alpha', 'CSV exports the same filtered records and ranking');
$csv = $controller->export(analysisRequest($period + ['risk' => 'insufficient_data']));
ob_start(); ($csv->getCallback())(); $contents = ob_get_clean();
$rows = array_map('str_getcsv', array_filter(explode("\n", trim($contents))));
checkAnalysis($rows[1][3] === '' && $rows[1][4] === 'insufficient_data', 'CSV retains missing-score context instead of a zero score');
$data = $controller->index(analysisRequest($period))->getData();
$source = str_replace("@extends('layouts.app')", '', file_get_contents(__DIR__.'/../resources/views/analytics.blade.php'))."\n@yield('content')";
$html = Blade::render($source, $data + ['errors' => new Illuminate\Support\ViewErrorBag], deleteCachedView: true);
libxml_use_internal_errors(true);
$document = new DOMDocument; $document->loadHTML('<?xml encoding="UTF-8">'.$html); $xpath = new DOMXPath($document);
checkAnalysis($xpath->query('//tr[contains(@class,"analysis-priority-details") and @hidden]')->length === 3, 'All supporting rows start collapsed');
checkAnalysis($xpath->query('//button[@data-analysis-row-toggle and @aria-expanded="false"]')->length === 3, 'Each disclosure has a keyboard-operable button and collapsed state');
checkAnalysis($xpath->query('//tr[contains(@class,"analysis-priority-details")]//table')->length === 0, 'Expanded details contain no nested tables');
checkAnalysis(str_contains($html, e($action)) && str_contains($html, 'Original Alpha narrative.') && str_contains($html, 'Existing rationale.'), 'Full recommendation and assessment text are preserved');
checkAnalysis(str_contains($html, 'Assessment Completeness: 2 of 3 selected analytics records'), 'Completeness uses the actual selected population');
checkAnalysis(str_contains($html, 'bar heights are relative') || str_contains($html, 'Bar heights are relative'), 'Chart explains its relative plotting scale');
checkAnalysis(str_contains($html, '40.0%') && str_contains($html, 'not complete barangay-wide assessments'), 'Barangay values and incomplete coverage explanation remain available');
$links = $xpath->query('//a[@title="Filter by Fixture North"]');
parse_str(parse_url(html_entity_decode($links[0]->getAttribute('href')), PHP_URL_QUERY), $params);
checkAnalysis($params['date_from'] === $period['date_from'] && $params['barangay'] === '1', 'Barangay links preserve reporting dates');
$pdfRequest = analysisRequest($period + ['barangay' => 1]);
$pdfData = $controller->index($pdfRequest)->getData();
$summaryMethod = new ReflectionMethod($controller, 'summaryData');
$reportHtml = view('reports.lgu-analytics', [
    'records' => $pdfData['analytics']->getCollection(), 'prescription' => $pdfData['prescription'],
    'summary' => $summaryMethod->invoke($controller), 'generatedAt' => now(), 'preparedBy' => 'Fixture Admin',
    'filters' => $pdfRequest->only(['q', 'barangay', 'type', 'risk', 'date_from', 'date_to']),
])->render();
checkAnalysis(str_contains($reportHtml, '2026-11-01') && str_contains($reportHtml, 'Original Alpha narrative.') && !str_contains($reportHtml, 'Original Delta narrative.'), 'PDF report HTML preserves reporting scope and original report contents');
checkAnalysis(class_exists(Dompdf\Options::class) && class_exists(Dompdf\Dompdf::class), 'PDF dependencies must be installed. Run composer install to restore the locked dependencies.');
$pdf = $controller->exportPdf($pdfRequest);
checkAnalysis($pdf->headers->get('Content-Type') === 'application/pdf' && str_starts_with($pdf->getContent(), '%PDF-')
    && str_ends_with(rtrim($pdf->getContent()), '%%EOF'), 'PDF export renders a complete PDF document for the reporting scope');
checkAnalysis(str_contains($pdf->headers->get('Content-Disposition'), 'attachment; filename="lipa-city-ict-planning-')
    && str_contains($pdf->headers->get('Cache-Control'), 'no-store'), 'Report is a downloadable private, non-cacheable PDF');
$viewer = new User(['role' => 'viewer', 'name' => 'Fixture Planner']);
$plannerPdf = $controller->exportPdf(analysisRequest($period + ['barangay' => 1], $viewer));
checkAnalysis(str_starts_with($plannerPdf->getContent(), '%PDF-'), 'Authorized LGU planner can export the PDF report');
try {
    $controller->exportPdf(analysisRequest($period, $editor));
    throw new RuntimeException('Unauthorized editor PDF export accepted');
} catch (Symfony\Component\HttpKernel\Exception\HttpException $error) {
    checkAnalysis($error->getStatusCode() === 403, 'Existing editor PDF-export restriction remains enforced');
}
$emptyPdf = $controller->exportPdf(analysisRequest(['date_from' => '2000-01-01', 'date_to' => '2000-01-02']));
checkAnalysis(str_starts_with($emptyPdf->getContent(), '%PDF-'), 'Reporting scope with no records still produces a valid PDF');

echo "PASS: $checks analysis scope, ranking, rendering and export checks (isolated SQLite; no live records).\n";
