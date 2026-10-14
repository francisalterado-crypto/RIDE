(function () {
    const capitalizeWords = (value) => {
        if (typeof value !== 'string' || value === '') {
            return value;
        }

        return value.replace(/(^|[\s\-'])([a-z])/g, (_, prefix, letter) => prefix + letter.toUpperCase());
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

    const initApp = () => {
        initFormWordCapitalization();
        initAccountsSearch();
        initProposalRegistrySearch();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApp);
    } else {
        initApp();
    }
})();
