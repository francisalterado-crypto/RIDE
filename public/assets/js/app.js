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

    // Keep in sync with PROSE_POST_FIELD_PATTERN in app/helpers.php.
    const PROSE_FIELD_PATTERN = new RegExp('(^|_)(abstract|body|caption|comments?|message|narrative|notes?|remarks?|feedback|reason|description|summary|vision|mission'
        + '|rationale|introduction|significance|objectives?|methods|methodology|outcomes|outputs|deliverables|strategies|indicator|impact|effects|inputs'
        + '|considerations|development|cited|references|gaps|plan|analysis|overview|progress|targets|practices|learned|challenges|problems'
        + '|available|responsibility|sharing|highlights_of_results|results|details|komento|content|text|bio)$');

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

        // Same exemptions as should_title_case_post_field() in PHP, checked on the last key of the name
        // ("entries[0][remarks]" -> "remarks"): links, and sentence text that keeps the case it was typed in.
        const key = (name.match(/([^[\]]+)\]?$/) || [, name])[1];
        if (key === 'url' || key === 'link' || key === 'doi' || key.includes('_url') || key.includes('_link')) {
            return false;
        }
        if (PROSE_FIELD_PATTERN.test(key)) {
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

    const capitalizePageFields = (scope = document) => {
        scope.querySelectorAll('input, textarea').forEach(applyCapitalization);
    };

    const initFormWordCapitalization = () => {
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

    const initSidebarGroups = () => {
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
    };

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
        const tabsBar = document.getElementById('proposal-registry-tabs');
        const tabButtons = tabsBar ? Array.from(tabsBar.querySelectorAll('[data-tab]')) : [];
        const formSelect = document.getElementById('proposal-registry-form');
        const yearSelect = document.getElementById('proposal-registry-year');
        const exportLink = document.getElementById('proposal-registry-export');
        const emptyNote = document.getElementById('proposal-registry-empty');
        const tabNames = tabButtons.map((button) => button.dataset.tab);
        const fromHash = window.location.hash.replace('#', '');
        let activeTab = tabNames.includes(fromHash) ? fromHash : ((tabsBar && tabsBar.dataset.defaultTab) || 'all');

        const updateFilter = () => {
            const query = searchInput.value.trim().toLowerCase();
            const form = formSelect ? formSelect.value : '';
            const year = yearSelect ? yearSelect.value : '';
            let visible = 0;

            rows.forEach((row) => {
                const haystack = row.getAttribute('data-search') || row.textContent.toLowerCase();
                const inTab = (row.dataset.tabs || 'all').split(' ').includes(activeTab);
                const matches = inTab
                    && (form === '' || row.dataset.form === form)
                    && (year === '' || row.dataset.year === year)
                    && (query === '' || haystack.includes(query));
                row.hidden = !matches;
                if (matches) {
                    visible += 1;
                }
            });

            tabButtons.forEach((button) => {
                const selected = button.dataset.tab === activeTab;
                button.setAttribute('aria-selected', selected ? 'true' : 'false');
                button.classList.toggle('is-active', selected);
                button.classList.toggle('active', selected);
            });
            if (emptyNote) {
                emptyNote.hidden = visible > 0;
            }
            if (exportLink) {
                const params = new URLSearchParams({ tab: activeTab, form, year, q: searchInput.value.trim() });
                exportLink.href = `${exportLink.dataset.base}${exportLink.dataset.base.includes("?") ? "&" : "?"}${params.toString()}`;
            }
            if (countLabel) {
                countLabel.textContent = visible === total
                    ? `${total} record${total === 1 ? '' : 's'}`
                    : `${visible} of ${total} record${total === 1 ? '' : 's'}`;
            }
        };

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeTab = button.dataset.tab;
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', `#${activeTab}`);
                }
                updateFilter();
            });
        });
        formSelect?.addEventListener('change', updateFilter);
        yearSelect?.addEventListener('change', updateFilter);
        searchInput.addEventListener('input', updateFilter);
        updateFilter();
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

    // Sidebar: collapse to an icon rail on wide screens (remembered per browser); a drawer on small screens.
    const initSidebar = () => {
        const sidebar = document.getElementById('app-sidebar');
        if (!sidebar) {
            return;
        }
        const root = document.documentElement;
        const toggle = document.getElementById('sidebar-toggle');
        const openButton = document.getElementById('sidebar-open');
        const backdrop = document.getElementById('sidebar-backdrop');
        const small = window.matchMedia('(max-width: 800px)');
        const storageKey = 'ride:sidebar-collapsed';

        // Collapsed links still say where they go, as tooltips.
        sidebar.querySelectorAll('.nav-item').forEach((item) => {
            const label = item.querySelector('span')?.textContent.trim();
            if (label && !item.title) {
                item.title = label;
            }
        });

        const syncToggle = () => {
            const collapsed = root.classList.contains('sidebar-collapsed');
            toggle?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            if (toggle) {
                toggle.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
            }
        };
        const setDrawer = (open) => {
            root.classList.toggle('sidebar-open', open);
            openButton?.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (backdrop) {
                backdrop.hidden = !open;
            }
        };

        toggle?.addEventListener('click', () => {
            if (small.matches) {
                setDrawer(false);
                return;
            }
            const collapsed = root.classList.toggle('sidebar-collapsed');
            try {
                if (collapsed) {
                    localStorage.setItem(storageKey, '1');
                } else {
                    localStorage.removeItem(storageKey);
                }
            } catch (error) {
                // Private mode: the choice just isn't remembered.
            }
            syncToggle();
        });
        // A group clicked on the icon rail opens the sidebar so its links can be seen.
        sidebar.querySelectorAll('.nav-item-toggle').forEach((groupToggle) => {
            groupToggle.addEventListener('click', (event) => {
                if (!small.matches && root.classList.contains('sidebar-collapsed')) {
                    event.stopImmediatePropagation();
                    root.classList.remove('sidebar-collapsed');
                    try { localStorage.removeItem(storageKey); } catch (error) { /* ignore */ }
                    syncToggle();
                }
            }, true);
        });
        openButton?.addEventListener('click', () => setDrawer(!root.classList.contains('sidebar-open')));
        backdrop?.addEventListener('click', () => setDrawer(false));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && root.classList.contains('sidebar-open')) {
                setDrawer(false);
            }
        });
        small.addEventListener('change', () => setDrawer(false));
        syncToggle();
    };

    // Button icons: every .btn gets a Font Awesome icon that matches its action, read from its label, so the
    // same action looks the same on every page (labels are often built in PHP, so this is done in one place).
    // A button that already has an icon, or has data-no-icon, is left alone. First matching rule wins.
    const BUTTON_ICON_RULES = [
        [/^print|^save as pdf/, 'fa-print'],
        [/^return for revision|^return\b/, 'fa-rotate-left'],
        [/^submit|^send|^post\b|^resubmit/, 'fa-paper-plane'],
        [/^approve|^accept|^endorse|^mark (as )?(reviewed|completed|complete)|^complete|^finish|^use this/, 'fa-check'],
        [/^decline|^reject/, 'fa-ban'],
        [/^reset password|password/, 'fa-key'],
        [/^save|^update/, 'fa-floppy-disk'],
        [/^cancel|^close|^discard|^clear/, 'fa-xmark'],
        [/^delete|^remove/, 'fa-trash-can'],
        [/^restore/, 'fa-trash-arrow-up'],
        [/^edit|^change/, 'fa-pen-to-square'],
        [/^upload/, 'fa-upload'],
        [/^download|^export/, 'fa-download'],
        [/^generate/, 'fa-file-circle-plus'],
        [/^\+|^add|^new|^create|^record\b/, 'fa-plus'],
        [/^back/, 'fa-arrow-left'],
        [/^continue|^next|^proceed/, 'fa-arrow-right'],
        [/^monitor/, 'fa-chart-line'],
        [/^message|conversation|^reply/, 'fa-comment'],
        [/^open/, 'fa-folder-open'],
        [/^view|^details|^show|^preview/, 'fa-eye'],
        [/^search/, 'fa-magnifying-glass'],
        [/^filter|^apply/, 'fa-filter'],
        [/^manage|^settings/, 'fa-gear'],
        [/^log ?out|^sign out/, 'fa-right-from-bracket'],
    ];
    const decorateButtons = (root) => {
        if (!root || !root.querySelectorAll) {
            return;
        }
        const buttons = root.matches?.('.btn') ? [root] : [];
        root.querySelectorAll('.btn, .webform-add-btn').forEach((button) => buttons.push(button));
        buttons.forEach((button) => {
            if (button.dataset.noIcon !== undefined || button.dataset.iconDone || button.querySelector('i, svg, img')) {
                return;
            }
            const label = (button.textContent || button.value || '').trim().toLowerCase();
            const rule = BUTTON_ICON_RULES.find(([pattern]) => pattern.test(label));
            button.dataset.iconDone = '1';
            if (!rule || button.tagName === 'INPUT') {
                return;
            }
            // "+ Add Row": the plus sign becomes the icon.
            const first = button.firstChild;
            if (first && first.nodeType === Node.TEXT_NODE && /^\s*\+\s*/.test(first.textContent)) {
                first.textContent = first.textContent.replace(/^\s*\+\s*/, '');
            }
            const icon = document.createElement('i');
            icon.className = `fa-solid ${rule[1]} btn-icon-auto`;
            icon.setAttribute('aria-hidden', 'true');
            button.prepend(icon);
        });
    };
    let buttonObserver = null;
    const watchButtons = () => {
        const target = document.querySelector('.page-body');
        if (!target || buttonObserver) {
            return;
        }
        buttonObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    decorateButtons(node);
                }
            }));
        });
        buttonObserver.observe(target, { childList: true, subtree: true });
    };

    // Per-page setup: runs on first load and again after each in-app navigation swaps the page body.
    const initPageContent = () => {
        decorateButtons(document.querySelector('.page-body'));
        watchButtons();
        capitalizePageFields(document.querySelector('.page-body') || document);
        initAccountsSearch();
        initProposalRegistrySearch();
        initChipInputs();
    };

    // In-app navigation: links inside the portal fetch the next page and swap only the main content, so the
    // sidebar keeps its place. Anything unusual (downloads, other layouts, errors) falls back to a full load.
    const initPageNavigation = () => {
        const body = document.body;
        const pageBody = document.querySelector('.main-content .page-body');
        const sidebar = document.getElementById('app-sidebar');
        if (!pageBody || !sidebar || !window.fetch || !window.history || !window.DOMParser) {
            return;
        }
        const base = new URL((body.dataset.appBase || window.location.origin + '/').replace(/\/?$/, '/'), window.location.href);
        const skip = /(^|\/)(logout|login|register|print|download|downloads|export|preview|forgot-password|reset-password)(\/|$)|\/documents\/\d+|\.[a-z0-9]{2,5}$/i;
        const publicPages = /^(|news|journal|conferences|articles(\/.*)?)$/;
        const loadedScripts = new Set(Array.from(document.scripts).filter((s) => s.src).map((s) => s.src));
        let controller = null;
        let pageListeners = new AbortController();
        let navId = 0;
        let scrollTimer = 0;

        const saveSidebarScroll = () => {
            try { sessionStorage.setItem('ride:sidebar-scroll', String(sidebar.scrollTop)); } catch (e) { /* ignore */ }
        };
        sidebar.addEventListener('scroll', saveSidebarScroll, { passive: true });
        window.addEventListener('pagehide', saveSidebarScroll);

        const canHandle = (url) => {
            if (url.origin !== base.origin || !url.pathname.startsWith(base.pathname)) {
                return false;
            }
            const rest = url.pathname.slice(base.pathname.length).replace(/\/$/, '');
            return !skip.test(url.pathname) && !publicPages.test(rest);
        };

        const skeletonKind = (url) => {
            const path = url.pathname.slice(base.pathname.length);
            if (/(^|\/)(create|new|edit)(\/|$)/.test(path)) { return 'form'; }
            if (/^dashboard/.test(path)) { return 'dashboard'; }
            if (/\/\d+(\/[a-z-]+)?$/.test(path)) { return 'detail'; }
            return 'table';
        };

        const line = (w, cls = '') => `<span class="sk sk-line ${cls}" style="width:${w}"></span>`;
        const skeletonHtml = (kind) => {
            const head = `<div class="sk-card"><div class="sk-row">${line('34%', 'sk-lg')}${line('14%', 'sk-pill')}</div>${line('52%')}</div>`;
            if (kind === 'dashboard') {
                const stat = `<div class="sk-card sk-stat">${line('40%')}${line('60%', 'sk-xl')}</div>`;
                return `<div class="sk-grid">${stat.repeat(4)}</div><div class="sk-grid sk-grid-2"><div class="sk-card"><span class="sk sk-block"></span></div><div class="sk-card">${line('45%', 'sk-lg')}${line('90%')}${line('80%')}${line('85%')}${line('70%')}</div></div>`;
            }
            if (kind === 'form') {
                const field = `<div class="sk-field">${line('28%')}<span class="sk sk-input"></span></div>`;
                return `${head}<div class="sk-card">${line('30%', 'sk-lg')}<div class="sk-grid sk-grid-2">${field.repeat(4)}</div>${field}</div><div class="sk-card">${line('30%', 'sk-lg')}${field.repeat(2)}</div>`;
            }
            if (kind === 'detail') {
                const fact = `<div>${line('50%')}${line('80%')}</div>`;
                return `${head}<div class="sk-card"><div class="sk-grid">${fact.repeat(4)}</div></div><div class="sk-card">${line('30%', 'sk-lg')}${line('95%')}${line('90%')}${line('70%')}</div>`;
            }
            const row = `<div class="sk-trow">${line('30%')}${line('18%')}${line('16%')}${line('12%')}</div>`;
            return `<div class="sk-card"><div class="sk-row">${line('26%', 'sk-lg')}<span class="sk sk-input" style="width:30%"></span></div>${row.repeat(8)}</div>`;
        };

        const showSkeleton = (url) => {
            pageBody.setAttribute('aria-busy', 'true');
            pageBody.innerHTML = `<div class="page-skeleton" aria-hidden="true">${skeletonHtml(skeletonKind(url))}</div><span class="sr-only">Loading</span>`;
            const title = document.querySelector('.top-bar .page-title');
            if (title) {
                title.innerHTML = '<h1><span class="sk sk-line sk-lg" style="width:220px"></span></h1>';
            }
        };

        // Copy the new page's active highlights and badge counts onto the sidebar that stays on screen.
        const syncSidebar = (nextSidebar) => {
            const keyed = (root) => {
                const map = new Map();
                root.querySelectorAll('a[href], button[id], button[aria-controls], .nav-group, .nav-subgroup').forEach((el) => {
                    const key = `${el.tagName}|${el.id || el.getAttribute('href') || el.getAttribute('aria-controls') || ''}`;
                    const list = map.get(key) || [];
                    list.push(el);
                    map.set(key, list);
                });
                return map;
            };
            const current = keyed(sidebar);
            keyed(nextSidebar).forEach((nextList, key) => {
                const list = current.get(key) || [];
                nextList.forEach((next, i) => {
                    const el = list[i];
                    if (!el) { return; }
                    const keep = el.classList.contains('is-collapsed');
                    el.className = next.className;
                    el.classList.toggle('is-collapsed', keep);
                    if (next.hasAttribute('aria-current')) {
                        el.setAttribute('aria-current', next.getAttribute('aria-current'));
                    } else {
                        el.removeAttribute('aria-current');
                    }
                    if (el.tagName === 'A') {
                        const title = el.title;
                        el.innerHTML = next.innerHTML;
                        el.title = title;
                    }
                });
            });
            // A sub-group holding the current page opens.
            sidebar.querySelectorAll('.nav-subgroup.has-active.is-collapsed').forEach((group) => {
                group.classList.remove('is-collapsed');
                const items = group.querySelector('.nav-subgroup-items');
                if (items) { items.hidden = false; }
                group.querySelector('.nav-subgroup-toggle')?.setAttribute('aria-expanded', 'true');
            });
        };

        // Run the new page's scripts in order. Listeners they add to document/window are removed on the next
        // navigation, and DOMContentLoaded/load handlers run right away (those events already happened).
        const runScripts = async (container, signal) => {
            const realDoc = document.addEventListener;
            const realWin = window.addEventListener;
            const wrap = (real, target) => function (type, listener, options) {
                if ((type === 'DOMContentLoaded' || (target === window && type === 'load')) && typeof listener === 'function') {
                    setTimeout(() => listener.call(target, new Event(type)), 0);
                    return undefined;
                }
                const opts = typeof options === 'object' && options !== null ? { ...options, signal } : { capture: !!options, signal };
                return real.call(this, type, listener, opts);
            };
            document.addEventListener = wrap(realDoc, document);
            window.addEventListener = wrap(realWin, window);
            try {
                for (const old of Array.from(container.querySelectorAll('script'))) {
                    const type = (old.type || '').trim().toLowerCase();
                    if (type !== '' && type !== 'text/javascript' && type !== 'module') {
                        continue;
                    }
                    const script = document.createElement('script');
                    Array.from(old.attributes).forEach((a) => script.setAttribute(a.name, a.value));
                    if (old.src) {
                        // Libraries from a CDN load once; the app's own page scripts run again each visit.
                        const external = new URL(old.src, window.location.href).origin !== window.location.origin;
                        if (external && loadedScripts.has(old.src)) {
                            continue;
                        }
                        loadedScripts.add(old.src);
                        await new Promise((resolve) => {
                            script.onload = resolve;
                            script.onerror = resolve;
                            old.replaceWith(script);
                        });
                    } else {
                        // Block scope so const/let names can be declared again when the page is revisited.
                        script.textContent = type === 'module' ? old.textContent : `{\n${old.textContent}\n}`;
                        old.replaceWith(script);
                    }
                }
            } finally {
                document.addEventListener = realDoc;
                window.addEventListener = realWin;
            }
        };

        const fullLoad = (href) => { window.location.assign(href); };

        const navigate = async (url, { push = true, scroll = 0 } = {}) => {
            const id = ++navId;
            controller?.abort();
            controller = new AbortController();
            document.documentElement.classList.remove('sidebar-open');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (backdrop) { backdrop.hidden = true; }
            document.documentElement.classList.add('is-navigating');
            const skeletonTimer = setTimeout(() => showSkeleton(url), 120);
            try {
                const res = await fetch(url.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'ride-nav' }, signal: controller.signal });
                const finalUrl = new URL(res.url || url.href);
                if (!(res.headers.get('content-type') || '').includes('text/html') || !canHandle(finalUrl)) {
                    fullLoad(finalUrl.href);
                    return;
                }
                const html = await res.text();
                if (id !== navId) { return; }
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nextBody = doc.querySelector('.main-content .page-body');
                const nextSidebar = doc.getElementById('app-sidebar');
                if (!nextBody || !nextSidebar) {
                    fullLoad(finalUrl.href);
                    return;
                }
                clearTimeout(skeletonTimer);
                pageListeners.abort();
                pageListeners = new AbortController();
                if (push) {
                    window.history.replaceState({ ...(window.history.state || {}), scroll: window.scrollY }, '');
                    window.history.pushState({ ride: true, scroll: 0 }, '', finalUrl.href + (url.hash || ''));
                }
                document.title = doc.title;
                const title = document.querySelector('.top-bar .page-title');
                const nextTitle = doc.querySelector('.top-bar .page-title');
                if (title && nextTitle) { title.innerHTML = nextTitle.innerHTML; }
                const actions = document.querySelector('.top-bar .header-actions');
                const nextActions = doc.querySelector('.top-bar .header-actions');
                if (actions && nextActions) { actions.innerHTML = nextActions.innerHTML; }
                syncSidebar(nextSidebar);
                pageBody.removeAttribute('aria-busy');
                pageBody.innerHTML = nextBody.innerHTML;
                window.scrollTo(0, scroll);
                if (url.hash) {
                    document.getElementById(decodeURIComponent(url.hash.slice(1)))?.scrollIntoView();
                }
                await runScripts(pageBody, pageListeners.signal);
                initPageContent();
                document.dispatchEvent(new CustomEvent('ride:page-load', { detail: { url: finalUrl.href } }));
            } catch (error) {
                if (error && error.name === 'AbortError') { return; }
                fullLoad(url.href);
            } finally {
                clearTimeout(skeletonTimer);
                if (id === navId) { document.documentElement.classList.remove('is-navigating'); }
            }
        };

        document.addEventListener('click', (event) => {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }
            const link = event.target.closest('a[href]');
            if (!link || link.hasAttribute('download') || link.closest('[data-no-pjax]') || (link.target && link.target !== '_self')) {
                return;
            }
            const raw = link.getAttribute('href') || '';
            if (raw === '' || raw.startsWith('#') || /^(mailto|tel|javascript):/i.test(raw)) {
                return;
            }
            const url = new URL(link.href, window.location.href);
            if (!canHandle(url)) {
                return;
            }
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash !== '') {
                return;
            }
            event.preventDefault();
            // Highlight a clicked menu link right away; the loaded page confirms it.
            if (sidebar.contains(link)) {
                sidebar.querySelectorAll('.nav-item.active, .nav-subitem.active').forEach((el) => { if (!el.classList.contains('nav-item-toggle')) { el.classList.remove('active'); } });
                link.classList.add('active');
            }
            navigate(url);
        });

        // GET forms (filters, search) load in place too.
        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (event.defaultPrevented || !(form instanceof HTMLFormElement) || (form.getAttribute('method') || 'get').toLowerCase() !== 'get'
                || form.closest('[data-no-pjax]') || (form.target && form.target !== '_self')) {
                return;
            }
            const url = new URL(form.getAttribute('action') || window.location.href, window.location.href);
            if (!canHandle(url)) {
                return;
            }
            url.search = new URLSearchParams(new FormData(form, event.submitter || undefined)).toString();
            event.preventDefault();
            navigate(url);
        });

        window.history.replaceState({ ride: true, scroll: window.scrollY }, '');
        window.addEventListener('popstate', (event) => {
            const url = new URL(window.location.href);
            if (!event.state || !event.state.ride || !canHandle(url)) {
                fullLoad(url.href);
                return;
            }
            navigate(url, { push: false, scroll: event.state.scroll || 0 });
        });
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(() => {
                window.history.replaceState({ ...(window.history.state || {}), scroll: window.scrollY }, '');
            }, 150);
        }, { passive: true });
    };

    const initApp = () => {
        initSidebarGroups();
        initSidebar();
        initFormWordCapitalization();
        initPageContent();
        initPageNavigation();
        // Transitions stay off until after the first paint so the restored sidebar state does not animate in.
        requestAnimationFrame(() => requestAnimationFrame(() => document.documentElement.classList.remove('ride-preload')));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApp, { once: true });
    } else {
        initApp();
    }
})();
