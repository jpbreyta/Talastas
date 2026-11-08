<?php if (! $__env->hasRenderedOnce('4e2627a5-83b8-4145-8e8a-e5bf2a3c285f')): $__env->markAsRenderedOnce('4e2627a5-83b8-4145-8e8a-e5bf2a3c285f'); ?>
<style>
    #addInstitutionModal .institution-create-dialog {
        max-width: 1200px;
        max-height: 90vh;
        max-height: 90dvh;
    }
    #addInstitutionModal #mapPickerContainer { height: 280px; }
    @media (min-width: 1024px) {
        #addInstitutionModal .institution-create-dialog { width: 84vw; }
        #addInstitutionModal #mapPickerContainer { height: 320px; }
    }
    @media (max-width: 639px) {
        #addInstitutionModal .institution-create-dialog {
            max-height: calc(100vh - 1rem);
            max-height: calc(100dvh - 1rem);
        }
    }
    /* Shared by institution creation and editing; never applied to other forms. */
    .institution-form h1,
    .institution-form #addInstitutionModalTitle {
        font-size: 1.125rem;
        letter-spacing: normal;
    }
    .institution-form h2:not(#addInstitutionModalTitle) {
        font-size: var(--talastas-text-card);
    }
    .institution-form .form-input {
        display: block;
        min-width: 0;
        border: 1px solid rgb(209 213 219);
        border-radius: 0.5rem;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
        padding-right: 0.75rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: rgb(17 24 39);
        background-color: white;
        transition: border-color 150ms ease, box-shadow 150ms ease;
    }
    .institution-form .form-input:not(.pl-9) { padding-left: 0.75rem; }
    .institution-form .form-input::placeholder { color: rgb(156 163 175); }
    .institution-form .form-input:focus {
        outline: none;
        border-color: rgb(59 130 246);
        box-shadow: 0 0 0 3px rgb(191 219 254 / 0.8);
    }
    .institution-form .form-input.border-red-400 { border-color: rgb(248 113 113); }

    .location-options { border: 1px solid #e2e8f0; border-radius: .75rem; background: #f8fafc; }
    .location-options-toggle { display: flex; align-items: center; gap: .65rem; padding: .9rem 1rem; color: #800000; font-size: .875rem; font-weight: 650; cursor: pointer; list-style: none; }
    .location-options-toggle::-webkit-details-marker, .manual-coordinate-option > summary::-webkit-details-marker { display: none; }
    .location-options-chevron { margin-left: auto; font-size: .7rem; transition: transform .15s; }
    .location-options[open] > summary .location-options-chevron { transform: rotate(180deg); }
    .location-method-grid { display: grid; gap: 1rem; padding: 0 1rem 1rem; grid-template-columns: minmax(0, 1fr); }
    .location-method-card { min-width: 0; padding: 1rem; border: 1px solid #e2e8f0; border-radius: .65rem; background: white; font-size: .8125rem; }
    .location-method-card h4 { display: flex; align-items: center; gap: .5rem; margin: 0; font-size: .875rem; font-weight: 650; color: #1e293b; }
    .location-method-card h4 > i { color: #800000; }
    .location-method-help { margin: .65rem 0; line-height: 1.6; color: #64748b; }
    .location-method-button { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 44px; margin-top: .75rem; padding: .65rem .9rem; border: 1px solid #cbd5e1; border-radius: .5rem; background: white; color: #800000; font-size: .8125rem; font-weight: 600; cursor: pointer; list-style: none; }
    .location-method-button:hover { background: #fef2f2; border-color: #800000; }
    .location-method-button:focus-visible, .location-options-toggle:focus-visible { outline: 2px solid #800000; outline-offset: 3px; }
    .gps-capture-button { background: #800000; color: white; border-color: #800000; }
    .gps-capture-button:hover { background: #600000; color: white; }
    .gps-capture-button:disabled { opacity: .75; cursor: wait; }
    .gps-card-heading { display: flex; flex-wrap: wrap; align-items: center; gap: .65rem; }
    .location-source-badge { display: inline-flex; flex-wrap: wrap; gap: .25rem; border: 1px solid #e2e8f0; border-radius: 999px; padding: .25rem .6rem; background: #f1f5f9; color: #475569; font-size: .6875rem; }
    .gps-help { display: flex; gap: .6rem; margin-top: .9rem; border-radius: .5rem; padding: .75rem; background: #f8fafc; color: #64748b; line-height: 1.65; font-size: .75rem; }
    .gps-help > i { margin-top: .2rem; color: #64748b; }
    .gps-help p + p { margin-top: .5rem; }
    .gps-status { display: flex; align-items: flex-start; gap: .6rem; margin-top: .85rem; padding: .8rem; border: 1px solid; border-radius: .5rem; line-height: 1.65; overflow-wrap: anywhere; }
    .gps-status > i { margin-top: .25rem; flex-shrink: 0; }
    [data-gps-state="idle"] .gps-status { display: none; }
    [data-gps-state="loading"] .gps-status { color: #1e40af; background: #eff6ff; border-color: #bfdbfe; animation: gps-loading-pulse 2s ease-in-out infinite; }
    [data-gps-state="success"] .gps-status { color: #166534; background: #f0fdf4; border-color: #bbf7d0; }
    [data-gps-state="error"] .gps-status { color: #991b1b; background: #fef2f2; border-color: #fecaca; }
    [data-gps-state="warning"] .gps-status { color: #92400e; background: #fffbeb; border-color: #fde68a; }
    .gps-review:not(.hidden) { display: flex; align-items: flex-start; gap: .5rem; margin-top: .85rem; line-height: 1.6; color: #334155; }
    .gps-review > input { margin-top: .25rem; accent-color: #800000; }
    @keyframes gps-loading-pulse { 50% { background-color: #dbeafe; } }
    @media (prefers-reduced-motion: reduce) { .gps-status, .gps-card .fa-spin { animation: none !important; } }
    @media (min-width: 900px) { .location-method-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>
<?php endif; ?>
<?php /**PATH C:\Paul\CAPSTONE\Talastas\resources\views/institutions/_form-styles.blade.php ENDPATH**/ ?>