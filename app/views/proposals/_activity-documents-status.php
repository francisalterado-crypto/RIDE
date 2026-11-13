<?php
/**
 * Required-document status (ETR, MOA, Accomplishment Report, documentary evidence) of every entry a college
 * recorded in one reporting period (Conducted Activities or Research Accomplishments), shown on the matching
 * quarterly report so the Dean and reviewers can open each entry's files.
 */
/** @var string $statusType activity type, e.g. trainings_conducted */
/** @var int $statusCollegeId */
/** @var string $statusPeriod */

use App\Models\ExtensionActivity;
use App\Models\ExtensionActivityDocument;
use App\Support\ExtensionActivityTypes;

$statusRange = $statusPeriod !== '' ? ExtensionActivityTypes::periodDateRange($statusPeriod) : null;
$statusRequired = array_keys(ExtensionActivityTypes::requiredDocumentCategories($statusType));
$statusTotal = count($statusRequired);
if ($statusCollegeId <= 0 || $statusRange === null || $statusTotal === 0) {
    return;
}

[$statusShort, $statusLong] = ExtensionActivityTypes::documentsName($statusType);
$statusTypeLabel = (string) (ExtensionActivityTypes::get($statusType)['label'] ?? '');
$statusOffice = ExtensionActivityTypes::officeOf($statusType);
$statusTerms = ExtensionActivityTypes::officeTerms($statusOffice);
$statusBase = ExtensionActivityTypes::basePath($statusOffice);
$statusEntryLabel = ucfirst($statusTerms['entry']);
$statusActivities = ExtensionActivity::list(['college_id' => $statusCollegeId], $statusType, $statusRange[0], $statusRange[1]);
$statusCounts = ExtensionActivityDocument::filledCategoryCounts(array_map(static fn (array $a): int => (int) $a['id'], $statusActivities), $statusRequired);
?>
<section class="proposal-section activity-documents-status" data-print="skip">
    <h3 class="proposal-subtitle"><?= htmlspecialchars($statusLong) ?> per <?= $statusEntryLabel ?></h3>
    <?php if ($statusActivities === []): ?>
        <p class="proposal-section-note">No <?= htmlspecialchars(strtolower($statusTypeLabel)) ?> recorded under <?= htmlspecialchars($statusTerms['page']) ?> for this college and reporting period.</p>
    <?php else: ?>
        <p class="proposal-section-note">Each <?= $statusTerms['entry'] ?> needs its own <?= htmlspecialchars($statusLong) ?>. Files are attached to the <?= $statusTerms['entry'] ?> under <?= htmlspecialchars($statusTerms['page']) ?>.</p>
        <ul class="etr-status-list">
            <?php foreach ($statusActivities as $statusActivity): ?>
                <?php $statusFilled = $statusCounts[(int) $statusActivity['id']] ?? 0; ?>
                <li>
                    <a href="<?= base_url($statusBase . '/' . (int) $statusActivity['id']) ?>" target="_blank" rel="noopener">
                        <?= htmlspecialchars((string) $statusActivity['title'] !== '' ? (string) $statusActivity['title'] : '(untitled)') ?>
                    </a>
                    <span class="muted">(<?= htmlspecialchars((string) ($statusActivity['activity_date'] ?? '')) ?>,
                        <?= htmlspecialchars(trim((string) $statusActivity['first_name'] . ' ' . (string) $statusActivity['last_name'])) ?>)</span>
                    <span class="badge<?= $statusFilled >= $statusTotal ? ' badge-approved' : ' badge-draft' ?>"><?= htmlspecialchars($statusShort) ?> <?= $statusTotal > 1 ? $statusFilled . '/' . $statusTotal : ($statusFilled > 0 ? 'done' : 'missing') ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
