<?php
/**
 * Web form of every quarterly report (5 ESO, 10 RDO). The rows come from Conducted Activities / Research
 * Accomplishments and are rebuilt on save; only the period and the narrative are typed here. Print Preview
 * (the filled office Excel/Word form) is on the saved report's page.
 */
/** @var string $reportType */
/** @var array|null $proposal */
/** @var string $collegeName */
/** @var list<array<string, mixed>> $colleges */
/** @var string|null $prefillSummary */
use App\Support\ExtensionActivityTypes;

$definition = ExtensionActivityTypes::get($reportType) ?? [];
$slug = (string) ($definition['slug'] ?? '');
$office = ExtensionActivityTypes::officeOf($reportType);
$terms = ExtensionActivityTypes::officeTerms($office);
$isEdit = is_array($proposal ?? null);
$summaryData = json_decode((string) ($isEdit ? ($proposal['summary'] ?? '') : ($prefillSummary ?? '')), true);
$summaryData = is_array($summaryData) ? $summaryData : ['form_type' => $reportType];
$reportLabel = proposal_form_label(['summary' => json_encode(['form_type' => $reportType])]);
$formCode = (string) ($definition['code'] ?? '');
$collegeDisplay = trim((string) ($collegeName ?? ''));
$hasNarrative = in_array($reportType, ['trainings_conducted', 'technical_advisory', 'outreach_activities', 'technology_adoption'], true);
$narrative = [
    'challenges' => ['Challenges', 'Problems encountered that contributed to low accomplishment.'],
    'best_practices' => ['Best practices', 'Factors that contributed to outstanding accomplishment.'],
    'lessons_learned' => ['Lessons learned and recommendations', ''],
];
$action = $isEdit ? base_url('proposals/' . (int) $proposal['id'] . '/' . $slug) : base_url('proposals/' . $slug);
$pageTitle = ($isEdit ? 'Edit ' : 'New ') . $reportLabel . ' | RIDE Hub';
$pageHeading = $reportLabel;
$pageSubtitle = 'Quarterly report' . ($formCode !== '' ? ' (' . $formCode . ')' : '') . '. The rows come from your ' . strtolower($terms['page']) . '.';
$cardNo = 0;
?>
<div class="page-actions-bar">
    <a class="btn btn-outline" href="<?= base_url('quarterly-reports/' . $slug) ?>">Back to <?= htmlspecialchars($reportLabel) ?> Reports</a>
    <a class="btn btn-outline" href="<?= base_url(ExtensionActivityTypes::basePath($office) . '?type=' . rawurlencode($slug)) ?>">Open <?= htmlspecialchars($terms['page']) ?></a>
</div>

<form class="webform quarterly-report-form" method="post" action="<?= $action ?>" data-capitalize="off">
    <?= csrf_field() ?>

    <?php
    $deadlineFormType = $reportType;
    $deadlinePeriod = (string) ($summaryData['reporting_period'] ?? ($conductedActivitiesPeriod ?? ''));
    require APP_PATH . '/views/partials/quarterly-deadline-notice.php';
    $blankFormType = $reportType;
    require APP_PATH . '/views/partials/blank-form-link.php';
    ?>

    <section class="webform-card">
        <header class="webform-card-head">
            <span class="webform-card-num"><?= ++$cardNo ?></span>
            <div>
                <h2>Report details</h2>
                <p>Select the reporting period. Changing the period reloads the entries recorded for it.</p>
            </div>
        </header>
        <div class="webform-grid">
            <?php $quarterlyFormType = $reportType; require APP_PATH . '/views/partials/quarterly-period-field.php'; ?>
            <div class="webform-field">
                <label for="report-college">College</label>
                <?php if ($collegeDisplay !== ''): ?>
                    <input type="hidden" name="college_name" value="<?= htmlspecialchars($collegeDisplay) ?>">
                    <input id="report-college" value="<?= htmlspecialchars($collegeDisplay) ?>" readonly>
                <?php else: ?>
                    <select id="report-college" name="college_id" required data-college-select>
                        <option value="">Select college</option>
                        <?php foreach ($colleges as $college): ?>
                            <option value="<?= (int) $college['id'] ?>" data-name="<?= htmlspecialchars((string) $college['name']) ?>"><?= htmlspecialchars((string) $college['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="college_name" value="" data-college-name>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="webform-card">
        <header class="webform-card-head">
            <span class="webform-card-num"><?= ++$cardNo ?></span>
            <div>
                <h2><?= htmlspecialchars(ucfirst($terms['entries'])) ?></h2>
                <p>Listed from <?= htmlspecialchars(strtolower($terms['page'])) ?> for the selected period. To add or change a row, update the entry there.</p>
            </div>
        </header>
        <?php
        $countText = (int) ($conductedActivitiesCount ?? 0);
        $periodText = \App\Support\QuarterlyReporting::periodLabel((string) ($summaryData['reporting_period'] ?? ($conductedActivitiesPeriod ?? '')));
        ?>
        <p class="report-rows-summary">
            <strong><?= $countText ?></strong> <?= $countText === 1 ? htmlspecialchars($terms['entry']) : htmlspecialchars($terms['entries']) ?><?= $periodText !== '' ? ' for ' . htmlspecialchars($periodText) : '' ?>.
            <a href="<?= base_url(ExtensionActivityTypes::basePath($office) . '?type=' . rawurlencode($slug) . '&period=' . rawurlencode((string) ($summaryData['reporting_period'] ?? ''))) ?>">View in <?= htmlspecialchars($terms['page']) ?></a>
        </p>
        <?php require APP_PATH . '/views/proposals/_quarterly-report-rows.php'; ?>
    </section>

    <?php if ($hasNarrative): ?>
        <section class="webform-card">
            <header class="webform-card-head">
                <span class="webform-card-num"><?= ++$cardNo ?></span>
                <div>
                    <h2>Narrative</h2>
                    <p>Optional notes printed below the table.</p>
                </div>
            </header>
            <?php foreach ($narrative as $name => [$label, $hint]): ?>
                <div class="webform-field">
                    <label for="report-<?= $name ?>"><?= htmlspecialchars($label) ?></label>
                    <?php if ($hint !== ''): ?><small class="webform-hint"><?= htmlspecialchars($hint) ?></small><?php endif; ?>
                    <textarea id="report-<?= $name ?>" name="<?= $name ?>" rows="3"><?= htmlspecialchars((string) ($summaryData[$name] ?? '')) ?></textarea>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <div class="webform-actionbar">
        <a class="btn btn-outline" href="<?= $isEdit ? base_url('proposals/' . (int) $proposal['id']) : base_url('quarterly-reports/' . $slug) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $isEdit ? 'Save changes' : 'Save draft' ?></button>
    </div>
</form>

<script>
(() => {
    const form = document.querySelector('.quarterly-report-form');
    const period = form.querySelector('[data-report-period]');
    period?.addEventListener('change', () => {
        if (period.value === '') return;
        const url = new URL(window.location.href);
        url.searchParams.set('period', period.value);
        window.location.assign(url.toString());
    });
    const college = form.querySelector('[data-college-select]');
    college?.addEventListener('change', () => {
        form.querySelector('[data-college-name]').value = college.selectedOptions[0]?.dataset.name || '';
    });
})();
</script>
