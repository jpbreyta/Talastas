<?php if (! $__env->hasRenderedOnce('b2b240ce-d265-48c1-87a0-69d1fed94a96')): $__env->markAsRenderedOnce('b2b240ce-d265-48c1-87a0-69d1fed94a96'); ?>
<style>
    .inventory-form .form-input {
        display: block;
        min-width: 0;
        border: 1px solid rgb(209 213 219);
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: rgb(17 24 39);
        background-color: white;
        transition: border-color 150ms ease, box-shadow 150ms ease;
    }
    .inventory-form .form-input:focus {
        outline: none;
        border-color: rgb(220 38 38);
        box-shadow: 0 0 0 3px rgb(254 202 202 / 0.4);
    }
    .inventory-form .form-input:disabled {
        background-color: rgb(243 244 246);
        color: rgb(107 114 128);
        cursor: not-allowed;
    }
    .inventory-form .form-input[aria-invalid="true"] { border-color: rgb(185 28 28); }
    .inventory-form .form-input[aria-invalid="true"]:focus {
        border-color: rgb(185 28 28);
        box-shadow: 0 0 0 3px rgb(254 202 202 / 0.55);
    }
    .inventory-form .inventory-inline-live-errors {
        border: 0;
        background: transparent;
        padding: 0;
        color: rgb(153 27 27);
    }
    .inventory-form .inventory-inline-live-errors summary:focus-visible {
        outline: 2px solid rgb(185 28 28);
        outline-offset: 3px;
        border-radius: 0.25rem;
    }
    .inventory-form .inventory-quantity-errors { overflow-wrap: anywhere; }
    .inventory-form .inventory-quantity-table { border-collapse: separate; border-spacing: 0; }
    .inventory-form .inventory-quantity-table thead th { background: #75151d; color: #fff; padding-top: .65rem; padding-bottom: .65rem; }
    .inventory-form .inventory-quantity-table thead th:first-child { border-top-left-radius: .5rem; }
    .inventory-form .inventory-quantity-table thead th:last-child { border-top-right-radius: .5rem; }
    .inventory-form .inventory-quantity-table [data-inventory-row] > td { vertical-align: middle; border-bottom: 1px solid #e5e7eb; }
    .inventory-form .inventory-quantity-table [data-subtotal-group] { background: #f9fafb; }
    .inventory-form .inventory-quantity-table [data-new-item] > td { background: #fbefef; border-top: 1px solid #d8a1a4; border-bottom: 1px solid #d8a1a4; }
    .inventory-form [data-new-item] > td:first-child { border-left: 2px solid #d8a1a4; border-radius: .5rem 0 0 .5rem; }
    .inventory-form [data-new-item] > td:last-child { border-right: 2px solid #d8a1a4; border-radius: 0 .5rem .5rem 0; }
    .inventory-form [data-new-item]:focus-within > td { border-color: #b96d73; }
    .inventory-form [data-new-item] .inventory-type-picker, .inventory-form [data-new-item] label { max-width: 20rem; }
    .inventory-form .inventory-type-picker { position: relative; }
    .inventory-form .inventory-type-picker input { padding-right: 2rem; }
    .inventory-form .inventory-type-picker i { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); font-size: .65rem; color: #374151; pointer-events: none; }
    .inventory-form .inventory-type-picker input::-webkit-calendar-picker-indicator { opacity: 0; }
    .inventory-form .inventory-quantity-table [data-count] { max-width: 5rem; margin-inline: auto; padding: .375rem .25rem; font-size: .8125rem; font-variant-numeric: tabular-nums; appearance: textfield; }
    .inventory-form .inventory-quantity-table [data-count]::-webkit-inner-spin-button,
    .inventory-form .inventory-quantity-table [data-count]::-webkit-outer-spin-button { appearance: none; margin: 0; }
    .inventory-form [data-add-row], .inventory-form #saveUnifiedInventory { color: #fff; background: #75151d; border-color: #75151d; }
    .inventory-form [data-add-row]:hover, .inventory-form #saveUnifiedInventory:hover { background: #5f1017; }
    .inventory-form [data-add-row]:focus-visible, .inventory-form #saveUnifiedInventory:focus-visible { outline: 2px solid #75151d; outline-offset: 3px; }
    .inventory-form .inventory-connection-card { padding: 1.25rem; border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff; }
    .inventory-form .inventory-connection-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.25rem; }
    .inventory-form [data-q10-connection] { min-width: 0; }
    .inventory-form [data-add-connection] { color: #fff; background: #75151d; border-color: #75151d; }
    .inventory-form [data-add-connection]:hover { background: #5f1017; }
    .inventory-form [data-add-connection]:focus-visible { outline: 2px solid #75151d; outline-offset: 3px; }
    .inventory-form [data-remove-connection] { color: #75151d; background: #fff; border-color: #e5c5c8; }
    .inventory-form [data-remove-connection]:hover { background: #fdf2f2; }
    .inventory-form [data-remove-connection]:focus-visible { outline: 2px solid #75151d; outline-offset: 3px; }
    .inventory-form [data-connection-measurement] { appearance: textfield; }
    .inventory-form [data-connection-measurement]::-webkit-inner-spin-button,
    .inventory-form [data-connection-measurement]::-webkit-outer-spin-button { appearance: none; margin: 0; }
    .inventory-form [data-q10-connection][hidden] { display: none; }
    @media (min-width: 640px) { .inventory-form .inventory-connection-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .inventory-form .inventory-connection-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
</style>
<?php endif; ?>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/ict-inventory/_form-styles.blade.php ENDPATH**/ ?>