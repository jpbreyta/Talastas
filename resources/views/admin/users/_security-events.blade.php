<section data-user-activity-live class="users-card" aria-labelledby="securityEventsHeading">
    <h2 id="securityEventsHeading" class="border-b border-gray-200 px-5 py-4 text-base font-semibold">Security Events</h2>
    <div class="users-table-scroll" tabindex="0" role="region" aria-label="Security events table">
        <table class="users-table users-accounts-table min-w-[900px]">
            <thead><tr>
                @foreach (['Event', 'Time (PHT)', 'Actor', 'Related account', 'Outcome', 'Context'] as $column)
                    <th scope="col">{{ $column }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @forelse ($securityEvents as $event)
                    <tr>
                        <td class="users-wrap"><span title="{{ $event->event }}">{{ Str::headline(str_replace('.', ' ', $event->event)) }}</span></td>
                        <td class="users-time"><time datetime="{{ \Carbon\Carbon::parse($event->occurred_at, 'UTC')->toIso8601String() }}">{{ \Carbon\Carbon::parse($event->occurred_at, 'UTC')->timezone('Asia/Manila')->format('d M Y h:i:s A') }}</time></td>
                        <td class="users-wrap">{{ $event->actor_name ?? ($event->actor_id !== null ? 'Account #'.$event->actor_id.' (name unavailable)' : 'System / guest') }}</td>
                        <td class="users-wrap">{{ $event->target_name ?? ($event->target_id !== null ? 'Account #'.$event->target_id.' (name unavailable)' : 'Not recorded') }}</td>
                        <td><span class="inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium {{ $event->outcome === 'success' ? 'bg-green-50 text-green-800' : (in_array($event->outcome, ['denied', 'failed'], true) ? 'bg-amber-50 text-amber-800' : 'bg-gray-100 text-gray-600') }}">{{ filled($event->outcome) ? Str::headline($event->outcome) : 'Not recorded' }}</span></td>
                        <td class="users-wrap text-xs text-gray-500">{{ $event->invitation_id ? 'Invitation #'.$event->invitation_id : 'Not recorded' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-500">{{ request()->filled('search') ? 'No security events match this search. Try another event, outcome or account name.' : 'No security events recorded yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="users-pagination">{{ $securityEvents->links() }}</div>
</section>
