<?php
/**
 * Quarterly Report page of one report type: period, entry count and deadline, Generate / Open, and the reports
 * generated so far.
 *
 * @var string $type
 * @var array<string, mixed> $definition
 * @var string $office 'eso' or 'rdo'
 * @var string $period
 * @var array<string, string> $periodOptions
 * @var int $entryCount
 * @var array<string, mixed>|null $existing report already generated for the selected period
 * @var list<array<string, mixed>> $reports
 * @var bool $canPrint
 * @var int $collegeId college the report is for
 * @var array<int, string> $collegeOptions colleges of the RIDE coordinator's clusters
 */
use App\Support\ExtensionActivityTypes;
use App\Support\QuarterlyReporting;

$slug = (string) $definition['slug'];
$label = (string) $definition['label'];
$terms = ExtensionActivityTypes::officeTerms($office);
$entriesPath = ExtensionActivityTypes::basePath($office);
$plainPeriod = static fn (string $key): string => (string) preg_replace('/\s*\(due [^)]*\)$/', '', QuarterlyReporting::periodLabel($key));
$pageHeading = $label;
$pageSubtitle = 'Quarterly report, one per college. Generate it for a reporting period from the ' . strtolower($terms['entries']) . ' recorded under ' . $terms['page'] . '.';
$collegeQuery = $collegeId > 0 ? '&college=' . $collegeId : '';
?>

<div class="card quarterly-report-toolbar">
    <form method="get" action="<?= base_url('quarterly-reports/' . $slug) ?>" class="inline-form">
        <?php if ($collegeOptions !== []): ?>
        <div>
            <label for="qr-college">College</label>
            <select id="qr-college" name="college" onchange="this.form.submit()">
                <?php foreach ($collegeOptions as $optionId => $optionName): ?>
                    <option value="<?= (int) $optionId ?>"<?= (int) $optionId === $collegeId ? ' selected' : '' ?>><?= htmlspecialchars($optionName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php elseif (\App\Support\AccessControl::isRideCoordinator()): ?>
        <p class="muted">No colleges are linked to your cluster yet. Ask the Administrator to assign your cluster.</p>
        <?php endif; ?>
        <div>
            <label for="qr-period">Reporting period</label>
            <select id="qr-period" name="period" onchange="this.form.submit()">
                <?php foreach ($periodOptions as $key => $optionLabel): ?>
                    <option value="<?= htmlspecialchars((string) $key) ?>"<?= $key === $period ? ' selected' : '' ?>><?= htmlspecialchars((string) $optionLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <noscript><button type="submit" class="btn btn-sm">Show</button></noscript>
    </form>
    <?php if ($period !== '' && ($range = ExtensionActivityTypes::periodDateRange($period)) !== null): ?>
        <p class="muted">Covers <?= htmlspecialchars(date('M j, Y', (int) strtotime($range[0]))) ?> to <?= htmlspecialchars(date('M j, Y', (int) strtotime($range[1]))) ?>.</p>
    <?php endif; ?>
</div>

<?php
$deadlineFormType = $type;
$deadlinePeriod = $period;
require APP_PATH . '/views/partials/quarterly-deadline-notice.php';
?>

<div class="card quarterly-report-generate">
    <div>
        <h2><?= htmlspecialchars($plainPeriod($period)) ?> report</h2>
        <p>
            <strong><?= $entryCount ?></strong> <?= $entryCount === 1 ? htmlspecialchars($terms['entry']) : htmlspecialchars($terms['entries']) ?> recorded for this period.
            <a href="<?= base_url($entriesPath . '?type=' . rawurlencode($slug) . '&period=' . rawurlencode($period)) ?>">View in <?= htmlspecialchars($terms['page']) ?></a>
        </p>
        <?php if ($existing !== null): ?>
            <p class="muted">A report for this period has already been generated (<?= htmlspecialchars(status_label((string) $existing['status'])) ?>). Open it to review, update or submit it.</p>
        <?php elseif ($entryCount === 0): ?>
            <p class="muted">There are no entries for this period yet. The report can still be generated, but its table will be empty.</p>
        <?php endif; ?>
    </div>
    <div class="quarterly-report-generate-actions">
        <?php if ($existing !== null): ?>
            <a class="btn btn-accent" href="<?= base_url('proposals/' . (int) $existing['id']) ?>"><i class="fas fa-folder-open" aria-hidden="true"></i> Open Report</a>
        <?php else: ?>
            <a class="btn btn-accent" href="<?= base_url('proposals/create/' . $slug . '?period=' . rawurlencode($period) . $collegeQuery) ?>"><i class="fas fa-file-alt" aria-hidden="true"></i> Generate Report</a>
        <?php endif; ?>
    </div>
</div>

<div class="card list-card">
    <h2>Generated Reports</h2>
    <?php if ($reports === []): ?>
        <div class="empty-state">
            <i class="fas fa-file-alt empty-icon" aria-hidden="true"></i>
            <p>No <?= htmlspecialchars($label) ?> reports have been generated yet.</p>
        </div>
    <?php else: ?>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reporting Period</th>
                        <th>Prepared By</th>
                        <th>Status</th>
                        <th>Step</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr<?= $report['period_key'] === $period ? ' class="is-selected-period"' : '' ?>>
                            <td><a href="<?= base_url('proposals/' . (int) $report['id']) ?>"><strong><?= htmlspecialchars($report['period_key'] !== '' ? $plainPeriod($report['period_key']) : 'No period') ?></strong></a></td>
                            <td><?= htmlspecialchars(trim((string) $report['first_name'] . ' ' . (string) $report['last_name'])) ?></td>
                            <td><span class="badge badge-<?= htmlspecialchars((string) $report['status']) ?>"><?= htmlspecialchars(status_label((string) $report['status'])) ?></span></td>
                            <td class="<?= empty($report['current_step']) ? 'data-table-muted' : '' ?>"><?= htmlspecialchars(!empty($report['current_step']) ? \App\Support\AccessControl::stepLabel((string) $report['current_step']) : '-') ?></td>
                            <td><?= htmlspecialchars(date('M j, Y', (int) strtotime((string) $report['updated_at']))) ?></td>
                            <td class="quarterly-report-row-actions">
                                <a class="btn btn-sm btn-outline" href="<?= base_url('proposals/' . (int) $report['id']) ?>">Open</a>
                                <?php if ($canPrint): ?>
                                    <a class="btn btn-sm btn-outline" href="<?= base_url('proposals/' . (int) $report['id'] . '/print') ?>" title="Download the filled office form"><i class="fas fa-print" aria-hidden="true"></i> Print</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
