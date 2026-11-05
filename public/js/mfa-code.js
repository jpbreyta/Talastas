(() => {
    const group = document.getElementById('mfaCodeBoxes');
    const fallback = document.getElementById('mfaCodeFallback');
    const value = document.getElementById('mfaCodeValue');
    if (!group || !fallback || !value) return;

    const digits = Array.from(group.querySelectorAll('input'));
    const sync = () => { value.value = digits.map(input => input.value).join(''); };
    const fill = (start, text) => {
        const numbers = text.replace(/[^0-9]/g, '').slice(0, digits.length - start);
        if (!numbers) { digits[start].value = ''; sync(); return; }
        Array.from(numbers).forEach((number, offset) => { digits[start + offset].value = number; });
        sync();
        digits[Math.min(start + numbers.length, digits.length - 1)].focus();
    };

    digits.forEach((input, index) => {
        input.required = true;
        input.addEventListener('focus', () => input.select());
        input.addEventListener('input', () => fill(index, input.value));
        input.addEventListener('paste', event => {
            event.preventDefault();
            const text = event.clipboardData.getData('text');
            // A full code replaces the entire row, even when pasted into a later box.
            fill(text.replace(/[^0-9]/g, '').length >= 6 ? 0 : index, text);
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'Backspace' && !input.value && index > 0) {
                event.preventDefault();
                digits[index - 1].value = '';
                digits[index - 1].focus();
                sync();
            } else if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault(); digits[index - 1].focus();
            } else if (event.key === 'ArrowRight' && index < digits.length - 1) {
                event.preventDefault(); digits[index + 1].focus();
            }
        });
    });

    // Keep one server-facing code field; native validation applies to the visible inputs.
    value.type = 'hidden';
    value.required = false;
    fallback.hidden = true;
    group.hidden = false;
})();
