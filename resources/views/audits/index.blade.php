@php
    $statuses = [
        'pending' => 'Pending',
        'validated' => 'Validated',
        'approved' => 'Approved',
        'flagged' => 'Flagged',
        'revision_required' => 'Revision Required',
    ];
@endphp

<div class="min-h-full bg-gray-50 pb-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="mb-4 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-4 rounded-t-xl">
            <div>
                <h1 class="text-lg font-semibold text-gray-900">ICT Audits</h1>
                <p class="mt-0.5 text-xs text-gray-500">{{ $audits->total() }} record(s)</p>
            </div>
        </div>

        <div class="mb-6 flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('audits.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>
                    <input type="search" name="q" value="{{ request('q') }}"
                        placeholder="Search institution"
                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm">
                </div>
                <select name="status" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Apply</button>
                @if (request()->filled('q') || request()->filled('status'))
                    <a href="{{ route('audits.index') }}" class="text-sm text-gray-500">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Institution</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Auditor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Audit Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Notes</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($audits as $audit)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <a href="{{ route('institutions.show', $audit->institution) }}" class="text-sm font-semibold text-gray-900 hover:text-red-700">
                                    {{ $audit->institution->name }}
                                </a>
                                <p class="text-xs text-gray-500">{{ $audit->institution->type }}</p>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-700">{{ $audit->auditor?->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-4 text-sm text-gray-700">{{ $audit->audit_date->format('M d, Y') }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold
                                    {{ $audit->validation_status === 'approved' ? 'bg-emerald-100 text-emerald-700' :
                                       ($audit->validation_status === 'validated' ? 'bg-blue-100 text-blue-700' :
                                       ($audit->validation_status === 'flagged' ? 'bg-amber-100 text-amber-700' :
                                       ($audit->validation_status === 'revision_required' ? 'bg-red-100 text-red-700' :
                                       'bg-slate-200 text-slate-600'))) }}">
                                    {{ $statuses[$audit->validation_status] ?? $audit->validation_status }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-500">{{ Str::limit($audit->notes ?? '', 80) }}</td>
                            <td class="px-3 py-4 text-right">
                                <a href="{{ route('audits.edit', $audit) }}" class="text-red-600 hover:text-red-800" title="Edit">
                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                    <i class="fa-solid fa-clipboard-check text-lg"></i>
                                </span>
                                <p class="mt-3 text-sm font-semibold text-gray-700">No audit records found</p>
                                <p class="mt-1 text-xs text-gray-500">Submit a quarterly ICT audit from an institution detail page.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($audits->hasPages())
                <div class="border-t border-gray-200 px-5 py-4">{{ $audits->links() }}</div>
            @endif
        </div>

    </div>
</div>