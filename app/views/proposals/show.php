<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */
$pageTitle = (string) $proposal['title'] . ' | RIDE';
$pageHeading = 'Research Proposal Application';
$pageSubtitle = 'Review the submitted application in the same structured format used for encoding.';
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
<div class="page-actions-bar">
    <?php if (in_array($proposal['status'], ['ongoing','approved','completed'], true)): ?>
        <a class="btn btn-accent" href="<?= base_url('projects/' . $proposal['id']) ?>">Monitor Project</a>
    <?php endif; ?>
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<div class="proposal-paper completed-researches-paper trainings-conducted-paper">
    <section class="proposal-section">
        <h2 class="proposal-section-title">Applicant&apos;s Information</h2>
        <table class="proposal-table">
            <tr>
                <th>Name of Applicant</th>
                <td colspan="3"><?= htmlspecialchars($applicantName !== '' ? $applicantName : '—') ?></td>
            </tr>
            <?php if ($summaryField('applicant_title_prefix') !== '' || $summaryField('applicant_sex') !== ''): ?>
                <tr>
                    <th>Title/Prefix</th>
                    <td><?= htmlspecialchars($summaryField('applicant_title_prefix') !== '' ? $summaryField('applicant_title_prefix') : '—') ?></td>
                    <th>Sex</th>
                    <td><?= htmlspecialchars($summaryField('applicant_sex') !== '' ? $summaryField('applicant_sex') : '—') ?></td>
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
                    <td><?= htmlspecialchars($summaryField('applicant_email') !== '' ? $summaryField('applicant_email') : '—') ?></td>
                    <th>Contact Number</th>
                    <td><?= htmlspecialchars($summaryField('applicant_contact_number') !== '' ? $summaryField('applicant_contact_number') : '—') ?></td>
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
                                : '—';
                            ?>
                            <tr>
                                <?php if ($showCoAuthorAccountDetails): ?>
                                    <td><?= $linkedUserId > 0 ? 'Faculty account' : 'Manual entry' ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars((string) ($coAuthor['last_name'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) ($coAuthor['first_name'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) ($coAuthor['middle_name'] ?? '—')) ?></td>
                                <?php if ($showCoAuthorAccountDetails): ?>
                                    <td><?= htmlspecialchars($invitationLabel !== '' ? $invitationLabel : ($linkedUserId > 0 ? 'Legacy (accepted)' : '—')) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Project Information</h2>
        <table class="proposal-table">
            <tr>
                <th>Title of Proposed Research</th>
                <td colspan="3"><?= htmlspecialchars($proposal['title']) ?></td>
            </tr>
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
            <tr>
                <th>Project Type</th>
                <td><?= htmlspecialchars(ucfirst((string) $proposal['project_type'])) ?></td>
                <th>Funding/Support</th>
                <td><?= htmlspecialchars($proposal['funding_source'] ?? '—') ?></td>
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
                    <td><?= htmlspecialchars($summaryField('period_covered') !== '' ? $summaryField('period_covered') : '—') ?></td>
                    <th>Duration in Months</th>
                    <td><?= htmlspecialchars($summaryField('duration_months') !== '' ? $summaryField('duration_months') : '—') ?></td>
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
    $workflowSteps = proposal_workflow_steps($proposal);
    require APP_PATH . '/views/proposals/_approval-workflow.php';
    ?>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Workflow Actions</h3>
        <div class="actions proposal-form-actions">
<?php
            $submitConfirmMessage = 'Submit this proposal for review?';
            require APP_PATH . '/views/proposals/_submit-for-review-button.php';
            ?>

            <?php if ($canApprove): ?>
                <form method="post" action="<?= base_url('proposals/' . $proposal['id'] . '/approve') ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-accent"><?= htmlspecialchars($approveLabel) ?></button>
                </form>
                <form method="post" action="<?= base_url('proposals/' . $proposal['id'] . '/return') ?>" class="proposal-review-form">
                    <?= csrf_field() ?>
                    <input name="comment" placeholder="Revision comments (required)" required>
                    <button type="submit" class="btn btn-outline">Return for Revision</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="proposal-paper proposal-history-paper">
    <section class="proposal-section">
        <h2 class="proposal-section-title">Comments &amp; History</h2>
        <?php if (empty($comments)): ?>
            <p>No comments yet.</p>
        <?php else: ?>
            <ul class="proposal-history-list">
                <?php foreach ($comments as $c): ?>
                    <li>
                        <div class="proposal-history-meta">
                            <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
                            • <?= htmlspecialchars($c['action']) ?>
                            <?php if ($c['step']): ?> (<?= htmlspecialchars($c['step']) ?>)<?php endif; ?>
                            • <?= htmlspecialchars($c['created_at']) ?>
                        </div>
                        <div><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
