@php
    $logQuery = request()->routeIs('admin.users.logs') ? request()->except('tab') : [];
    $userTabs = [
        'accounts' => ['label' => 'Accounts', 'url' => route('admin.users.index')],
        'invitations' => ['label' => 'Invitations', 'url' => route('admin.invitations.index')],
        'sessions' => ['label' => 'Session Activity', 'url' => route('admin.users.logs', array_merge($logQuery, ['tab' => 'sessions']))],
        'changes' => ['label' => 'Data Changes', 'url' => route('admin.users.logs', array_merge($logQuery, ['tab' => 'changes']))],
    ];
@endphp
<nav class="users-tabs" aria-label="Accounts and logs">
    @foreach ($userTabs as $key => $userTab)
        <a href="{{ $userTab['url'] }}" @if ($activeTab === $key) aria-current="page" @endif>{{ $userTab['label'] }}</a>
    @endforeach
</nav>
<script>
document.addEventListener('DOMContentLoaded', () => {
    function revealSelectedTab() {
        const tabs = document.querySelector('.users-tabs');
        const selected = tabs?.querySelector('[aria-current="page"]');
        if (!selected || tabs.scrollWidth <= tabs.clientWidth) return;
        tabs.scrollLeft += selected.getBoundingClientRect().left - tabs.getBoundingClientRect().left - (tabs.clientWidth - selected.offsetWidth) / 2;
    }
    revealSelectedTab();
    document.fonts?.ready.then(revealSelectedTab);
    window.addEventListener('resize', revealSelectedTab);
});
</script>
