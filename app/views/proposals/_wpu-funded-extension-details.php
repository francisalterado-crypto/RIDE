<?php
/**
 * Read-only view of a saved ESO-03 Extension Project Proposal, laid out like the web form (cards and fields).
 * The office paper layout is used only for printing.
 *
 * @var array $proposal
 */
$summaryData = json_decode((string) ($proposal['summary'] ?? ''), true);
$summaryData = is_array($summaryData) ? $summaryData : [];

$text = static function (string $key, string $legacyKey = '') use ($summaryData): string {
    foreach (array_filter([$key, $legacyKey]) as $candidate) {
        $value = $summaryData[$candidate] ?? '';
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }

    return '';
};
$rows = static function (string $key) use ($summaryData): array {
    $list = is_array($summaryData[$key] ?? null) ? $summaryData[$key] : [];

    return array_values(array_filter($list, static function ($row): bool {
        if (!is_array($row)) {
            return false;
        }
        foreach ($row as $value) {
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }));
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
$table = static function (array $columns, array $list) use ($value): void {
    if ($list === []) {
        echo '<p class="wf-view-empty">None provided.</p>';
        return;
    }
    ?>
    <div class="data-table-wrap">
        <table class="data-table wf-view-table">
            <thead><tr><?php foreach ($columns as $label): ?><th><?= htmlspecialchars($label) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr><?php foreach (array_keys($columns) as $key): ?><td><?= $value(trim((string) ($row[$key] ?? ''))) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
};
$amount = static function (string $raw): float {
    $cleaned = preg_replace('/[^\d.-]/', '', str_replace(',', '', $raw)) ?? '';

    return is_numeric($cleaned) ? (float) $cleaned : 0.0;
};

$legacyLeader = trim(implode(' ', array_filter([
    $text('program_leader_first_name', 'applicant_first_name'),
    $text('program_leader_middle_name', 'applicant_middle_name'),
    $text('program_leader_last_name', 'applicant_last_name'),
])));
$members = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $text('members_trainers')) ?: [])));
$male = $text('male_beneficiaries');
$female = $text('female_beneficiaries');
$totalBeneficiaries = (is_numeric($male) || is_numeric($female)) ? (int) $male + (int) $female : null;
$formatDate = static function (string $date): string {
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $parsed instanceof DateTimeImmutable ? $parsed->format('F j, Y') : $date;
};
$start = $text('duration_start_date');
$end = $text('duration_end_date');
$duration = $start !== '' || $end !== ''
    ? implode(' to ', array_map($formatDate, array_filter([$start, $end])))
    : $text('duration_inclusive_dates');
