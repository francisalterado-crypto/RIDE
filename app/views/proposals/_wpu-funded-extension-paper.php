<?php
/** Shared paper (print) layout for the ESO-03 Extension Program/Project Proposal. */
/** @var array $proposal */
/** @var string $stepLabel */

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$summaryField = static function (string $key, string $legacyKey = '') use ($summaryData): string {
    $value = $summaryData[$key] ?? '';
    if (is_string($value) && trim($value) !== '') {
        return $value;
    }

    if ($legacyKey !== '') {
        $legacy = $summaryData[$legacyKey] ?? '';
        return is_string($legacy) ? $legacy : '';
    }

    return '';
};

$display = static function (string $value): string {
    $trimmed = trim($value);
    return $trimmed !== '' ? nl2br(htmlspecialchars($trimmed)) : '—';
};

$legacyLeaderName = trim(implode(' ', array_filter([
    $summaryField('program_leader_first_name', 'applicant_first_name'),
    $summaryField('program_leader_middle_name', 'applicant_middle_name'),
    $summaryField('program_leader_last_name', 'applicant_last_name'),
])));

$rowsOrEmpty = static function (mixed $rows): array {
    return is_array($rows) ? array_values(array_filter($rows, static fn ($row): bool => is_array($row))) : [];
};

$rowHasContent = static function (array $row): bool {
    foreach ($row as $value) {
        if (is_string($value) && trim($value) !== '') {
            return true;
        }
    }

    return false;
};

