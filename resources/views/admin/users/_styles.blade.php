@once
<style>
    .users-module { --users-maroon: #75151d; color: #111827; }
    .users-module .users-tabs { display: flex; gap: .5rem; overflow-x: auto; border-bottom: 1px solid #dfe2e7; margin-bottom: 2rem; }
    .users-module .users-tabs a { flex-shrink: 0; border-bottom: 3px solid transparent; padding: 1rem 1.25rem; font-size: 1rem; font-weight: 500; color: #374151; white-space: nowrap; }
    .users-module .users-tabs a[aria-current="page"] { border-color: var(--users-maroon); color: var(--users-maroon); }
    .users-module .users-tabs a:hover { color: var(--users-maroon); background: #faf7f7; }
    .users-module :is(a, button, summary):focus-visible { outline: 2px solid var(--users-maroon); outline-offset: 3px; }
    .users-module .users-card { min-width: 0; overflow: hidden; border: 1px solid #e1e4e8; border-radius: .875rem; background: #fff; box-shadow: 0 2px 4px #1118270d; }
    .users-module .users-control { display: block; width: 100%; min-width: 0; min-height: 2.75rem; border: 1px solid #d1d5db; border-radius: .625rem; padding: .625rem .875rem; background: #fff; color: #111827; font-size: .875rem; }
    .users-module .users-control:focus { outline: none; border-color: var(--users-maroon); box-shadow: 0 0 0 3px #75151d15; }
    .users-module .users-button { display: inline-flex; min-height: 2.75rem; align-items: center; justify-content: center; gap: .5rem; border: 1px solid var(--users-maroon); border-radius: .625rem; padding: .625rem 1rem; background: var(--users-maroon); color: #fff; font-size: .875rem; font-weight: 600; }
    .users-module .users-button:hover { background: #5e1017; }
    .users-module .users-button:disabled { opacity: .5; cursor: not-allowed; }
    .users-module .users-button.users-button-danger { border-color: #b91c1c; background: #b91c1c; color: #fff; }
    .users-module .users-button.users-button-danger:hover { border-color: #991b1b; background: #991b1b; }
    .users-module .users-button-secondary { border-color: #d1d5db; background: #fff; color: #4b5563; }
    .users-module .users-button-secondary:hover { background: #f9fafb; }
    .users-module .users-table-scroll { position: relative; overflow-x: auto; }
    .users-module .users-table { width: 100%; border-collapse: collapse; text-align: left; font-size: .875rem; }
    .users-module .users-table th { padding: 1rem 1.25rem; background: var(--users-maroon); color: #fff; font-size: .75rem; font-weight: 600; text-transform: uppercase; white-space: nowrap; }
    .users-module .users-table td { padding: 1.25rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; line-height: 1.6; }
    .users-module .users-table tbody tr:last-child td { border-bottom: 0; }
    .users-module .users-table tbody tr:hover { background: #fafbfc; }
    .users-module .users-table .users-time { white-space: nowrap; font-variant-numeric: tabular-nums; }
    .users-module .users-table .users-wrap { overflow-wrap: anywhere; }
    .users-module .users-table-scroll:focus-visible { outline: 2px solid var(--users-maroon); outline-offset: -2px; }
    .users-module .users-pagination { border-top: 1px solid #e5e7eb; padding: 1rem 1.25rem; }
    .users-module .users-checkbox { margin-top: .2rem; flex-shrink: 0; accent-color: var(--users-maroon); }
    .users-module .users-accordion > summary { display: flex; cursor: pointer; align-items: center; gap: .625rem; list-style: none; }
    .users-module .users-accordion > summary::-webkit-details-marker { display: none; }
    .users-module .users-accordion > summary::before { content: '\203A'; display: inline-block; font-size: 1.25rem; line-height: 1; transition: transform .15s; }
    .users-module .users-accordion[open] > summary::before { transform: rotate(90deg); }
    .users-module .users-drawer { position: fixed; inset: 0 0 0 auto; margin: 0 0 0 auto; width: min(36rem, 100%); max-width: 100%; height: 100dvh; max-height: 100dvh; border: 0; padding: 0; background: #fff; color: #111827; box-shadow: -8px 0 32px #11182726; overflow-y: auto; }
    .users-module .users-drawer::backdrop { background: #11182766; }
    .users-module [hidden] { display: none !important; }
    @media (max-width: 639px) {
        .users-module .users-tabs { margin-bottom: 1.25rem; gap: 0; }
        .users-module .users-tabs a { padding: .875rem 1rem; font-size: .875rem; }
        .users-module .users-table th, .users-module .users-table td { padding: 1rem; }
    }
</style>
@endonce
