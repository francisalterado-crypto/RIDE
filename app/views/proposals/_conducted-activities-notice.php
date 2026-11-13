<?php
/**
 * Quarterly report rows come from the entries of the selected reporting period (Conducted Activities for ESO,
 * Research Accomplishments for RDO). They are shown read-only and rebuilt on save, so a row is changed by editing
 * its entry. Changing the period reloads the page so the rows follow it.
 */
/** @var int|null $conductedActivitiesCount */
/** @var string|null $conductedActivitiesPeriod */
/** @var string|null $conductedActivitiesSlug */
/** @var bool|null $conductedActivitiesCollegeScope */

if (!isset($conductedActivitiesPeriod, $conductedActivitiesSlug) || $conductedActivitiesPeriod === '') {
    return;
}

$conductedActivitiesCount = (int) ($conductedActivitiesCount ?? 0);
$periodText = \App\Support\QuarterlyReporting::periodLabel($conductedActivitiesPeriod);
$noticeType = (string) \App\Support\ExtensionActivityTypes::fromSlug($conductedActivitiesSlug);
$noticeOffice = \App\Support\ExtensionActivityTypes::officeOf($noticeType);
$noticeTerms = \App\Support\ExtensionActivityTypes::officeTerms($noticeOffice);
$logUrl = base_url(\App\Support\ExtensionActivityTypes::basePath($noticeOffice) . '?type=' . rawurlencode($conductedActivitiesSlug) . '&period=' . rawurlencode($conductedActivitiesPeriod));
$whose = !empty($conductedActivitiesCollegeScope) ? 'your college\'s' : 'your';
// Input-name prefixes of the generated rows, e.g. "entries[need_based][" or "training_entries[".
$rowPrefixes = [];
foreach ((\App\Support\ExtensionActivityTypes::get($noticeType)['sections'] ?? []) as $sectionDef) {
    $path = $sectionDef['path'];
    $rowPrefixes[] = array_shift($path) . implode('', array_map(static fn (string $k): string => '[' . $k . ']', $path)) . '[';
}
?>
<section class="proposal-section">
    <div class="alert <?= $conductedActivitiesCount > 0 ? 'alert-success' : 'alert-warn-box card' ?> conducted-activities-notice" data-conducted-activities>
        <span>
            <?php if ($conductedActivitiesCount > 0): ?>
                The rows below list the <strong><?= $conductedActivitiesCount ?> <?= $conductedActivitiesCount === 1 ? $noticeTerms['entry'] : $noticeTerms['entries'] ?></strong>
                in <?= $whose ?> <?= strtolower($noticeTerms['page']) ?> for <strong><?= htmlspecialchars($periodText) ?></strong>.
                To add or change a row, update the entry under <?= htmlspecialchars($noticeTerms['page']) ?>; the report follows it when saved.
            <?php else: ?>
                No <?= strtolower($noticeTerms['page']) ?> in <?= $whose ?> records for <strong><?= htmlspecialchars($periodText) ?></strong>.
                Add them under <?= htmlspecialchars($noticeTerms['page']) ?> and they will be listed in this report.
            <?php endif; ?>
        </span>
        <a class="btn btn-sm btn-outline" href="<?= $logUrl ?>">Open <?= htmlspecialchars($noticeTerms['page']) ?></a>
    </div>
</section>
<style>
    .generated-report-field,
    .generated-report-field:focus { background: transparent !important; border-color: transparent !important; box-shadow: none !important; resize: none; color: inherit; opacity: 1; cursor: default; }
    select.generated-report-field { appearance: none; -webkit-appearance: none; }
    textarea.generated-report-field { overflow: hidden; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const notice = document.querySelector('[data-conducted-activities]');
    const form = notice?.closest('form');
    if (!form) {
        return;
    }

    // The rows are generated: show them read-only, drop blank placeholder rows and the row buttons.
    const prefixes = <?= json_encode($rowPrefixes, JSON_UNESCAPED_SLASHES) ?>;
    const fields = [...form.elements].filter((el) => el.name && prefixes.some((p) => el.name.startsWith(p)));
    const tables = new Set();
    fields.forEach((el) => {
        if (el.tagName === 'SELECT' || ['checkbox', 'radio', 'file'].includes(el.type)) {
            el.disabled = true;
        } else {
            el.readOnly = true;
        }
        el.tabIndex = -1;
        el.removeAttribute('placeholder');
        el.classList.add('generated-report-field');
        const table = el.closest('table');
        if (table) {
            tables.add(table);
        }
    });
    new Set(fields.map((el) => el.closest('tr')).filter(Boolean)).forEach((row) => {
        const rowFields = [...row.querySelectorAll('.generated-report-field')];
        if (rowFields.every((el) => String(el.value || '').trim() === '')) {
            row.remove();
        }
    });
    const hide = (button) => { button.hidden = true; button.style.display = 'none'; };
    tables.forEach((table) => table.querySelectorAll('button').forEach(hide));
    form.querySelectorAll('button').forEach((button) => {
        if (/^\s*Add Row\b/i.test(button.textContent) || /add-row|remove-row/.test(button.className)) {
            hide(button);
        }
    });
    form.querySelectorAll('.trainings-conducted-scroll-hint').forEach((hint) => {
        hint.textContent = 'Scroll horizontally to view all columns.';
    });
    form.dispatchEvent(new Event('input', { bubbles: true }));

    const select = form.querySelector('select[name="reporting_period"]');
    if (!select) {
        return;
    }
    select.addEventListener('change', () => {
        if (select.value === '') {
            return;
        }
        const url = new URL(window.location.href);
        url.searchParams.set('period', select.value);
        window.location.assign(url.toString());
    });
});
</script>