$signoff = eso_extension_signoff($proposal, $summaryData);
$cardNo = 0;
$card = static function (string $title, string $subtitle = '') use (&$cardNo): void {
    ?>
    <header class="webform-card-head">
        <span class="webform-card-num"><?= ++$cardNo ?></span>
        <div>
            <h2><?= htmlspecialchars($title) ?></h2>
            <?php if ($subtitle !== ''): ?><p><?= htmlspecialchars($subtitle) ?></p><?php endif; ?>
        </div>
    </header>
    <?php
};
?>
<div class="webform wf-view">
    <section class="webform-card">
        <?php $card('Identifying Information'); ?>
        <div class="webform-grid">
            <?php $field('Title', (string) ($proposal['title'] ?? ''), true); ?>
            <?php $field('College Extension Program', $text('college_extension_program'), true); ?>
            <?php $field('Project Team Leader', $text('project_team_leader') !== '' ? $text('project_team_leader') : $legacyLeader); ?>
            <?php $field('Implementing College / Department', $text('implementing_college_department', 'program_leader_college') !== '' ? $text('implementing_college_department', 'program_leader_college') : (string) ($proposal['college_name'] ?? '')); ?>
            <div class="wf-view-field webform-field--full">
                <span class="wf-view-label">Members / Trainers</span>
                <div class="wf-view-value">
                    <?php if ($members === []): ?>
                        <span class="wf-view-empty">Not provided</span>
                    <?php else: ?>
                        <span class="wf-view-chips"><?php foreach ($members as $member): ?><span class="chip-item"><?= htmlspecialchars($member) ?></span><?php endforeach; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php $field('Collaborating Organizations', $text('collaborating_organizations'), true); ?>
            <?php $field('Beneficiaries', $text('beneficiaries'), true); ?>
            <?php $field('Male Beneficiaries', $male); ?>
            <?php $field('Female Beneficiaries', $female); ?>
            <?php if ($totalBeneficiaries !== null): ?>
                <?php $field('Total Beneficiaries', (string) $totalBeneficiaries); ?>
            <?php endif; ?>
            <?php $field('Duration / Inclusive Dates', $duration); ?>
            <?php $field('Location', $text('location'), true); ?>
            <?php $field('Budget', $text('budget', 'program_summary_total_amount')); ?>
            <?php $field('Source of Fund', $text('source_of_fund', 'program_summary_total_source') !== '' ? $text('source_of_fund', 'program_summary_total_source') : (string) ($proposal['funding_source'] ?? '')); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('Rationale'); ?>
        <div class="webform-grid"><?php $field('Rationale', $text('rationale', 'introduction'), true); ?></div>
    </section>

    <section class="webform-card">
        <?php $card('Objectives'); ?>
        <div class="webform-grid">
            <?php $field('General Objective', $text('general_objective', 'objectives'), true); ?>
            <?php $field('Specific Objectives', $text('specific_objectives'), true); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('Project Components'); ?>
        <div class="webform-grid">
            <?php $field('A. Community Analysis', $text('community_analysis'), true); ?>
            <?php $field('B. Problem Analysis', $text('problem_analysis'), true); ?>
            <?php $field('C. Description of the Target Group', $text('target_group_description'), true); ?>
        </div>
        <h3 class="wf-view-subhead">D. Partnership</h3>
        <?php $table(['partner' => 'Partner', 'task_description' => 'Task Description', 'area_of_responsibility' => 'Area of Responsibility', 'resource_sharing' => 'Resource Sharing'], $rows('partnerships')); ?>
        <h3 class="wf-view-subhead">E. Duties and Responsibilities of the Project Team</h3>
        <?php $table(['member' => 'Member', 'college' => 'College', 'department' => 'Department / Program', 'role' => 'Role', 'task_description' => 'Task Description', 'responsibility' => 'Responsibility'], $rows('team_duties')); ?>
    </section>

    <section class="webform-card">
        <?php $card('Logical Framework'); ?>
        <?php $table(['inputs' => 'Inputs', 'activities' => 'Activities', 'outputs' => 'Outputs', 'effects' => 'Effects', 'outcomes' => 'Outcomes', 'impact' => 'Impact', 'sdg' => 'Sustainable Development Goal'], $rows('logical_framework')); ?>
    </section>

    <section class="webform-card">
        <?php $card('Methodology & Activities'); ?>
        <div class="webform-grid">
            <?php $field('Methodology', $text('methodology'), true); ?>
            <?php $field('Activities', $text('activities_narrative', 'expected_outputs') !== '' ? $text('activities_narrative', 'expected_outputs') : $text('expected_outcomes'), true); ?>
        </div>
    </section>

    <section class="webform-card">
        <?php $card('Work & Financial Plan'); ?>
        <?php $table(['activities' => 'Activity', 'objective' => 'Objective', 'indicator' => 'Indicator', 'strategies' => 'Strategies', 'time_frame' => 'Time Frame', 'responsible_persons' => 'Responsible Persons', 'budget_needed' => 'Budget Needed', 'outputs' => 'Expected Output/s'], $rows('work_plan')); ?>
    </section>

    <section class="webform-card">
        <?php $card('Budget'); ?>
        <h3 class="wf-view-subhead">A. General Breakdown</h3>
        <?php $table(['item' => 'Item', 'particulars' => 'Particulars', 'amount' => 'Amount'], $rows('budget_general')); ?>
        <?php foreach ((is_array($summaryData['budget_line_groups'] ?? null) ? $summaryData['budget_line_groups'] : []) as $groupIndex => $group): ?>
            <?php
            $items = is_array($group) ? array_values(array_filter(is_array($group['items'] ?? null) ? $group['items'] : [], static fn ($item): bool => is_array($item) && trim(implode('', array_map('strval', array_diff_key($item, ['item_no' => ''])))) !== '')) : [];
            if ($items === []) {
                continue;
            }
            $subtotal = array_sum(array_map(static fn (array $item): float => $amount((string) ($item['total_cost'] ?? '')), $items));
            $groupLabel = trim((string) ($group['label'] ?? '')) !== '' ? (string) $group['label'] : 'Line-Item Budget ' . ((int) $groupIndex + 1);
            ?>
            <h3 class="wf-view-subhead">B.<?= (int) $groupIndex + 1 ?> Specific Breakdown: <?= htmlspecialchars($groupLabel) ?></h3>
            <?php $table(['item_no' => 'No.', 'particulars' => 'Particulars', 'qty' => 'Qty', 'unit' => 'Unit', 'cost_unit' => 'Cost / Unit', 'total_cost' => 'Total Cost'], $items); ?>
            <p class="wf-view-total">Sub-total: <strong><?= htmlspecialchars(number_format($subtotal, 2)) ?></strong></p>
        <?php endforeach; ?>
    </section>

    <section class="webform-card">
        <?php $card('References'); ?>
        <div class="webform-grid"><?php $field('References', $text('references'), true); ?></div>
    </section>

    <section class="webform-card">
        <?php $card('Sign-off', 'Filled in from the submission and the approval record.'); ?>
        <div class="webform-grid webform-grid--3">
            <?php foreach (['proponent' => 'Submitted by (Proponent)', 'dean' => 'Endorsed by (Dean)', 'received' => 'Received by (Extension Admin Staff)'] as $key => $label): ?>
                <?php $field($label, trim($signoff[$key]['name'] . ($signoff[$key]['date'] !== '' ? "\n" . $signoff[$key]['date'] : ''))); ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>
