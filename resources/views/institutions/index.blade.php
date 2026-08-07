@extends('layouts.app')

@section('dashboard-layout')
@endsection

@section('content')
    <div class="flex h-full min-h-0 flex-col bg-gray-100">
        {{-- Page Header --}}
        <div class="shrink-0 bg-red-700 px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600 text-red-100">
                        <i class="fa-solid fa-building"></i>
                    </div>

                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold leading-tight tracking-tight text-white">
                            Institutions
                        </h1>
                        <p class="mt-0.5 text-xs text-red-200">
                            Manage registered institutions and their ICT profiles
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <div class="relative hidden sm:block">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-red-300">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </div>

                        <input id="institutionSearch" type="text" placeholder="Search institutions..."
                            class="w-56 rounded-lg border border-red-600 bg-red-800 py-2 pl-9 pr-4 text-sm text-white placeholder-blue-300 transition focus:outline-none focus:ring-2 focus:ring-red-300">
                    </div>

                    <button type="button" id="openAddModal" data-open-institution-modal
                        class="inline-flex items-center gap-2 rounded-lg border border-red-100 bg-white px-4 py-2 text-sm font-medium text-red-700 shadow-sm transition hover:bg-red-50">
                        <i class="fa-solid fa-plus"></i>
                        Add Institution
                    </button>
                </div>
            </div>
        </div>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Please correct the following:</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Content Area --}}
        <div class="flex min-h-0 flex-1 flex-col">
            @if ($institutions->count() > 0)
                <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-1 pt-3">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[800px] text-sm">
                                <thead class="sticky top-0 z-10">
                                    <tr class="border-b border-gray-200 bg-gray-50 text-left">
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Institution
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Sector
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Barangay
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Population
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Status
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($institutions as $institution)
                                        <tr id="institution-row-{{ $institution->id }}" class="inst-row transition hover:bg-red-50/40">
                                            <td class="px-4 py-3">
                                                <span class="font-medium text-gray-900">
                                                    {{ $institution->name }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-3 text-gray-500">
                                                {{ $institution->type }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-500">
                                                {{ $institution->barangay?->name ?? '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-500">
                                                {{ $institution->type === 'School'
                                                    ? ($institution->student_population !== null
                                                        ? number_format($institution->student_population)
                                                        : '—')
                                                    : ($institution->staff_population !== null
                                                        ? number_format($institution->staff_population)
                                                        : '—') }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if ($institution->is_active)
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                                            <i class="fa-solid fa-circle-check text-[10px]"></i>
                                                            Active
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-500">
                                                            <i class="fa-solid fa-circle-minus text-[10px]"></i>
                                                            Inactive
                                                        </span>
                                                    @endif

                                                    @if ($institution->is_pilot_site)
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">
                                                            <i class="fa-solid fa-flask text-[10px]"></i>
                                                            Pilot
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('institutions.show', $institution) }}"
                                                        class="inline-flex items-center gap-1 text-xs font-medium text-red-600 transition hover:text-blue-800">
                                                        <i class="fa-solid fa-eye"></i>
                                                        View
                                                    </a>

                                                    <a href="{{ route('institutions.edit', $institution) }}"
                                                        class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 transition hover:text-gray-900">
                                                        <i class="fa-solid fa-pen"></i>
                                                        Edit
                                                    </a>

                                                    <button type="button"
                                                        class="delete-institution-button inline-flex items-center gap-1 text-xs font-medium text-gray-400 transition hover:text-red-600"
                                                        data-delete-url="{{ route('institutions.destroy', $institution) }}"
                                                        data-institution-id="{{ $institution->id }}"
                                                        data-institution-name="{{ $institution->name }}">
                                                        <i class="fa-solid fa-trash"></i>
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div id="noResults" class="hidden py-12 text-center">
                            <i class="fa-solid fa-magnifying-glass text-2xl text-gray-300"></i>
                            <p class="mt-3 text-sm text-gray-500">
                                No institutions match your search.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-200 bg-white px-5 py-2">
                    {{ $institutions->links() }}
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center px-5 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-blue-500">
                        <i class="fa-solid fa-building text-xl"></i>
                    </div>

                    <h3 class="mt-4 text-sm font-semibold text-gray-900">
                        No institutions yet
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Get started by adding your first institution.
                    </p>

                    <button type="button" id="openAddModalEmpty" data-open-institution-modal
                        class="mt-5 inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                        <i class="fa-solid fa-plus"></i>
                        Add Institution
                    </button>
                </div>
            @endif
        </div>

        @include('institutions.modals')
    </div>
@endsection