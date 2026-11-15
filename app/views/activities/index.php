<?php
/** @var list<array<string, mixed>> $entries */
/** @var string $selectedType */
/** @var string $selectedPeriod */
/** @var array{0: string, 1: string}|null $periodRange */
/** @var bool $isCollegeScope */
/** @var int $currentUserId */
/** @var bool $canGenerateReports */
/** @var string $office 'eso' or 'rdo' */

use App\Support\ExtensionActivityTypes;
use App\Support\QuarterlyReporting;

$types = ExtensionActivityTypes::forOffice($office);
$basePath = ExtensionActivityTypes::basePath($office);
$terms = ExtensionActivityTypes::officeTerms($office);
$isResearch = $office === 'rdo';
$docCounts = \App\Models\ExtensionActivityDocument::filledCategoryCounts(
    array_map(static fn (array $e): int => (int) $e['id'], $entries),
    ExtensionActivityTypes::allRequiredDocumentCategories()
);
$pageHeading = $terms['page'];
if ($isResearch) {
    $pageSubtitle = $isCollegeScope
        ? 'Research accomplishments reported by the researchers of your college, with their documentary evidence. The quarterly reports are generated from these.'
        : 'Report each research accomplishment with its documentary evidence. Your R&D Coordinator generates the quarterly reports from these.';
} else {
    $pageSubtitle = $isCollegeScope
        ? 'Extension activities conducted by everyone in your college. The quarterly reports are generated from these.'
        : 'Record each extension activity you conduct. The quarterly reports are generated from these.';
}

$selectedSlug = $selectedType !== '' ? (string) $types[$selectedType]['slug'] : '';
$periodOptions = QuarterlyReporting::periodOptions(1, 1);
$query = static function (string $slug, string $period): string {
    $params = array_filter(['type' => $slug, 'period' => $period], static fn (string $v): bool => $v !== '');

    return $params !== [] ? '?' . http_build_query($params) : '';
};
$formatDate = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '-';
    }
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

    return $parsed instanceof DateTimeImmutable ? $parsed->format('M j, Y') : $date;
};
?>

