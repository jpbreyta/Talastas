@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">ICT Gap Analytics Dashboard</h1>
                    <p class="text-gray-600 mt-1">Lipa City Infrastructure Assessment & Decision Support</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Last Updated:</span>
                    <span class="text-sm font-medium text-gray-700">{{ now()->format('M d, Y') }}</span>
                </div>
            </div>

            <!-- Summary Cards with Gap Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-600 uppercase tracking-wider">Citywide Gap Score</h3>
                    <p class="text-3xl font-bold text-blue-900 mt-1">{{ number_format($averageGapScore ?? 0, 1) }}%</p>
                    <p class="text-xs text-blue-600 mt-1">Overall deficit</p>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-green-600 uppercase tracking-wider">Institutions</h3>
                    <p class="text-3xl font-bold text-green-900 mt-1">{{ $institutionCount ?? 0 }}</p>
                    <p class="text-xs text-green-600 mt-1">Total locations</p>
                </div>

                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-red-600 uppercase tracking-wider">Critical Areas</h3>
                    <p class="text-3xl font-bold text-red-900 mt-1">{{ $criticalAreas ?? 0 }}</p>
                    <p class="text-xs text-red-600 mt-1">Gap ≥ 80%</p>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-yellow-600 uppercase tracking-wider">Needs Attention</h3>
                    <p class="text-3xl font-bold text-yellow-900 mt-1">{{ $needsAttention ?? 0 }}</p>
                    <p class="text-xs text-yellow-600 mt-1">Gap 50-79%</p>
                </div>
            </div>

            <!-- Data Quality & Scope Monitoring (Per 1.4 Scope & Limitations) -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-purple-600 uppercase tracking-wider">Pilot Sites</h3>
                    <p class="text-3xl font-bold text-purple-900 mt-1">{{ $pilotSites ?? 0 }}</p>
                    <p class="text-xs text-purple-600 mt-1">Representative locations</p>
                </div>

                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-orange-600 uppercase tracking-wider">Missing Critical Data</h3>
                    <p class="text-3xl font-bold text-orange-900 mt-1">{{ $missingCritical ?? 0 }}</p>
                    <p class="text-xs text-orange-600 mt-1">Needs verification</p>
                </div>

                <div class="bg-teal-50 border border-teal-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-teal-600 uppercase tracking-wider">Audits Pending</h3>
                    <p class="text-3xl font-bold text-teal-900 mt-1">{{ $needsAudit ?? 0 }}</p>
                    <p class="text-xs text-teal-600 mt-1">Field inspection</p>
                </div>

                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-indigo-600 uppercase tracking-wider">Completeness</h3>
                    <p class="text-3xl font-bold text-indigo-900 mt-1">{{ number_format(($averageCompleteness ?? 0), 0) }}%</p>
                    <p class="text-xs text-indigo-600 mt-1">Avg record quality</p>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="barangayFilter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Barangay</label>
                        <select id="barangayFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Barangays</option>
                            @foreach($barangays ?? [] as $barangay)
                                <option value="{{ $barangay }}">{{ $barangay }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="typeFilter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Type</label>
                        <select id="typeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="School">School</option>
                            <option value="Hospital">Hospital</option>
                            <option value="Government">Government</option>
                            <option value="Library">Library</option>
                            <option value="Community Center">Community Center</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button id="exportBtn" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-medium">
                            Export Report
                        </button>
                    </div>
                </div>
            </div>

            <!-- Gap Score Heat Map by Barangay -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Barangay-Level Gap Distribution (Objective 3.2)</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    @foreach($barangayScores ?? [] as $barangay => $score)
                        <div class="relative p-3 rounded-lg border border-gray-200 {{ $score >= 80 ? 'bg-red-50 border-red-200' : ($score >= 50 ? 'bg-yellow-50 border-yellow-200' : 'bg-green-50 border-green-200') }}">
                            <div class="text-xs text-gray-500 uppercase">{{ $barangay }}</div>
                            <div class="text-xl font-bold {{ $score >= 80 ? 'text-red-700' : ($score >= 50 ? 'text-yellow-700' : 'text-green-700') }}">
                                {{ number_format($score, 0) }}%
                            </div>
                            <div class="mt-1 h-1 rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-full {{ $score >= 80 ? 'bg-red-500' : ($score >= 50 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ $score }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    * Heat map shows average gap score per barangay (red = critical, yellow = needs attention, green = adequate)
                </p>
            </div>

            <!-- Analytics Data Table -->
            <div class="overflow-x-auto">
                <table id="analyticsTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Institution</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barangay</th>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sector</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pilot</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quality</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gap</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Recommendations</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($analytics ?? [] as $analytic)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $analytic->institution->name ?? 'N/A' }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">
                                {{ Str::limit($analytic->institution->barangay_name ?? 'N/A', 12) }}
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-1.5 py-0.5 text-xs rounded bg-blue-50 text-blue-700">
                                    {{ Str::limit($analytic->institution->sector_type ?? $analytic->institution->type ?? 'N/A', 10) }}
                                </span>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                @if($analytic->institution->is_pilot_site ?? true)
                                    <span class="px-1.5 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700 font-bold">PILOT</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                @php
                                    $completeness = $analytic->institution->data_completeness_score ?? 0;
                                    $qualityClass = $completeness >= 80 ? 'bg-green-100 text-green-700' : 
                                                   ($completeness >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                                @endphp
                                <span class="px-1.5 py-0.5 text-xs rounded-full {{ $qualityClass }}">
                                    {{ $completeness }}%
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span class="text-base font-bold
                                        {{ ($analytic->gap_score ?? 0) >= 80 ? 'text-red-600' : 
                                           (($analytic->gap_score ?? 0) >= 50 ? 'text-yellow-600' : 'text-green-600') }}">
                                        {{ number_format($analytic->gap_score ?? 0, 1) }}%
                                    </span>
                                    <div class="w-12 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full 
                                            {{ ($analytic->gap_score ?? 0) >= 80 ? 'bg-red-500' : 
                                               (($analytic->gap_score ?? 0) >= 50 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                            style="width: {{ min(100, $analytic->gap_score ?? 0) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate" title="{{ $analytic->policy_recommendation ?? 'No recommendations' }}">
                                {{ Str::limit($analytic->policy_recommendation ?? 'No recommendations', 70) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination -->
                @if(method_exists($analytics, 'links'))
                    <div class="mt-4">
                        {{ $analytics->links() }}
                    </div>
                @endif
            </div>

            <!-- Scope & Limitations Footer Note -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="flex items-start text-xs text-gray-500">
                    <svg class="w-4 h-4 mr-2 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>
                        <strong>Scope Note:</strong> Data covers public sector institutions (schools, barangay halls, city offices) only. 
                        System is in <strong>pilot testing phase</strong> with representative sites. 
                        No real-time IoT monitoring; regular field audits required for data integrity. 
                        See <a href="{{ route('home') }}#scope" class="text-blue-600 hover:underline">project description</a> for full scope and limitations.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Simple filter functionality
        const barangayFilter = document.getElementById('barangayFilter');
        const typeFilter = document.getElementById('typeFilter');
        const table = document.getElementById('analyticsTable');

        function filterTable() {
            const selectedBarangay = barangayFilter.value;
            const selectedType = typeFilter.value;
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const barangay = row.cells[1].textContent.trim();
                const type = row.cells[2].textContent.trim();
                
                const barangayMatch = !selectedBarangay || barangay === selectedBarangay;
                const typeMatch = !selectedType || type === selectedType;
                
                row.style.display = barangayMatch && typeMatch ? '' : 'none';
            });
        }

        barangayFilter.addEventListener('change', filterTable);
        typeFilter.addEventListener('change', filterTable);

        // Export report
        document.getElementById('exportBtn').addEventListener('click', () => {
            window.open('/api/v1/analytics/export', '_blank');
        });
    });
</script>
@endsection
