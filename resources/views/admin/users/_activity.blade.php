<section data-user-activity-live class="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500" @if ($activityTab !== 'sessions') hidden @endif aria-label="Session information">
    <p>Currently active: {{ $online }} users</p>
    <details class="users-accordion max-w-xl text-xs">
        <summary>About session activity</summary>
        <p class="mt-2 leading-5">Active means seen within two minutes. Browser closure is not an exact logout. Expired sessions show last activity, not an invented close time. Logs begin when this feature is enabled; existing sessions show first observed access. Times are shown in Asia/Manila.</p>
    </details>
</section>
<section data-user-activity-live class="users-card" @if ($activityTab !== 'sessions') hidden @endif aria-label="Session Activity">
    <div class="users-table-scroll" tabindex="0" role="region" aria-label="Session activity table">
        <table class="users-table min-w-[1000px]">
            <thead><tr>
                @foreach (['User', 'Channel', 'Status', 'Opened / Created', 'Last Activity', 'Signed Out'] as $column)
                    <th scope="col">{{ $column }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @forelse ($sessions as $session)
                    @php
                        $seen = \Carbon\Carbon::parse($session->last_seen_at);
                        $state = $session->closed_at ? 'Signed out' : ($seen->lt(now()->subMinutes(config('session.lifetime', 120))) ? 'Expired' : ($seen->gte(now()->subMinutes(2)) ? 'Active' : 'Idle'));
                    @endphp
                    <tr>
                        <td class="min-w-[180px]">{{ $session->user_name }}</td>
                        <td>{{ strtoupper($session->channel) }}</td>
                        <td class="whitespace-nowrap">{{ $state }}</td>
                        <td class="users-time">{{ \Carbon\Carbon::parse($session->opened_at)->timezone('Asia/Manila')->format('d M Y h:i:s A') }}</td>
                        <td class="users-time">{{ $seen->timezone('Asia/Manila')->format('d M Y h:i:s A') }}</td>
                        <td class="users-time">{{ $session->closed_at ? \Carbon\Carbon::parse($session->closed_at)->timezone('Asia/Manila')->format('d M Y h:i:s A') : 'Not recorded' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-500">No session activity recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="users-pagination">{{ $sessions->links() }}</div>
</section>
<section data-user-activity-live class="users-card" @if ($activityTab !== 'changes') hidden @endif aria-label="Data Changes">
    <div class="users-table-scroll" tabindex="0" role="region" aria-label="Data changes table">
        <table class="users-table min-w-[1000px]">
            <thead><tr>
                @foreach (['User', 'Action', 'Record', 'Fields Changed', 'Time'] as $column)
                    <th scope="col">{{ $column }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="min-w-[180px]">{{ $log->user_name }}</td>
                        <td>{{ ucfirst($log->action) }}</td>
                        <td class="min-w-[140px]">{{ $log->record_type }} #{{ $log->record_id }}</td>
                        <td class="users-wrap min-w-[280px]">
                            {{ $log->changed_fields ?: 'Not applicable' }}
                            @if ($log->location_source ?? null)
                                <details class="users-accordion mt-2 text-xs text-gray-600">
                                    <summary>Location: {{ $log->location_source }}</summary>
                                    <div class="mt-2 space-y-1 leading-5">
                                        @if ($log->actor_latitude !== null)
                                            <p>Device: {{ $log->actor_latitude }}, {{ $log->actor_longitude }}</p>
                                            <p>Reported accuracy: approximately {{ $log->gps_accuracy }} meters. Pin verified: {{ $log->gps_verified ? 'Yes' : 'No' }}.</p>
                                        @else
                                            <p>No device location captured.</p>
                                        @endif
                                    </div>
                                </details>
                            @endif
                        </td>
                        <td class="users-time">{{ \Carbon\Carbon::parse($log->occurred_at)->timezone('Asia/Manila')->format('d M Y h:i:s A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-gray-500">No data changes recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="users-pagination">{{ $logs->links() }}</div>
</section>
