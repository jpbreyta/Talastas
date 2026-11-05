// Shared by unified inventory encoding and single-item editing.
window.InventoryCounts = {
    availabilityFields: ['active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'],
    errors(values, requireTotal = false) {
        const fields = ['total_count', ...this.availabilityFields];
        const recorded = field => values[field] !== '' && values[field] !== null && values[field] !== undefined;
        if (!requireTotal && fields.every(field => !recorded(field))) return [];
        const errors = [];
        const label = field => field === 'defective_active_count' ? 'Defective within Active' : field.replace('_count', '').replace(/^./, c => c.toUpperCase());
        fields.forEach(field => {
            if (recorded(field) && (!/^[0-9]+$/.test(String(values[field])) || Number(values[field]) > 1000000)) {
                errors.push({field, message: `${label(field)}: enter a whole count from 0 to 1,000,000.`});
            }
        });
        if (!recorded('total_count')) {
            errors.push({field: 'total_count', message: 'Total: enter the source quantity. Availability fields may be left blank.'});
            return errors;
        }
        const total = Number(values.total_count);
        this.availabilityFields.forEach(field => {
            if (recorded(field) && Number(values[field]) > total) errors.push({field, message: `${label(field)}: cannot exceed Total.`});
        });
        ['active_count', 'defective_count'].forEach(field => {
            if (recorded('defective_active_count') && recorded(field) && Number(values.defective_active_count) > Number(values[field])) {
                errors.push({field: 'defective_active_count', message: 'Defective within Active cannot exceed ' + label(field) + '.'});
            }
        });
        return errors;
    },
    subtotal(values) {
        if (!values.length || values.some(value => value === '' || value === null || value === undefined || !/^[0-9]+$/.test(String(value)))) return null;
        return values.reduce((sum, value) => sum + Number(value), 0);
    },
    status(values) {
        const count = this.availabilityFields.filter(field => values[field] !== '' && values[field] !== null && values[field] !== undefined).length;
        const totalRecorded = values.total_count !== '' && values.total_count !== null && values.total_count !== undefined;
        if (!totalRecorded && count === 0) return 'Availability Not Recorded';
        return totalRecorded && count === this.availabilityFields.length ? 'Availability Recorded' : 'Availability Partially Recorded';
    },
};
