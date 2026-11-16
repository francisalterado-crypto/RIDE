<?php
/**
 * Dashboard search: finds the pages and forms the signed-in user can open. Pages come from the sidebar (which
 * already shows only what the user's roles allow); downloadable office forms come from the forms repository.
 */
use App\Controllers\FormTemplateController;
use App\Models\FormTemplate;

$searchExtra = [
    ['label' => 'Messages', 'context' => 'Account', 'href' => base_url('messages'), 'icon' => 'fa-envelope', 'keywords' => 'inbox chat'],
    ['label' => 'Account Settings', 'context' => 'Account', 'href' => base_url('profile'), 'icon' => 'fa-user-gear', 'keywords' => 'profile password photo title prefix campus'],
];
foreach (FormTemplate::activeFor(FormTemplateController::visibleAudiences()) as $template) {
    $searchExtra[] = [
        'label' => (string) $template['title'],
        'context' => 'Downloadable Forms',
        'href' => base_url('forms/' . (int) $template['id'] . '/download'),
        'icon' => 'fa-file-arrow-down',
        'keywords' => trim((string) ($template['description'] ?? '') . ' ' . (string) ($template['original_name'] ?? '') . ' template download blank'),
    ];
}
?>
<div class="feature-search" id="feature-search" data-extra="<?= htmlspecialchars((string) json_encode($searchExtra, JSON_UNESCAPED_SLASHES)) ?>">
    <label class="sr-only" for="feature-search-input">Search for forms, requests, and more</label>
    <div class="feature-search-box">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" id="feature-search-input" autocomplete="off" spellcheck="false"
            placeholder="Search for forms, requests, and more"
            role="combobox" aria-expanded="false" aria-controls="feature-search-results" aria-autocomplete="list">
        <kbd class="feature-search-key" aria-hidden="true">/</kbd>
    </div>
    <ul class="feature-search-results" id="feature-search-results" role="listbox" hidden></ul>
</div>

<script>
(() => {
    const root = document.getElementById('feature-search');
    const input = document.getElementById('feature-search-input');
    const list = document.getElementById('feature-search-results');
    if (!root || !input || !list) {
        return;
    }

    // Index: every link in the sidebar, with its section and menu as context.
    const items = [];
    const seen = new Set();
    const add = (item) => {
        const key = `${item.href}|${item.label}`;
        if (!item.label || seen.has(key)) {
            return;
        }
        seen.add(key);
        item.haystack = `${item.label} ${item.context} ${item.keywords || ''}`.toLowerCase();
        items.push(item);
    };
    document.querySelectorAll('#app-sidebar a.nav-item, #app-sidebar a.nav-subitem').forEach((link) => {
        const label = link.querySelector('span')?.textContent.trim() || '';
        const context = [];
        const section = link.closest('.nav-section')?.querySelector('.nav-section-label')?.textContent.trim();
        if (section) { context.push(section); }
        const group = link.closest('.nav-group')?.querySelector('.nav-item-toggle span')?.textContent.trim();
        if (group) { context.push(group); }
        const subgroup = link.closest('.nav-subgroup')?.querySelector('.nav-subgroup-toggle span')?.textContent.trim();
        if (subgroup) { context.push(subgroup); }
        const icon = [...(link.querySelector('i')?.classList || [])].find((name) => name.startsWith('fa-') && name !== 'fa-solid') || 'fa-circle';
        add({ label, context: context.join(' / '), href: link.href, icon });
    });
    try {
        JSON.parse(root.dataset.extra || '[]').forEach(add);
    } catch (error) { /* no extra entries */ }

    let results = [];
    let active = -1;
    const escape = (text) => text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const search = (query) => {
        const terms = query.toLowerCase().split(/\s+/).filter(Boolean);
        if (terms.length === 0) {
            return [];
        }
        return items
            .filter((item) => terms.every((term) => item.haystack.includes(term)))
            .map((item) => {
                const label = item.label.toLowerCase();
                const score = (label.startsWith(terms[0]) ? 0 : label.includes(terms[0]) ? 1 : 2);
                return { item, score };
            })
            .sort((a, b) => a.score - b.score || a.item.label.localeCompare(b.item.label))
            .slice(0, 8)
            .map((entry) => entry.item);
    };

    const setActive = (index) => {
        active = index;
        list.querySelectorAll('[role="option"]').forEach((option, i) => {
            option.classList.toggle('is-active', i === active);
            option.setAttribute('aria-selected', i === active ? 'true' : 'false');
            if (i === active) {
                input.setAttribute('aria-activedescendant', option.id);
                option.scrollIntoView({ block: 'nearest' });
            }
        });
        if (active < 0) {
            input.removeAttribute('aria-activedescendant');
        }
    };

    const render = () => {
        const query = input.value.trim();
        results = search(query);
        if (query === '') {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            return;
        }
        list.innerHTML = results.length === 0
            ? '<li class="feature-search-empty">No matching features or forms.</li>'
            : results.map((item, i) => `
                <li role="option" id="feature-search-option-${i}" aria-selected="false">
                    <a href="${escape(item.href)}">
                        <span class="feature-search-icon"><i class="fa-solid ${escape(item.icon)}" aria-hidden="true"></i></span>
                        <span class="feature-search-text">
                            <span class="feature-search-label">${escape(item.label)}</span>
                            ${item.context ? `<span class="feature-search-context">${escape(item.context)}</span>` : ''}
                        </span>
                    </a>
                </li>`).join('');
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        setActive(results.length > 0 ? 0 : -1);
    };

    input.addEventListener('input', render);
    input.addEventListener('focus', () => { if (input.value.trim() !== '') { render(); } });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' && results.length > 0) {
            event.preventDefault();
            setActive((active + 1) % results.length);
        } else if (event.key === 'ArrowUp' && results.length > 0) {
            event.preventDefault();
            setActive((active - 1 + results.length) % results.length);
        } else if (event.key === 'Enter' && active >= 0) {
            event.preventDefault();
            list.querySelectorAll('[role="option"] a')[active]?.click();
        } else if (event.key === 'Escape') {
            input.value = '';
            render();
        }
    });
    list.addEventListener('mousemove', (event) => {
        const option = event.target.closest('[role="option"]');
        if (option) {
            setActive([...list.children].indexOf(option));
        }
    });
    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
        }
    });
    // "/" focuses the search box from anywhere on the dashboard.
    document.addEventListener('keydown', (event) => {
        const target = event.target;
        const typing = target instanceof HTMLElement && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName));
        if (event.key === '/' && !typing && document.body.contains(input)) {
            event.preventDefault();
            input.focus();
        }
    });
})();
</script>
