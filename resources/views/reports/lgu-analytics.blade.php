<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Lipa City ICT Planning Report</title>
<style>
@page { margin: 42px 42px 55px; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #243247; line-height: 1.5; }
.brand { border-bottom: 3px solid #b91c1c; padding-bottom: 12px; }
.brand img { width: 120px; }
h1 { font-size: 22px; margin: 10px 0 4px; color: #991b1b; }
h2 { font-size: 14px; color: #991b1b; margin-top: 22px; }
h3 { font-size: 11px; margin-bottom: 4px; }
.muted { color: #64748b; }
.box { background: #f1f5f9; padding: 12px; margin: 10px 0; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th { background: #991b1b; color: white; text-align: left; }
td, th { padding: 7px; border-bottom: 1px solid #dbe1e8; overflow-wrap: break-word; }
thead { display: table-header-group; }
tr { page-break-inside: avoid; }
.entry { page-break-inside: avoid; }
h2, h3 { page-break-after: avoid; }
.signature { margin-top: 24px; border-top: 1px solid #94a3b8; padding-top: 8px; }
</style></head>
<body>
<div class="brand"><img src="{{ public_path('images/logo_ict_mapping.png') }}" alt="TALASTAS"><br>
<strong>City of Lipa, Batangas</strong><br>LGU City Planner | ICT decision support</div>
<h1>ICT Gap and Investment Planning Report</h1>
<p class="muted">Generated {{ $generatedAt->format('d M Y, h:i A') }} (Asia/Manila)<br>Prepared by: {{ $preparedBy }}</p>
<p><strong>Scope:</strong> Active schools and barangay halls. Selected analytics records: {{ $records->count() }}.</p>
<p><strong>Filters:</strong> {{ collect($filters)->filter(fn($value) => filled($value))->map(fn($value, $key) => $key . ': ' . $value)->implode(' | ') ?: 'All active in-scope institutions' }}</p>
<h2>1. Executive summary</h2><div class="box">{{ $prescription['summary'] }}</div>
<p>Citywide assessment coverage: {{ $summary['analyzed_institution_count'] }} of {{ $summary['institution_count'] }} active institutions ({{ $summary['analysis_coverage_percentage'] }}%). Awaiting complete scores: {{ $summary['pending_assessment_count'] }}. This coverage figure is citywide; the following results use the selected filters.</p>
<table><thead><tr><th>Selected assessment dimension</th><th style="width: 24%">Mean gap</th></tr></thead><tbody>
@forelse($prescription['dimensions'] as $dimension)<tr><td>{{ $dimension['label'] }}</td><td>{{ number_format($dimension['score'], 1) }}%</td></tr>
@empty<tr><td colspan="2">No calculated data available.</td></tr>@endforelse
</tbody></table>
<h2>2. AI prescription and recommended steps</h2>
<p class="muted">Synthesis of stored institution recommendations. Source labels distinguish AI output from fallback advice. Recommendations require planner validation.</p>
@forelse($prescription['actions'] as $action)
<div class="entry">
<h3>{{ $loop->iteration }}. {{ $action['institution'] }}</h3><p>{{ $action['action'] }}</p><p class="muted">{{ $action['source'] }} | {{ str_replace('_', ' ', $action['urgency']) }}</p>
</div>
@empty<p>No stored recommendations are available. Run institution analytics after validating evidence.</p>@endforelse
<div class="box"><strong>Planning follow-up:</strong> {{ $prescription['next_step'] }}</div>
<h2>3. Institution priorities</h2>
<table><thead><tr><th style="width: 38%">Institution / Barangay</th><th style="width: 15%">ICT gap</th><th>Status / Recommendation</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->institution?->name }}<br><span class="muted">{{ $record->institution?->barangay?->name ?? 'Unassigned' }} | {{ $record->institution?->type }}</span></td><td>{{ $record->gap_score !== null ? number_format($record->gap_score, 1).'%' : 'No data' }}</td><td>{{ str_replace('_', ' ', $record->risk_level ?? 'pending') }}<br>{{ \Illuminate\Support\Str::limit($record->policy_recommendation ?? 'Validate evidence and run analytics.', 350) }}</td></tr>
@empty<tr><td colspan="3">No analytics records match the selected filters.</td></tr>@endforelse
</tbody></table>
<h2>4. Assessment details</h2>
@foreach($records as $record)
<div class="entry">
<h3>{{ $record->institution?->name }}</h3>
<p class="muted">Assessment date: {{ $record->analysis_date?->timezone('Asia/Manila')->format('d M Y, h:i A') ?? 'Not recorded' }}</p>
<p>Infrastructure deficit: {{ $record->infrastructure_deficit_score !== null ? number_format($record->infrastructure_deficit_score, 1).'%' : 'No data' }} | Connectivity divide: {{ $record->connectivity_divide_score !== null ? number_format($record->connectivity_divide_score, 1).'%' : 'No data' }} | Obsolescence proxy: {{ $record->technological_obsolescence_score !== null ? number_format($record->technological_obsolescence_score, 1).'%' : 'No data' }}</p>
@include('ict-inventory._connectivity-assessment', ['assessment' => data_get($record->recommendations_detail, 'connectivity_assessment'), 'compact' => true])
<p>{{ $record->infrastructure_narrative ?? 'No infrastructure narrative available.' }}</p>
<p><strong>Recommendation:</strong> {{ $record->policy_recommendation ?? 'No stored recommendation available.' }}</p>
</div>
@endforeach
<div class="entry">
<h2>5. Methodology and limitations</h2>
<p>Overall gap is the mean of infrastructure deficit, connectivity divide, and technological obsolescence proxy for complete institution assessments. Dimension averages exclude missing values. These are descriptive institution means, not population-weighted measures or compliance ratings.</p>
<p>Infrastructure deficit = defective active applicable units / active applicable units x 100. The numeric acquired speed in the Plan field is treated as Mbps. For each connection, download shortfall = max(acquired Mbps - measured download Mbps, 0) / acquired Mbps x 100 and upload shortfall = max(acquired Mbps - measured upload Mbps, 0) / acquired Mbps x 100. The connection score is their mean; Connectivity Divide is the equal mean of connection scores only when all reported connections have complete usable data. Obsolescence proxy = retired applicable units / total applicable inventory x 100.</p>
<p>Missing scores indicate insufficient evidence, not zero gap. Retired units are a proxy for obsolescence. AI advice does not determine scores, approved budgets, or procurement decisions.</p>
<div class="signature">Prepared by: {{ $preparedBy }}<br><br>Reviewed by: __________________________ &nbsp; Date: ______________<br>City Planning and Development Office</div>
</div>
</body></html>
