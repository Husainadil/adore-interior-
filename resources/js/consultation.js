export function initConsultation() {
    document.querySelectorAll('.inquiry-form').forEach(form => {
        const updateSummary = () => {
            form.querySelector('[data-project-summary]').textContent = form.elements.project_type.value || 'Your project';
            form.querySelector('[data-budget-summary]').textContent = form.elements.budget.value || 'Budget to discuss';
        };
        form.addEventListener('change', updateSummary);
        updateSummary();
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) return;
            if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
            const submitter = event.submitter;
            if (!submitter) return;
            form.dataset.submitting = 'true';
            // Keep name/value available for the browser's native form submission.
            // Disabling the submitter here would discard the WhatsApp intent.
            submitter.setAttribute('aria-busy', 'true');
            submitter.textContent = submitter.value === 'whatsapp' ? 'Saving your inquiry…' : 'Sending your inquiry…';
        });
        window.addEventListener('pageshow', () => {
            delete form.dataset.submitting;
            form.querySelectorAll('[data-submit-label]').forEach(button => {
                button.removeAttribute('aria-busy');
                button.textContent = button.dataset.submitLabel;
            });
        });
    });
}