<div class="card activity-list-toolbar">
    <div class="activity-list-add">
        <strong>Add <?= $isResearch ? 'an accomplishment' : 'an activity' ?>:</strong>
        <?php foreach ($types as $definition): ?>
            <a class="btn btn-sm<?= $selectedSlug === $definition['slug'] ? '' : ' btn-outline' ?>" href="<?= base_url($basePath . '/new/' . $definition['slug']) ?>">
                <i class="fas fa-plus" aria-hidden="true"></i> <?= htmlspecialchars((string) $definition['label']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="get" action="<?= base_url($basePath) ?>" class="inline-form activity-list-filter">
        <?php if ($selectedSlug !== ''): ?>
            <input type="hidden" name="type" value="<?= htmlspecialchars($selectedSlug) ?>">
        <?php endif; ?>
        <div>
            <label for="activity-period">Reporting period</label>
            <select id="activity-period" name="period" onchange="this.form.submit()">
                <option value="all"<?= $selectedPeriod === 'all' ? ' selected' : '' ?>>All dates</option>
                <?php foreach ($periodOptions as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"<?= $key === $selectedPeriod ? ' selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <noscript><button type="submit" class="btn btn-sm">Filter</button></noscript>
    </form>
    <?php if ($periodRange !== null): ?>
        <p class="muted activity-list-range">
            Covers <?= htmlspecialchars($formatDate($periodRange[0])) ?> to <?= htmlspecialchars($formatDate($periodRange[1])) ?>.
        </p>
    <?php endif; ?>
</div>

<nav class="tabs" aria-label="<?= $isResearch ? 'Accomplishment category' : 'Activity type' ?>">
    <a class="tab<?= $selectedType === '' ? ' active' : '' ?>" href="<?= base_url($basePath . $query('', $selectedPeriod)) ?>">All</a>
    <?php foreach ($types as $definition): ?>
        <a class="tab<?= $selectedSlug === $definition['slug'] ? ' active' : '' ?>" href="<?= base_url($basePath . $query((string) $definition['slug'], $selectedPeriod)) ?>">
            <?= htmlspecialchars((string) $definition['label']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php
$deadlineFormType = $selectedType !== '' ? $selectedType : (string) array_key_first($types);
$deadlinePeriod = $selectedPeriod !== 'all' ? $selectedPeriod : '';
require APP_PATH . '/views/partials/quarterly-deadline-notice.php';
?>

<?php if ($canGenerateReports && $selectedType !== ''): ?>
    <p class="muted activity-report-link">Reports are generated from <a href="<?= base_url('quarterly-reports/' . $selectedSlug . ($selectedPeriod !== 'all' ? '?period=' . rawurlencode($selectedPeriod) : '')) ?>">Quarterly Report: <?= htmlspecialchars((string) $types[$selectedType]['label']) ?></a>.</p>
<?php endif; ?>

<div class="card list-card">
    <?php if ($entries === []): ?>
        <div class="empty-state">
            <i class="fas <?= $isResearch ? 'fa-award' : 'fa-clipboard-list' ?> empty-icon" aria-hidden="true"></i>
            <p>No <?= $isResearch ? 'research accomplishments' : 'conducted activities' ?><?= $periodRange !== null ? ' for this reporting period' : '' ?> yet. Use the buttons above to add one.</p>
        </div>
    <?php else: ?>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th><?= $isResearch ? 'Accomplishment' : 'Activity' ?></th>
                        <th><?= $isResearch ? 'Category' : 'Type' ?></th>
                        <?php if ($isCollegeScope): ?><th><?= htmlspecialchars($terms['verb']) ?></th><?php endif; ?>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry): ?>
                    <?php
                    $definition = $types[(string) $entry['activity_type']] ?? null;
                    if ($definition === null) {
                        continue;
                    }
                    $sectionLabel = (string) ($definition['sections'][(string) $entry['section']]['label'] ?? '');
                    $isOwner = (int) $entry['user_id'] === $currentUserId;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($formatDate($entry['activity_date'] ?? null)) ?></td>
                        <td><a href="<?= base_url($basePath . '/' . (int) $entry['id']) ?>"><strong><?= htmlspecialchars((string) $entry['title'] !== '' ? (string) $entry['title'] : '(untitled)') ?></strong></a></td>
                        <td>
                            <?= htmlspecialchars((string) $definition['label']) ?>
                            <?php if ($sectionLabel !== ''): ?>
                                <div class="muted"><?= htmlspecialchars($sectionLabel) ?></div>
                            <?php endif; ?>
                            <?php $docTotal = count(ExtensionActivityTypes::requiredDocumentCategories((string) $entry['activity_type'])); ?>
                            <?php if ($docTotal > 0): ?>
                                <?php $docFilled = $docCounts[(int) $entry['id']] ?? 0; ?>
                                <span class="badge<?= $docFilled >= $docTotal ? ' badge-approved' : ' badge-draft' ?>">
                                    <?= htmlspecialchars(ExtensionActivityTypes::documentsName((string) $entry['activity_type'])[0]) ?>
                                    <?= $docTotal > 1 ? $docFilled . '/' . $docTotal : ($docFilled > 0 ? 'done' : 'missing') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php if ($isCollegeScope): ?>
                            <td><?= htmlspecialchars(trim((string) $entry['first_name'] . ' ' . (string) $entry['last_name'])) ?></td>
                        <?php endif; ?>
                        <td class="activity-list-actions">
                            <?php if ($isOwner): ?>
                                <a class="btn btn-sm btn-outline" href="<?= base_url($basePath . '/' . (int) $entry['id'] . '/edit') ?>">Edit</a>
                                <form method="post" action="<?= base_url($basePath . '/' . (int) $entry['id'] . '/delete') ?>" class="inline-action" onsubmit="return confirm('Delete this <?= $terms['entry'] ?>?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
