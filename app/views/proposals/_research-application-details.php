<?php
/**
 * Read-only view of a saved RDO-20 Research Proposal Application, laid out like the web form (cards and fields).
 * Printing uses the office Word template (Print Preview .docx).
 *
 * @var array $proposal
 */
use App\Support\ResearchFormOptions;

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
$chips = static function (string $label, array $labels, bool $full = true): void {
    ?>
    <div class="wf-view-field<?= $full ? ' webform-field--full' : '' ?>">
        <span class="wf-view-label"><?= htmlspecialchars($label) ?></span>
        <div class="wf-view-value">
            <?php if ($labels === []): ?>
                <span class="wf-view-empty">None selected</span>
            <?php else: ?>
                <span class="wf-view-chips"><?php foreach ($labels as $item): ?><span class="chip-item"><?= htmlspecialchars((string) $item) ?></span><?php endforeach; ?></span>
            <?php endif; ?>
        </div>
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

$proponent = trim(implode(' ', array_filter([
    $text('applicant_title_prefix'), $text('applicant_first_name'), $text('applicant_middle_name'), $text('applicant_last_name'),
])));
if ($proponent === '') {
    $proponent = trim((string) ($proposal['first_name'] ?? '') . ' ' . (string) ($proposal['last_name'] ?? ''));
}
$coauthors = array_values(array_filter(array_map(
    static fn ($row): string => is_array($row) ? trim(implode(' ', array_filter([trim((string) ($row['first_name'] ?? '')), trim((string) ($row['middle_name'] ?? '')), trim((string) ($row['last_name'] ?? ''))]))) : '',
    is_array($summaryData['coauthors'] ?? null) ? $summaryData['coauthors'] : []
)));
$sixPs = [
    'patent_granted' => 'Patent granted',
    'publication' => 'Publication',
    'people_trained' => 'People trained',
    'partnership_developed' => 'Partnership developed',
    'products_processes_developed' => 'Products/Processes developed',
    'policies_formulated' => 'Policies formulated',
];
$budgetItems = array_values(array_filter(
    is_array($summaryData['budget_items'] ?? null) ? $summaryData['budget_items'] : [],
    static fn ($row): bool => is_array($row) && trim(implode('', array_map('strval', $row))) !== ''
));
$plan = array_values(array_filter(
    is_array($summaryData['implementation_plan'] ?? null) ? $summaryData['implementation_plan'] : [],
    static fn ($row): bool => is_array($row) && trim((string) ($row['activity'] ?? '')) !== ''
));
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$riskLabels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
?>
<div class="webform wf-view">
    <section class="webform-card">
        <?php $card('The Proponent'); ?>
        <div class="webform-grid">
            <?php $field('Name', $proponent); ?>
            <?php $field('Position', $text('applicant_position')); ?>
            <?php $field('Email', $text('applicant_email')); ?>
            <?php $field('Contact Number', $text('applicant_contact_number')); ?>
            <?php $field('College / Department', $text('applicant_college_department') !== '' ? $text('applicant_college_department') : (string) ($proposal['college_name'] ?? '')); ?>
            <?php $field('Campus', $text('applicant_campus')); ?>
            <?php $chips('Co-Author(s)', $coauthors); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('Project Information'); ?>
        <div class="webform-grid">
            <?php $field('Title of Proposed Research', (string) ($proposal['title'] ?? ''), true); ?>
            <?php $field('Period Covered', $text('period_covered')); ?>
            <?php $field('Duration in Months', $text('duration_months')); ?>
            <?php $chips('Funding Support', ResearchFormOptions::labels($summaryData['funding_support'] ?? [], ResearchFormOptions::FUNDING_SUPPORT), false); ?>
            <?php $field('Funding Source', (string) ($proposal['funding_source'] ?? '')); ?>
            <?php $field('Risk Level', $riskLabels[(string) ($proposal['risk_level'] ?? '')] ?? (string) ($proposal['risk_level'] ?? '')); ?>
            <?php $field('Ethics Review Required', !empty($proposal['ethics_required']) ? 'Yes' : 'No'); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('Abstract'); ?>
        <div class="webform-grid">
            <?php $field('Abstract', $text('abstract'), true); ?>
            <?php $field('Graphical Abstract', $text('graphical_abstract_link'), true); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('Background'); ?>
        <div class="webform-grid">
            <?php $field('Introduction', $text('introduction'), true); ?>
            <?php $field('Research Gaps', $text('research_gaps'), true); ?>
            <?php $field('Significance of the Study', $text('significance'), true); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('Objectives & Methods'); ?>
        <div class="webform-grid">
            <?php $field('Objectives', $text('objectives'), true); ?>
            <?php $field('Materials and Methods / Description of Project Activities', $text('methods'), true); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('GAD, Ethics & Alignment'); ?>
        <div class="webform-grid">
            <?php $field('Gender and Development', $text('gender_development'), true); ?>
            <?php $field('Ethical Considerations', $text('ethical_considerations'), true); ?>
            <?php $chips('CHED A.C.H.I.E.V.E.', ResearchFormOptions::labels($summaryData['ched_achieve'] ?? [], ResearchFormOptions::CHED_ACHIEVE)); ?>
            <?php $chips('Sustainable Development Goal/s', ResearchFormOptions::labels($summaryData['sdgs'] ?? [], ResearchFormOptions::SDGS)); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('Expected Outcomes'); ?>
        <div class="webform-grid">
            <?php $field('Expected Outcomes', $text('expected_outcomes'), true); ?>
            <?php $chips('Expected 6Ps', ResearchFormOptions::labels($summaryData['six_ps'] ?? [], $sixPs)); ?>
            <?php $field('Sustainability Plan', $text('sustainability_plan'), true); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('Budget'); ?>
        <?php if ($budgetItems === []): ?>
            <p class="wf-view-empty">No budget items provided.</p>
        <?php else: ?>
            <div class="data-table-wrap">
                <table class="data-table wf-view-table">
                    <thead><tr><th>Item</th><th>Amount</th><th>Justification</th></tr></thead>
                    <tbody>
                        <?php foreach ($budgetItems as $item): ?>
                            <tr>
                                <td><?= $value(trim((string) ($item['item'] ?? ''))) ?></td>
                                <td><?= $value(trim((string) ($item['amount'] ?? ''))) ?></td>
                                <td><?= $value(trim((string) ($item['justification'] ?? ''))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <div class="webform-grid wf-view-after-table">
            <?php $field('Total Amount Requested', $text('budget_total') !== '' && is_numeric(str_replace(',', '', $text('budget_total'))) ? number_format((float) str_replace(',', '', $text('budget_total')), 2) : $text('budget_total')); ?>
            <?php $field('Resources Available', $text('resources_available'), true); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('Implementation Plan and Time Frame'); ?>
        <div class="webform-grid">
            <?php $field('From CY', $text('plan_year_from')); ?>
            <?php $field('To CY', $text('plan_year_to')); ?>
        </div>
        <?php if ($plan === []): ?>
            <p class="wf-view-empty">No activities provided.</p>
        <?php else: ?>
            <div class="data-table-wrap wf-view-after-grid">
                <table class="data-table wf-view-table wf-view-gantt">
                    <thead><tr><th>Activity</th><?php foreach ($months as $month): ?><th><?= $month ?></th><?php endforeach; ?></tr></thead>
                    <tbody>
                        <?php foreach ($plan as $row): ?>
                            <?php $picked = array_fill_keys(array_map('strval', is_array($row['months'] ?? null) ? $row['months'] : []), true); ?>
                            <tr>
                                <td><?= $value(trim((string) $row['activity'])) ?></td>
                                <?php foreach ($months as $month): ?>
                                    <td class="wf-view-gantt-cell<?= isset($picked[$month]) ? ' is-on' : '' ?>"><?= isset($picked[$month]) ? '<span class="sr-only">' . $month . '</span>' : '' ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="webform-card">
        <?php $card('Literature Cited'); ?>
        <div class="webform-grid"><?php $field('Literature Cited', $text('literature_cited'), true); ?></div>
    </section>
</div>
