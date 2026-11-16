<?php
/**
 * Read-only view of a saved Progress Report or Terminal Report, laid out like the web form (cards and fields).
 *
 * @var array $proposal
 */
$summaryData = json_decode((string) ($proposal['summary'] ?? ''), true);
$summaryData = is_array($summaryData) ? $summaryData : [];
$text = static function (string $key) use ($summaryData): string {
    $value = $summaryData[$key] ?? '';
    return is_scalar($value) ? trim((string) $value) : '';
};
$value = static function (string $value): string {
    return $value !== '' ? nl2br(htmlspecialchars($value)) : '<span class="wf-view-empty">Not provided</span>';
};
$field = static function (string $label, string $content, bool $full = false) use ($value): void {
    ?>
    <div class="wf-view-field<?= $full ? ' webform-field--full' : '' ?>">
        <span class="wf-view-label"><?= htmlspecialchars($label) ?></span>
        <div class="wf-view-value"><?= $value($content) ?></div>
    </div>
    <?php
};
$cardNo = 0;
$card = static function (string $title) use (&$cardNo): void {
    ?>
    <header class="webform-card-head">
        <span class="webform-card-num"><?= ++$cardNo ?></span>
        <div><h2><?= htmlspecialchars($title) ?></h2></div>
    </header>
    <?php
};

$proponent = trim(implode(' ', array_filter([$text('title_prefix'), $text('first_name'), $text('middle_name'), $text('last_name')])));
$coauthors = array_values(array_filter(array_map(
    static fn ($row): string => is_array($row) ? trim(implode(' ', array_filter([trim((string) ($row['first_name'] ?? '')), trim((string) ($row['middle_name'] ?? '')), trim((string) ($row['last_name'] ?? ''))]))) : '',
    is_array($summaryData['coauthors'] ?? null) ? $summaryData['coauthors'] : []
)));
$entries = array_values(array_filter(
    is_array($summaryData['entries'] ?? null) ? $summaryData['entries'] : [],
    static fn ($row): bool => is_array($row) && trim(implode('', array_map('strval', $row))) !== ''
));
$periodKey = \App\Support\QuarterlyReporting::periodFromSummary($summaryData);
$periodText = $periodKey !== '' ? \App\Support\QuarterlyReporting::periodLabel($periodKey, proposal_form_type($proposal)) : $text('report_as_of');
?>
<div class="webform wf-view">
    <section class="webform-card">
        <?php $card('Report Information'); ?>
        <div class="webform-grid">
            <?php $field('Research Title', $text('research_title') !== '' ? $text('research_title') : (string) ($proposal['title'] ?? ''), true); ?>
            <?php $field('Period Covered', $text('period_covered')); ?>
            <?php $field('Duration in Months', $text('duration_months')); ?>
            <?php $field('Funding/Support', $text('funding_support')); ?>
            <?php $field('Reporting Period', $periodText); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('The Proponent'); ?>
        <div class="webform-grid">
            <?php $field('Name', $proponent); ?>
            <?php $field('College/Department', $text('college_name')); ?>
            <?php $field('Campus', $text('campus')); ?>
            <?php $field('Email', $text('email')); ?>
            <?php $field('Contact Number', $text('contact_number')); ?>
            <?php $field('Google Scholar Account Link', $text('google_scholar_link')); ?>
            <?php $field('ResearchGate Account Link', $text('researchgate_link')); ?>
            <div class="wf-view-field webform-field--full">
                <span class="wf-view-label">Co-Author(s)</span>
                <div class="wf-view-value">
                    <?php if ($coauthors === []): ?>
                        <span class="wf-view-empty">None</span>
                    <?php else: ?>
                        <span class="wf-view-chips"><?php foreach ($coauthors as $name): ?><span class="chip-item"><?= htmlspecialchars($name) ?></span><?php endforeach; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('Project/Study Overview'); ?>
        <div class="webform-grid"><?php $field('Overview', $text('project_overview'), true); ?></div>
    </section>

    <section class="webform-card">
        <?php $card('Work Completed and/or in Progress'); ?>
        <?php if ($entries === []): ?>
            <p class="wf-view-empty">No activities provided.</p>
        <?php else: ?>
            <div class="data-table-wrap">
                <table class="data-table wf-view-table">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Target Schedule</th>
                            <th>Actual Implementation / Completion</th>
                            <th>Problems Encountered / Action Required</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?= $value(trim((string) ($entry['activity'] ?? ''))) ?></td>
                                <td><?= $value(trim((string) ($entry['target_schedule'] ?? ''))) ?></td>
                                <td><?= $value(trim((string) ($entry['actual_period'] ?? ''))) ?></td>
                                <td><?= $value(trim((string) ($entry['problems'] ?? ''))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="webform-card">
        <?php $card('Revised Targets and General Progress'); ?>
        <div class="webform-grid">
            <?php $field('Revised targets if the project is delayed/advanced', $text('revised_targets'), true); ?>
            <?php $field('How the project/study is going in general', $text('general_progress'), true); ?>
        </div>
    </section>
</div>