$partnerships = array_values(array_filter($rowsOrEmpty($summaryData['partnerships'] ?? []), $rowHasContent));
$teamDuties = array_values(array_filter($rowsOrEmpty($summaryData['team_duties'] ?? []), $rowHasContent));
$logicalFramework = array_values(array_filter($rowsOrEmpty($summaryData['logical_framework'] ?? []), $rowHasContent));
$workPlan = array_values(array_filter($rowsOrEmpty($summaryData['work_plan'] ?? []), $rowHasContent));
$budgetGeneral = array_values(array_filter($rowsOrEmpty($summaryData['budget_general'] ?? []), $rowHasContent));
$budgetLineGroups = $rowsOrEmpty($summaryData['budget_line_groups'] ?? []);
?>
    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Extension Services</p>
        <h2 class="completed-researches-title">EXTENSION PROGRAM/PROJECT PROPOSAL</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-03 Rev.00 (08.15.25)</p>
    </header>

    <section class="proposal-section">
        <table class="proposal-table">
            <tr>
                <th>Project Code</th>
                <td><?= htmlspecialchars($proposal['project_code'] ?: 'Draft') ?></td>
                <th>Status</th>
                <td>
                    <span class="badge badge-<?= htmlspecialchars($proposal['status']) ?>"><?= htmlspecialchars($proposal['status']) ?></span>
                    <?php if ($proposal['current_step']): ?>
                        <div class="proposal-table-meta">Step: <?= htmlspecialchars($stepLabel) ?></div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">I. Identifying Information</h2>
        <table class="proposal-table eso-identifying-table">
            <tr>
                <th>Title</th>
                <td colspan="3"><?= $display($proposal['title'] ?? '') ?></td>
            </tr>
            <tr>
                <th>College Extension Program</th>
                <td colspan="3"><?= $display($summaryField('college_extension_program')) ?></td>
            </tr>
            <tr>
                <th>Project Team Leader</th>
                <td colspan="3"><?= $display($summaryField('project_team_leader') !== '' ? $summaryField('project_team_leader') : $legacyLeaderName) ?></td>
            </tr>
            <tr>
                <th>Members/Trainers</th>
                <td colspan="3"><?= $display($summaryField('members_trainers')) ?></td>
            </tr>
            <tr>
                <th>Implementing College / Department</th>
                <td colspan="3"><?= $display($summaryField('implementing_college_department', 'program_leader_college') !== '' ? $summaryField('implementing_college_department', 'program_leader_college') : (string) ($proposal['college_name'] ?? '')) ?></td>
            </tr>
            <tr>
                <th>Collaborating Organizations</th>
                <td colspan="3"><?= $display($summaryField('collaborating_organizations')) ?></td>
            </tr>
            <tr>
                <th>Beneficiaries</th>
                <td colspan="3"><?= $display($summaryField('beneficiaries')) ?></td>
            </tr>
            <tr>
                <th>Number of Male Beneficiaries</th>
                <td><?= $display($summaryField('male_beneficiaries')) ?></td>
                <th>Number of Female Beneficiaries</th>
                <td><?= $display($summaryField('female_beneficiaries')) ?></td>
            </tr>
            <tr>
                <th>Duration / Inclusive Dates</th>
                <td colspan="3"><?= $display($summaryField('duration_inclusive_dates')) ?></td>
            </tr>
            <tr>
                <th>Location</th>
                <td colspan="3"><?= $display($summaryField('location')) ?></td>
            </tr>
            <tr>
                <th>Budget</th>
                <td><?= $display($summaryField('budget', 'program_summary_total_amount')) ?></td>
                <th>Source of Fund</th>
                <td><?= $display($summaryField('source_of_fund', 'program_summary_total_source') !== '' ? $summaryField('source_of_fund', 'program_summary_total_source') : (string) ($proposal['funding_source'] ?? '')) ?></td>
            </tr>
        </table>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">II. Rationale</h2>
        <p><?= $display($summaryField('rationale', 'introduction')) ?></p>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">III. Objectives</h2>
        <h3 class="proposal-subtitle">A. General objective</h3>
        <p><?= $display($summaryField('general_objective', 'objectives')) ?></p>
        <h3 class="proposal-subtitle">B. Specific objectives</h3>
        <p><?= $display($summaryField('specific_objectives')) ?></p>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">IV. Project Components/Descriptions</h2>
        <h3 class="proposal-subtitle">A. Community Analysis</h3>
        <p><?= $display($summaryField('community_analysis')) ?></p>
        <h3 class="proposal-subtitle">B. Problem Analysis</h3>
        <p><?= $display($summaryField('problem_analysis')) ?></p>
        <h3 class="proposal-subtitle">C. Description of the Target Group</h3>
        <p><?= $display($summaryField('target_group_description')) ?></p>

        <h3 class="proposal-subtitle">D. Partnership</h3>
        <?php if ($partnerships === []): ?>
            <p>—</p>
        <?php else: ?>
            <div class="proposal-table-wrap">
                <table class="proposal-table">
                    <thead>
                        <tr>
                            <th>Partner</th>
                            <th>Task Description</th>
                            <th>Area of Responsibility</th>
                            <th>Resource Sharing</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partnerships as $row): ?>
                            <tr>
                                <td><?= $display((string) ($row['partner'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['task_description'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['area_of_responsibility'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['resource_sharing'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h3 class="proposal-subtitle">E. Duties and Responsibilities of the Project Team Members</h3>
        <?php if ($teamDuties === []): ?>
            <p>—</p>
        <?php else: ?>
            <div class="proposal-table-wrap trainings-conducted-table-wrap">
                <table class="proposal-table trainings-conducted-table eso-team-duties-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>College</th>
                            <th>Department/Program</th>
                            <th>Role</th>
                            <th>Task Description</th>
                            <th>Responsibility</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamDuties as $row): ?>
                            <tr>
                                <td><?= $display((string) ($row['member'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['college'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['department'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['role'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['task_description'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['responsibility'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">V. Logical Framework</h2>
        <?php if ($logicalFramework === []): ?>
            <p>—</p>
        <?php else: ?>
            <div class="proposal-table-wrap trainings-conducted-table-wrap">
                <table class="proposal-table trainings-conducted-table eso-framework-table">
                    <thead>
                        <tr>
                            <th>Inputs</th>
                            <th>Activities</th>
                            <th>Outputs</th>
                            <th>Effects</th>
                            <th>Outcomes</th>
                            <th>Impact</th>
                            <th>Sustainable Development Goals</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logicalFramework as $row): ?>
                            <tr>
                                <td><?= $display((string) ($row['inputs'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['activities'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['outputs'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['effects'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['outcomes'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['impact'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['sdg'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">VI. Methodology</h2>
        <p><?= $display($summaryField('methodology')) ?></p>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">VII. Activities</h2>
        <p><?= $display($summaryField('activities_narrative', 'expected_outputs') !== '' ? $summaryField('activities_narrative', 'expected_outputs') : $summaryField('expected_outcomes')) ?></p>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">VIII. Work and Financial Plan</h2>
        <?php if ($workPlan === []): ?>
            <p>—</p>
        <?php else: ?>
            <div class="proposal-table-wrap trainings-conducted-table-wrap">
                <table class="proposal-table trainings-conducted-table eso-workplan-table">
                    <thead>
                        <tr>
                            <th>Activities</th>
                            <th>Objective</th>
                            <th>Indicator</th>
                            <th>Strategies</th>
                            <th>Time Frame</th>
                            <th>Responsible Persons</th>
                            <th>Budget Needed</th>
                            <th>Output/s</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workPlan as $row): ?>
                            <tr>
                                <td><?= $display((string) ($row['activities'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['objective'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['indicator'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['strategies'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['time_frame'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['responsible_persons'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['budget_needed'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['outputs'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">IX. Budgetary Requirements</h2>
        <h3 class="proposal-subtitle">A. General Breakdown of the Budgetary Requirements</h3>
        <?php if ($budgetGeneral === []): ?>
            <p>—</p>
        <?php else: ?>
            <div class="proposal-table-wrap">
                <table class="proposal-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Particulars</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($budgetGeneral as $row): ?>
                            <tr>
                                <td><?= $display((string) ($row['item'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['particulars'] ?? '')) ?></td>
                                <td><?= $display((string) ($row['amount'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php foreach ($budgetLineGroups as $groupIndex => $group): ?>
            <?php
            $items = array_values(array_filter($rowsOrEmpty($group['items'] ?? []), $rowHasContent));
            if ($items === []) {
                continue;
            }
            $groupLabel = trim((string) ($group['label'] ?? '')) !== ''
                ? (string) $group['label']
                : 'Line-Item Budget ' . ($groupIndex + 1);
            $subtotal = 0.0;
            foreach ($items as $item) {
                $cleaned = preg_replace('/[^\d.-]/', '', str_replace(',', '', (string) ($item['total_cost'] ?? ''))) ?? '';
                $subtotal += is_numeric($cleaned) ? (float) $cleaned : 0.0;
            }
            ?>
            <h3 class="proposal-subtitle"><?= (int) $groupIndex + 1 ?>. Specific Breakdown of Budget of <?= htmlspecialchars($groupLabel) ?></h3>
            <div class="proposal-table-wrap">
                <table class="proposal-table">
                    <thead>
                        <tr>
                            <th>Item no.</th>
                            <th>Particulars</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Cost/unit</th>
                            <th>Total cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= $display((string) ($item['item_no'] ?? '')) ?></td>
                                <td><?= $display((string) ($item['particulars'] ?? '')) ?></td>
                                <td><?= $display((string) ($item['qty'] ?? '')) ?></td>
                                <td><?= $display((string) ($item['unit'] ?? '')) ?></td>
                                <td><?= $display((string) ($item['cost_unit'] ?? '')) ?></td>
                                <td><?= $display((string) ($item['total_cost'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <th colspan="5">Sub-Total</th>
                            <td><?= htmlspecialchars(number_format($subtotal, 2, '.', ',')) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">X. References</h2>
        <p><?= $display($summaryField('references')) ?></p>
    </section>

    <section class="proposal-section completed-researches-signoff">
        <div class="eso-signoff-grid">
            <div>
                <p class="completed-researches-signoff-label">Submitted by:</p>
                <p><?= $display($summaryField('proponent_name')) ?></p>
                <p class="completed-researches-signoff-role">Proponent — <?= $display($summaryField('proponent_date')) ?></p>
            </div>
            <div>
                <p class="completed-researches-signoff-label">Endorsed by:</p>
                <p><?= $display($summaryField('dean_name')) ?></p>
                <p class="completed-researches-signoff-role">Dean — <?= $display($summaryField('dean_date')) ?></p>
            </div>
            <div>
                <p class="completed-researches-signoff-label">Received by:</p>
                <p><?= $display($summaryField('extension_admin_name')) ?></p>
                <p class="completed-researches-signoff-role">Extension Admin Staff — <?= $display($summaryField('extension_admin_date')) ?></p>
            </div>
        </div>
    </section>
