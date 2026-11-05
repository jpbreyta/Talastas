@once
<dialog id="inventorySavingDialog" class="inventory-saving-dialog" aria-labelledby="inventorySavingTitle">
    <div class="inventory-saving-content">
        <span class="inventory-saving-spinner" aria-hidden="true"></span>
        <p id="inventorySavingTitle" role="status" class="text-sm font-semibold text-gray-800">Saving…</p>
    </div>
</dialog>
<style>
    .inventory-saving-dialog { border: 0; border-radius: 1rem; padding: 1.5rem 2rem; margin: auto; box-shadow: 0 16px 48px rgb(0 0 0 / .18); }
    .inventory-saving-dialog::backdrop { background: rgb(15 23 42 / .35); }
    .inventory-saving-content { display: flex; align-items: center; gap: .75rem; }
    .inventory-saving-spinner { display: block; width: 1.5rem; height: 1.5rem; border: 3px solid #e5e7eb; border-top-color: #7f1d1d; border-radius: 50%; animation: inventory-saving-spin .8s linear infinite; }
    @keyframes inventory-saving-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .inventory-saving-spinner { animation: none; } }
    .inventory-form .inventory-row-invalid { background: rgb(249 250 251); }
    .inventory-form .form-input[aria-invalid="true"] { border-color: #b91c1c; box-shadow: 0 0 0 2px rgb(185 28 28 / .12); }
    .inventory-form [data-row-feedback] { color: #991b1b; font-size: .75rem; line-height: 1.25rem; overflow-wrap: anywhere; }
    .inventory-form [data-row-feedback]:not(:empty) { margin-top: .375rem; }
</style>
<script>
(() => {
    const dialog = document.getElementById('inventorySavingDialog');
    let previousFocus;
    let feedbackSequence = 0;
    const fieldFeedback = new WeakMap();
    function clearFieldFeedback(input) {
        const previous = fieldFeedback.get(input);
        if (!previous) return;
        previous.feedback.remove();
        previous.eventTarget.removeEventListener('input', previous.clear);
        input.removeAttribute('aria-invalid');
        if (previous.description) input.setAttribute('aria-describedby', previous.description);
        else input.removeAttribute('aria-describedby');
        fieldFeedback.delete(input);
    }
    const fieldLabel = field => field.replace('_count', '').replace(/^./, value => value.toUpperCase());
    const joinLabels = labels => labels.length < 2 ? labels[0] : `${labels.slice(0, -1).join(', ')} and ${labels.at(-1)}`;
    window.InventoryFeedback = {
        presentation(values, errors) {
            const exceeded = [...new Set(errors.filter(error => error.field !== 'total_count' && /cannot exceed Total/i.test(error.message)).map(error => error.field))];
            const messages = [];
            const fields = new Set(exceeded);
            if (exceeded.length) {
                messages.push(`Check quantities: ${joinLabels(exceeded.map(fieldLabel))} ${exceeded.length === 1 ? 'count' : 'counts'} cannot exceed the Total (${values.total_count}).`);
            }
            errors.forEach(error => {
                if (exceeded.includes(error.field) && /cannot exceed Total/i.test(error.message)) return;
                if (/Active \+ Retired/.test(error.message)) {
                    if (exceeded.some(field => ['active_count', 'retired_count'].includes(field))) return;
                    fields.add('active_count'); fields.add('retired_count');
                    messages.push(`Check quantities: Active and Retired together cannot exceed the Total (${values.total_count}).`);
                    return;
                }
                fields.add(error.field);
                if (error.field === 'total_count' && /required|enter.*total|total: enter/i.test(error.message)) {
                    messages.push('Enter Total for this item.');
                } else {
                    messages.push(error.message);
                }
            });
            return {messages: [...new Set(messages)], fields};
        },
        showRowErrors(row, values, errors) {
            const feedback = row.querySelector('[data-row-feedback]');
            if (!feedback) return;
            feedback.id ||= `inventory-row-feedback-${++feedbackSequence}`;
            const oldIds = new Set([feedback.id, ...Array.from(feedback.querySelectorAll('[id]'), element => element.id)]);
            const presentation = this.presentation(values, errors);
            row.querySelectorAll('[data-field]').forEach(input => {
                const invalid = presentation.fields.has(input.dataset.field);
                if (invalid) input.setAttribute('aria-invalid', 'true');
                else input.removeAttribute('aria-invalid');
                const ids = (input.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id && !oldIds.has(id));
                if (invalid) ids.push(feedback.id);
                if (ids.length) input.setAttribute('aria-describedby', ids.join(' '));
                else input.removeAttribute('aria-describedby');
            });
            row.classList.toggle('inventory-row-invalid', errors.length > 0);
            feedback.replaceChildren();
            presentation.messages.forEach(message => {
                const text = document.createElement('p'); text.textContent = message; feedback.appendChild(text);
            });
        },
        showFieldError(input, messages) {
            if (!input || input.type === 'hidden') return false;
            clearFieldFeedback(input);
            const feedback = document.createElement('p');
            feedback.id = `inventory-field-feedback-${++feedbackSequence}`;
            feedback.dataset.saveFieldError = '';
            feedback.className = 'mt-1 text-xs font-normal leading-5 text-red-800';
            feedback.textContent = messages.join(' ');
            const target = input.type === 'radio' ? input.closest('fieldset') : input;
            target.insertAdjacentElement('afterend', feedback);
            input.setAttribute('aria-invalid', 'true');
            const previousDescription = input.getAttribute('aria-describedby');
            input.setAttribute('aria-describedby', [previousDescription, feedback.id].filter(Boolean).join(' '));
            const clear = () => clearFieldFeedback(input);
            const eventTarget = input.type === 'radio' ? target : input;
            fieldFeedback.set(input, {feedback, description: previousDescription, clear, eventTarget});
            eventTarget.addEventListener('input', clear, {once: true});
            return true;
        },
        beginSaving(form) {
            previousFocus = form.contains(document.activeElement) ? document.activeElement : form.querySelector('button[type="submit"]');
            form.setAttribute('aria-busy', 'true');
            if (!dialog.open) dialog.showModal();
        },
        endSaving(form) {
            if (dialog.open) dialog.close();
            form?.removeAttribute('aria-busy');
            if (previousFocus?.isConnected) previousFocus.focus({preventScroll: true});
            previousFocus = null;
        },
    };
    dialog.addEventListener('cancel', event => event.preventDefault());
})();
</script>
@endonce
