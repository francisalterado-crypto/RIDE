/**
 * Keeps a form's Print Preview buttons disabled until its required fields are filled.
 *
 * <form data-preview-rules='[{"name":"title","label":"Title"}, ...]'>: each rule names an input ("[*]" = any
 * row index, "[]" = checkbox group); it passes when a matching input has a value or is checked.
 * Buttons: .js-preview-btn. Status text: .js-preview-hint.
 */
(() => {
    const toPattern = (name) => new RegExp('^' + name
        .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
        .replace(/\\\[\\\*\\\]/g, '\\[\\d+\\]') + '$');

    const filled = (el) => {
        if (el.disabled) {
            return false;
        }
        if (el.type === 'checkbox' || el.type === 'radio') {
            return el.checked;
        }
        return String(el.value || '').trim() !== '' && String(el.value) !== '0';
    };

    document.querySelectorAll('form[data-preview-rules]').forEach((form) => {
        let rules;
        try {
            rules = JSON.parse(form.dataset.previewRules).map((rule) => ({ ...rule, pattern: toPattern(rule.name) }));
        } catch (error) {
            return;
        }
        const buttons = form.querySelectorAll('.js-preview-btn');
        const hints = form.querySelectorAll('.js-preview-hint');

        const update = () => {
            const elements = Array.from(form.elements).filter((el) => el.name);
            const missing = rules
                .filter((rule) => !elements.some((el) => rule.pattern.test(el.name) && filled(el)))
                .map((rule) => rule.label);
            const ready = missing.length === 0;
            const title = ready ? (form.dataset.previewReadyTitle || 'Download the filled form (.docx)') : 'Still missing: ' + missing.join(', ');
            const text = ready
                ? (form.dataset.previewReadyText || 'Form complete. Print Preview downloads the filled form (.docx) on the office letterhead.')
                : 'Print Preview is available once the form is filled in. Still missing: ' + missing.join(', ') + '.';
            // Write only on change, so the mutation observer below doesn't re-trigger itself.
            buttons.forEach((button) => {
                if (button.disabled !== !ready) {
                    button.disabled = !ready;
                }
                if (button.title !== title) {
                    button.title = title;
                }
            });
            hints.forEach((hint) => {
                if (hint.textContent !== text) {
                    hint.textContent = text;
                }
                hint.classList.toggle('is-ready', ready);
            });
        };

        let queued = false;
        const schedule = () => {
            if (!queued) {
                queued = true;
                requestAnimationFrame(() => { queued = false; update(); });
            }
        };
        form.addEventListener('input', schedule);
        form.addEventListener('change', schedule);
        // Rows added or removed by the repeaters (hint text changes are ignored).
        new MutationObserver((records) => {
            if (records.some((r) => !Array.from(hints).some((hint) => hint === r.target || hint.contains(r.target)))) {
                schedule();
            }
        }).observe(form, { childList: true, subtree: true });
        update();
    });
})();
