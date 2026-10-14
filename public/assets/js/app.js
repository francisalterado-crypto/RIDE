(function () {
    const capitalizeWords = (value) => {
        if (typeof value !== 'string' || value === '') {
            return value;
        }

        // Small connecting words stay as typed unless they start the text (matches title_case_words() in PHP).
        const minorWords = ['a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in', 'nor', 'of', 'on', 'or', 'the', 'to', 'vs', 'via', 'ng', 'sa'];

        return value.replace(/(^|[\s\-'])([a-z])([\p{L}]*)/gu, (whole, prefix, letter, rest, offset) => {
            if (offset > 0 && prefix.trim() === '' && minorWords.includes(letter + rest)) {
                return whole;
            }

            return prefix + letter.toUpperCase() + rest;
        });
    };

    const shouldCapitalizeField = (field) => {
        if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) {
            return false;
        }

        if (field.disabled || field.readOnly) {
            return false;
        }

        if (field.classList.contains('no-capitalize') || field.closest('[data-capitalize="off"]')) {
            return false;
        }

        if (field instanceof HTMLInputElement) {
            const type = (field.type || 'text').toLowerCase();
            if (['email', 'password', 'url', 'number', 'date', 'datetime-local', 'time', 'month', 'week', 'tel', 'search', 'file', 'hidden', 'checkbox', 'radio', 'range', 'color', 'submit', 'button', 'reset'].includes(type)) {
                return false;
            }
        }

        const name = (field.name || '').toLowerCase();
        if (name === '_csrf' || name === 'email' || name.endsWith('_email') || name.includes('password')) {
            return false;
        }

        return true;
    };

    const applyCapitalization = (field) => {
        if (!shouldCapitalizeField(field)) {
            return;
        }

        const nextValue = capitalizeWords(field.value);
        if (nextValue !== field.value) {
            field.value = nextValue;
        }
    };

    const initFormWordCapitalization = () => {
        document.querySelectorAll('input, textarea').forEach(applyCapitalization);

        document.addEventListener('blur', (event) => {
            applyCapitalization(event.target);
        }, true);

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            form.querySelectorAll('input, textarea').forEach(applyCapitalization);
        }, true);
    };

    const initCollapsibleNav = (groupId, toggleId, submenuId, storageKey) => {
        const group = document.getElementById(groupId);
        const toggle = document.getElementById(toggleId);
        const submenu = document.getElementById(submenuId);
        if (!group || !toggle || !submenu) {
            return;
        }

        const setCollapsed = (collapsed) => {
            group.classList.toggle('is-collapsed', collapsed);
            submenu.hidden = collapsed;
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            try {
                if (collapsed) {
                    localStorage.setItem(storageKey, '1');
                } else {
                    localStorage.removeItem(storageKey);
                }
            } catch (e) {
                /* ignore storage errors */
            }
        };

        try {
            if (localStorage.getItem(storageKey) === '1') {
                setCollapsed(true);
            }
        } catch (e) {
            /* ignore storage errors */
        }

        toggle.addEventListener('click', () => {
            setCollapsed(!group.classList.contains('is-collapsed'));
        });
    };

    initCollapsibleNav(
        'research-proposal-nav',
        'research-proposal-nav-toggle',
        'research-proposal-submenu',
        'ride:nav:research-proposal-collapsed'
    );

    initCollapsibleNav(
        'research-extension-nav',
        'research-extension-nav-toggle',
        'research-extension-submenu',
        'ride:nav:research-extension-collapsed'
    );

    initCollapsibleNav(
        'consolidated-reports-nav',
        'consolidated-reports-nav-toggle',
        'consolidated-reports-submenu',
        'ride:nav:consolidated-reports-collapsed'
    );

    initCollapsibleNav(
        'terminal-report-nav',
        'terminal-report-nav-toggle',
        'terminal-report-submenu',
        'ride:nav:terminal-report-collapsed'
    );

    // Nested collapsible groups inside a nav submenu (e.g. Quarterly Report).
    document.querySelectorAll('[data-nav-subgroup]').forEach((group) => {
        const toggle = group.querySelector('.nav-subgroup-toggle');
        const items = group.querySelector('.nav-subgroup-items');
        if (!toggle || !items || !group.id) {
            return;
        }
        const storageKey = `ride:nav:${group.id}-collapsed`;
        const setCollapsed = (collapsed, remember = true) => {
            group.classList.toggle('is-collapsed', collapsed);
            items.hidden = collapsed;
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            if (!remember) {
                return;
            }
            try {
                if (collapsed) {
                    localStorage.setItem(storageKey, '1');
                } else {
                    localStorage.removeItem(storageKey);
                }
            } catch (e) {
                /* ignore storage errors */
            }
        };

        try {
            // Keep the group open while one of its pages is the current page.
            if (localStorage.getItem(storageKey) === '1' && !group.classList.contains('has-active')) {
                setCollapsed(true, false);
            }
        } catch (e) {
            /* ignore storage errors */
        }

        toggle.addEventListener('click', () => {
            setCollapsed(!group.classList.contains('is-collapsed'));
        });
    });

    const initAccountsSearch = () => {
        const searchInput = document.getElementById('accounts-search');
        const table = document.getElementById('accounts-table');
        const countLabel = document.getElementById('accounts-list-count');
        if (!searchInput || !table) {
            return;
        }

        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const total = rows.length;

        const updateFilter = () => {
            const query = searchInput.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach((row) => {
                const haystack = row.getAttribute('data-search') || row.textContent.toLowerCase();
                const matches = query === '' || haystack.includes(query);
                row.hidden = !matches;
                if (matches) {
                    visible += 1;
                }
            });

            if (countLabel) {
                countLabel.textContent = query === ''
                    ? `${total} account${total === 1 ? '' : 's'}`
                    : `${visible} of ${total} account${total === 1 ? '' : 's'}`;
            }
        };

        searchInput.addEventListener('input', updateFilter);
    };

    const initProposalRegistrySearch = () => {
        const searchInput = document.getElementById('proposal-registry-search');
        const table = document.getElementById('proposal-registry-table');
        const countLabel = document.getElementById('proposal-registry-count');
        if (!searchInput || !table) {
            return;
        }

        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const total = rows.length;

        const updateFilter = () => {
            const query = searchInput.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach((row) => {
                const haystack = row.getAttribute('data-search') || row.textContent.toLowerCase();
                const matches = query === '' || haystack.includes(query);
                row.hidden = !matches;
                if (matches) {
                    visible += 1;
                }
            });

            if (countLabel) {
                countLabel.textContent = query === ''
                    ? `${total} record${total === 1 ? '' : 's'}`
                    : `${visible} of ${total} record${total === 1 ? '' : 's'}`;
            }
        };

        searchInput.addEventListener('input', updateFilter);
    };

    // Chip input: type a value and press Enter or comma to add it; each chip submits as a hidden field.
    const initChipInputs = () => {
        document.querySelectorAll('[data-chip-input]').forEach((box) => {
            const entry = box.querySelector('.chip-entry');
            const form = box.closest('form');
            if (!entry || !form) {
                return;
            }
            const max = parseInt(box.dataset.max || '15', 10);
            const fieldName = 'specializations[]';
            const suggestions = form.querySelectorAll('.chip-suggestion');

            const values = () => Array.from(box.querySelectorAll('input[type="hidden"]')).map((input) => input.value);
            const hasValue = (value) => values().some((existing) => existing.toLowerCase() === value.toLowerCase());

            const refreshSuggestions = () => {
                const query = entry.value.trim().toLowerCase();
                suggestions.forEach((button) => {
                    const value = button.dataset.value || '';
                    button.hidden = hasValue(value) || (query !== '' && !value.toLowerCase().includes(query));
                });
                entry.disabled = values().length >= max;
                entry.placeholder = entry.disabled ? `Limit of ${max} reached` : 'Type a specialization, then press Enter';
            };

            const bindRemove = (chip) => {
                chip.querySelector('.chip-remove')?.addEventListener('click', () => {
                    chip.remove();
                    refreshSuggestions();
                    entry.focus();
                });
            };

            const addChip = (raw) => {
                const value = raw.replace(/\s+/g, ' ').trim().slice(0, 80);
                if (value === '' || hasValue(value) || values().length >= max) {
                    return;
                }
                const chip = document.createElement('span');
                chip.className = 'chip-item';
                const text = document.createElement('span');
                text.className = 'chip-text';
                text.textContent = value;
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = fieldName;
                hidden.value = value;
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'chip-remove';
                remove.setAttribute('aria-label', `Remove ${value}`);
                remove.innerHTML = '<i class="fas fa-xmark" aria-hidden="true"></i>';
                chip.append(text, hidden, remove);
                box.insertBefore(chip, entry);
                bindRemove(chip);
                refreshSuggestions();
            };

            box.querySelectorAll('.chip-item').forEach(bindRemove);
            box.addEventListener('click', (event) => {
                if (event.target === box) {
                    entry.focus();
                }
            });
            entry.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ',') {
                    event.preventDefault();
                    addChip(entry.value);
                    entry.value = '';
                    refreshSuggestions();
                } else if (event.key === 'Backspace' && entry.value === '') {
                    const chips = box.querySelectorAll('.chip-item');
                    chips[chips.length - 1]?.remove();
                    refreshSuggestions();
                }
            });
            entry.addEventListener('input', refreshSuggestions);
            // Text left in the box when saving is added as a chip too.
            form.addEventListener('submit', () => {
                if (entry.value.trim() !== '') {
                    addChip(entry.value);
                    entry.value = '';
                }
            });
            suggestions.forEach((button) => {
                button.addEventListener('click', () => {
                    addChip(button.dataset.value || '');
                    entry.value = '';
                    refreshSuggestions();
                    entry.focus();
                });
            });
            refreshSuggestions();
        });
    };

    const initApp = () => {
        initFormWordCapitalization();
        initAccountsSearch();
        initProposalRegistrySearch();
        initChipInputs();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApp);
    } else {
        initApp();
    }
})();
