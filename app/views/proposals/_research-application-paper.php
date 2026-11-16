<?php
/**
 * Research Proposal Application (WPU-QSF-RIDE-RDO-20) in its paper layout. Used by the proposal page, the
 * print page and the unsaved print preview.
 */
/** @var array $proposal */
/** @var string $stepLabel */
$summaryData = null;
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$summaryField = static function (string $key) use ($summaryData): string {
    if (!is_array($summaryData)) {
        return '';
    }

    $value = $summaryData[$key] ?? '';
    return is_string($value) ? $value : '';
};

$sectionTitles = [
    'abstract' => 'Abstract',
    'introduction' => 'Introduction',
    'research_gaps' => 'Research Gaps',
    'significance' => 'Significance of the Study',
    'objectives' => 'Objectives',
    'methods' => 'Materials and Methods/Description of Project Activities',
    'gender_development' => 'Gender and Development',
    'ethical_considerations' => 'Ethical Considerations',
    'expected_outcomes' => 'Expected Outcomes',
    'sustainability_plan' => 'Sustainability Plan',
    'literature_cited' => 'Literature Cited',
];
$sixPsLabels = [
    'patent_granted' => 'Patent granted',
    'publication' => 'Publication',
    'people_trained' => 'People trained',
    'partnership_developed' => 'Partnership developed',
    'products_processes_developed' => 'Products/Processes developed',
    'policies_formulated' => 'Policies formulated',
];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$coAuthorsRaw = is_array($summaryData) && is_array($summaryData['coauthors'] ?? null) ? $summaryData['coauthors'] : [];
$coAuthors = \App\Support\ProposalCoAuthors::coauthorsForApplicantDisplay(
    $coAuthorsRaw,
    (string) ($proposal['current_step'] ?? '')
);
$showCoAuthorAccountDetails = \App\Support\ProposalCoAuthors::shouldShowCoAuthorAccountDetails();
$applicantName = trim(implode(' ', array_filter([
    $summaryField('applicant_last_name'),
    $summaryField('applicant_first_name'),
    $summaryField('applicant_middle_name'),
])));
if ($applicantName === '') {
    $applicantName = trim((string) ($proposal['first_name'] ?? '') . ' ' . (string) ($proposal['last_name'] ?? ''));
}
?>
    <?php if (($proposal['project_type'] ?? '') !== 'extension'): ?>
    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <h2 class="completed-researches-title">RESEARCH PROPOSAL APPLICATION FORM</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-20 Rev.01 (10.12.26)</p>
    </header>
    <?php endif; ?>
    <section class="proposal-section">
        <h2 class="proposal-section-title">I. The Proponent</h2>
        <table class="proposal-table">
            <tr>
                <th>Name of Applicant</th>
                <td colspan="3"><?= htmlspecialchars($applicantName !== '' ? $applicantName : '-') ?></td>
            </tr>
            <?php if ($summaryField('applicant_title_prefix') !== '' || $summaryField('applicant_sex') !== ''): ?>
                <tr>
                    <th>Title/Prefix</th>
                    <td><?= htmlspecialchars($summaryField('applicant_title_prefix') !== '' ? $summaryField('applicant_title_prefix') : '-') ?></td>
                    <th>Sex</th>
                    <td><?= htmlspecialchars($summaryField('applicant_sex') !== '' ? $summaryField('applicant_sex') : '-') ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($summaryField('applicant_position') !== ''): ?>
                <tr>
                    <th>Position held</th>
                    <td colspan="3"><?= htmlspecialchars($summaryField('applicant_position')) ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($summaryField('applicant_email') !== '' || $summaryField('applicant_contact_number') !== ''): ?>
                <tr>
                    <th>E-mail</th>
                    <td><?= htmlspecialchars($summaryField('applicant_email') !== '' ? $summaryField('applicant_email') : '-') ?></td>
                    <th>Contact Number</th>
                    <td><?= htmlspecialchars($summaryField('applicant_contact_number') !== '' ? $summaryField('applicant_contact_number') : '-') ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($summaryField('applicant_college_department') !== ''): ?>
                <tr>
                    <th>College/Department</th>
                    <td colspan="3"><?= htmlspecialchars($summaryField('applicant_college_department')) ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($summaryField('applicant_program') !== ''): ?>
                <tr>
                    <th>Program</th>
                    <td colspan="3"><?= htmlspecialchars($summaryField('applicant_program')) ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($summaryField('applicant_campus') !== ''): ?>
                <tr>
                    <th>Campus</th>
                    <td colspan="3"><?= htmlspecialchars($summaryField('applicant_campus')) ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($summaryField('applicant_google_scholar_link') !== ''): ?>
                <tr>
                    <th>Google Scholar account link</th>
                    <td colspan="3"><?= htmlspecialchars($summaryField('applicant_google_scholar_link')) ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($summaryField('applicant_researchgate_link') !== ''): ?>
                <tr>
                    <th>ResearchGate account link</th>
                    <td colspan="3"><?= htmlspecialchars($summaryField('applicant_researchgate_link')) ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <?php if ($coAuthors !== []): ?>
            <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
            <div class="proposal-table-wrap trainings-conducted-table-wrap">
                <table class="proposal-table proposal-coauthors-table trainings-conducted-table">
                    <thead>
                        <tr>
                            <?php if ($showCoAuthorAccountDetails): ?>
                                <th>Source</th>
                            <?php endif; ?>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <?php if ($showCoAuthorAccountDetails): ?>
                                <th>Invitation</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coAuthors as $coAuthor): ?>
                            <?php
                            if (!is_array($coAuthor)) {
                                continue;
                            }
                            $hasCoAuthor = trim((string) ($coAuthor['last_name'] ?? '')) !== ''
                                || trim((string) ($coAuthor['first_name'] ?? '')) !== ''
                                || trim((string) ($coAuthor['middle_name'] ?? '')) !== '';
                            if (!$hasCoAuthor) {
                                continue;
                            }
                            $linkedUserId = (int) ($coAuthor['user_id'] ?? 0);
                            $invitationStatus = trim((string) ($coAuthor['invitation_status'] ?? ''));
                            $invitationLabel = $linkedUserId > 0
                                ? \App\Support\ProposalCoAuthors::invitationStatusLabel($invitationStatus)
                                : '-';
                            ?>
                            <tr>
                                <?php if ($showCoAuthorAccountDetails): ?>
                                    <td><?= $linkedUserId > 0 ? 'Faculty account' : 'Manual entry' ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars((string) ($coAuthor['last_name'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($coAuthor['first_name'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($coAuthor['middle_name'] ?? '-')) ?></td>
                                <?php if ($showCoAuthorAccountDetails): ?>
                                    <td><?= htmlspecialchars($invitationLabel !== '' ? $invitationLabel : ($linkedUserId > 0 ? 'Legacy (accepted)' : '-')) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">II. Project Information</h2>
        <table class="proposal-table">
            <tr>
                <th>Title of Proposed Research</th>
                <td colspan="3"><?= htmlspecialchars($proposal['title']) ?></td>
            </tr>
            <tr>
                <th>RIDE Control No.</th>
                <td><?= htmlspecialchars($proposal['project_code'] ?: 'Draft') ?></td>
                <th>Status</th>
                <td>
                    <span class="badge badge-<?= htmlspecialchars($proposal['status']) ?>"><?= htmlspecialchars($proposal['status']) ?></span>
                    <?php if ($proposal['current_step']): ?>
                        <div class="proposal-table-meta">Step: <?= htmlspecialchars($stepLabel) ?></div>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Project Type</th>
                <td><?= htmlspecialchars(ucfirst((string) $proposal['project_type'])) ?></td>
                <th>Funding/Support</th>
                <td>
                    <?php $fundingLabels = \App\Support\ResearchFormOptions::labels($summaryData['funding_support'] ?? [], \App\Support\ResearchFormOptions::FUNDING_SUPPORT); ?>
                    <?= htmlspecialchars($fundingLabels !== [] ? implode(', ', $fundingLabels) : '-') ?>
                    <?php if (trim((string) ($proposal['funding_source'] ?? '')) !== ''): ?>
                        <div class="proposal-table-meta">Source: <?= htmlspecialchars((string) $proposal['funding_source']) ?></div>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>College</th>
                <td colspan="3"><?= htmlspecialchars($proposal['college_name']) ?></td>
            </tr>
            <tr>
                <th>Risk Level</th>
                <td><?= htmlspecialchars(ucfirst((string) $proposal['risk_level'])) ?></td>
                <th>Ethics Review</th>
                <td><?= !empty($proposal['ethics_required']) ? 'Required' : 'Not required' ?></td>
            </tr>
            <?php if ($summaryField('period_covered') !== '' || $summaryField('duration_months') !== ''): ?>
                <tr>
                    <th>Period Covered</th>
                    <td><?= htmlspecialchars($summaryField('period_covered') !== '' ? $summaryField('period_covered') : '-') ?></td>
                    <th>Duration in Months</th>
                    <td><?= htmlspecialchars($summaryField('duration_months') !== '' ? $summaryField('duration_months') : '-') ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </section>

    <?php if (is_array($summaryData)): ?>
        <div class="proposal-detail-stack">
            <?php foreach ($sectionTitles as $key => $label): ?>
                <?php if ($summaryField($key) !== ''): ?>
                    <section class="proposal-section">
                        <h3 class="proposal-subtitle"><?= htmlspecialchars($label) ?></h3>
                        <p><?= nl2br(htmlspecialchars($summaryField($key))) ?></p>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($summaryField('graphical_abstract_link') !== ''): ?>
                <section class="proposal-section">
                    <h3 class="proposal-subtitle">Graphical Abstract</h3>
                    <p><a href="<?= htmlspecialchars($summaryField('graphical_abstract_link')) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($summaryField('graphical_abstract_link')) ?></a></p>
                </section>
            <?php endif; ?>

            <?php foreach (['ched_achieve' => ['CHED A.C.H.I.E.V.E.', \App\Support\ResearchFormOptions::CHED_ACHIEVE], 'sdgs' => ['Sustainable Development Goal/s (SDG/s)', \App\Support\ResearchFormOptions::SDGS]] as $optionKey => [$optionTitle, $optionList]): ?>
                <?php $optionLabels = \App\Support\ResearchFormOptions::labels($summaryData[$optionKey] ?? [], $optionList); ?>
                <?php if ($optionLabels !== []): ?>
                    <section class="proposal-section">
                        <h3 class="proposal-subtitle"><?= htmlspecialchars($optionTitle) ?></h3>
                        <div class="proposal-chip-row">
                            <?php foreach ($optionLabels as $optionLabel): ?>
                                <span class="proposal-chip"><?= htmlspecialchars($optionLabel) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!empty($summaryData['six_ps']) && is_array($summaryData['six_ps'])): ?>
                <section class="proposal-section">
                    <h3 class="proposal-subtitle">Expected 6Ps</h3>
                    <div class="proposal-chip-row">
                        <?php foreach ($summaryData['six_ps'] as $item): ?>
                            <?php if (!is_string($item) || !isset($sixPsLabels[$item])) {
                                continue;
                            } ?>
                            <span class="proposal-chip"><?= htmlspecialchars($sixPsLabels[$item]) ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php
            $budgetItems = is_array($summaryData['budget_items'] ?? null) ? $summaryData['budget_items'] : [];
            $hasBudgetRow = false;
            foreach ($budgetItems as $item) {
                if (is_array($item) && trim((string) ($item['item'] ?? '')) !== '') {
                    $hasBudgetRow = true;
                    break;
                }
            }
            ?>
            <?php if ($summaryField('budget_total') !== '' || $summaryField('resources_available') !== '' || $hasBudgetRow): ?>
                <section class="proposal-section">
                    <h3 class="proposal-subtitle">Budget</h3>
                    <?php if ($summaryField('budget_total') !== ''): ?>
                        <p><strong>Total Amount Requested:</strong> <?= htmlspecialchars($summaryField('budget_total')) ?></p>
                    <?php endif; ?>
                    <?php if ($hasBudgetRow): ?>
                        <table class="proposal-table">
                            <tr>
                                <th>Item</th>
                                <th>Amount</th>
                                <th>Justification</th>
                            </tr>
                            <?php foreach ($budgetItems as $item): ?>
                                <?php
                                if (!is_array($item) || trim((string) ($item['item'] ?? '')) === '') {
                                    continue;
                                }
                                ?>
                                <tr>
                                    <td><?= nl2br(htmlspecialchars((string) ($item['item'] ?? ''))) ?></td>
                                    <td><?= htmlspecialchars((string) ($item['amount'] ?? '')) ?></td>
                                    <td><?= nl2br(htmlspecialchars((string) ($item['justification'] ?? ''))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                    <?php if ($summaryField('resources_available') !== ''): ?>
                        <p><strong>Resources Available:</strong><br><?= nl2br(htmlspecialchars($summaryField('resources_available'))) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php
            $planRows = is_array($summaryData['implementation_plan'] ?? null) ? $summaryData['implementation_plan'] : [];
            $hasPlanRows = false;
            foreach ($planRows as $row) {
                if (is_array($row) && trim((string) ($row['activity'] ?? '')) !== '') {
                    $hasPlanRows = true;
                    break;
                }
            }
            ?>
            <?php if ($hasPlanRows): ?>
                <section class="proposal-section trainings-conducted-section">
                    <h3 class="proposal-subtitle">Implementation Plan and Time Frame</h3>
                    <?php if ($summaryField('plan_year_from') !== ''): ?>
                        <p class="proposal-section-note">CY <?= htmlspecialchars($summaryField('plan_year_from')) ?> to CY <?= htmlspecialchars($summaryField('plan_year_to') !== '' ? $summaryField('plan_year_to') : $summaryField('plan_year_from')) ?></p>
                    <?php endif; ?>
                    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
                    <div class="proposal-table-wrap trainings-conducted-table-wrap">
                        <table class="proposal-table proposal-plan-table trainings-conducted-table">
                            <tr>
                                <th class="proposal-plan-activity">Activity</th>
                                <?php foreach ($months as $month): ?>
                                    <th class="proposal-plan-month"><?= htmlspecialchars($month) ?></th>
                                <?php endforeach; ?>
                            </tr>
                            <?php foreach ($planRows as $row): ?>
                                <?php
                                if (!is_array($row) || trim((string) ($row['activity'] ?? '')) === '') {
                                    continue;
                                }
                                $selectedMonths = array_fill_keys(
                                    array_values(array_filter($row['months'] ?? [], static fn ($value): bool => is_string($value))),
                                    true
                                );
                                ?>
                                <tr>
                                    <td class="proposal-plan-activity-cell"><?= htmlspecialchars((string) ($row['activity'] ?? '')) ?></td>
                                    <?php foreach ($months as $month): ?>
                                        <td class="proposal-plan-check"><?= isset($selectedMonths[$month]) ? 'X' : '' ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

        </div>
    <?php elseif ($proposal['summary']): ?>
        <section class="proposal-section">
            <h3 class="proposal-subtitle">Summary</h3>
            <p><?= nl2br(htmlspecialchars($proposal['summary'])) ?></p>
        </section>
    <?php endif; ?>

    <?php
    $proposalId = (int) ($proposal['id'] ?? 0);
    $coordinatorSign = $proposalId > 0 ? proposal_coordinator_endorsement($proposalId, 'research') : null;
    $deanSign = $proposalId > 0 ? proposal_step_endorsement($proposalId, \App\Support\AccessControl::DEAN, 'College Dean/Immediate Supervisor') : null;
    $submittedDate = proposal_format_workflow_date($proposal['submitted_at'] ?? null);
    ?>
    <section class="proposal-section research-application-signoff">
        <h3 class="proposal-subtitle">Signature of the Applicant</h3>
        <p class="proposal-section-note">I hereby certify that the information given in my application is accurate, complete, and honestly presented. If awarded a research grant, I agree to provide reports. Funds will be used only for my proposed research to cover expenditures as indicated in this application.</p>
        <div class="completed-researches-signoff-grid">
            <div>
                <p class="completed-researches-signoff-label">Applicant</p>
                <p class="completed-researches-signoff-role"><?= htmlspecialchars($applicantName !== '' ? $applicantName : '-') ?></p>
                <p class="proposal-table-meta">Date: <?= htmlspecialchars($submittedDate !== '' ? $submittedDate : '-') ?></p>
            </div>
        </div>
        <div class="completed-researches-signoff-grid">
            <div>
                <p class="completed-researches-signoff-label">Endorsement (R&amp;D Coordinator)</p>
                <p class="proposal-section-note">I hereby certify that the proposed research is currently in line with the thrusts and priorities of the College/Unit/Center and is in accordance with the University RIDE Agenda.</p>
                <p class="completed-researches-signoff-role"><?= htmlspecialchars($coordinatorSign['name'] ?? '-') ?></p>
                <p class="proposal-table-meta">Date: <?= htmlspecialchars(($coordinatorSign['date'] ?? '') !== '' ? $coordinatorSign['date'] : '-') ?></p>
            </div>
            <div>
                <p class="completed-researches-signoff-label">Approval (College Dean/Immediate Supervisor)</p>
                <p class="proposal-section-note">I hereby certify that the proposed research can be conducted by the applicant under the circumstances set out by the applicant.</p>
                <p class="completed-researches-signoff-role"><?= htmlspecialchars($deanSign['name'] ?? '-') ?></p>
                <p class="proposal-table-meta">Date: <?= htmlspecialchars(($deanSign['date'] ?? '') !== '' ? $deanSign['date'] : '-') ?></p>
            </div>
        </div>
    </section>
