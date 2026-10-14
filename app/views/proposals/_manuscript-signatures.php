<?php

/** @var array|null $proposal */

/** @var array $user */

/** @var bool $showSignatureHint */

use App\Support\MonitoringRoles;

$proposalId = is_array($proposal) ? (int) ($proposal['id'] ?? 0) : 0;
$projectType = is_array($proposal) ? (string) ($proposal['project_type'] ?? 'research') : 'research';
$isExtension = $projectType === 'extension';
$applicantSignatureUrl = signature_url((int) ($user['id'] ?? ($proposal['user_id'] ?? 0)));
$applicantName = trim((string) ($user['first_name'] ?? $proposal['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? $proposal['last_name'] ?? ''));

$collegeId = is_array($proposal) && !empty($proposal['college_id'])
    ? (int) $proposal['college_id']
    : (int) ($user['college_id'] ?? 0);

$collegeName = is_array($proposal) && !empty($proposal['college_name'])
    ? (string) $proposal['college_name']
    : (string) ($user['college_name'] ?? '');

if ($collegeName === '' && $collegeId > 0) {
    $college = \App\Models\College::find($collegeId);
    $collegeName = is_array($college) ? (string) ($college['name'] ?? '') : '';
}

$coordinatorStep = MonitoringRoles::coordinatorStepForType($projectType);
$coordinatorLabel = $isExtension ? 'Field Extensionist' : 'Research Coordinator';
$coordinatorEndorsement = $proposalId > 0 ? proposal_step_endorsement($proposalId, $coordinatorStep, $coordinatorLabel) : null;
$deanEndorsement = $proposalId > 0 ? proposal_step_endorsement($proposalId, MonitoringRoles::DEAN, 'College Dean') : null;

$coordinatorSignatory = proposal_manuscript_college_signatory(
    $collegeId,
    $collegeName,
    $coordinatorStep,
    $coordinatorLabel,
    $coordinatorEndorsement
);

$deanSignatory = proposal_manuscript_college_signatory(
    $collegeId,
    $collegeName,
    MonitoringRoles::DEAN,
    'College Dean',
    $deanEndorsement
);

$directorStep = MonitoringRoles::directorStepForType($projectType);
$directorRoleLabel = $isExtension ? 'ESO Director' : 'RDO Director';
$directorDisplayRole = $isExtension
    ? 'ESO Director'
    : 'Director, Research and Development Office';

$directorEndorsement = $proposalId > 0
    ? proposal_step_endorsement($proposalId, $directorStep, $directorRoleLabel)
    : null;
$directorSignatory = proposal_manuscript_role_signatory(
    $directorStep,
    $directorDisplayRole,
    $directorEndorsement,
    $isExtension ? '' : 'DR. JHONAMIE M. OMAR'
);

$isApproved = is_array($proposal) && in_array((string) ($proposal['status'] ?? ''), ['ongoing', 'approved', 'completed'], true);
$approvedAt = '';

if ($isApproved && is_string($proposal['approved_at'] ?? null) && $proposal['approved_at'] !== '') {
    $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));
    $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $proposal['approved_at'], $timezone)
        ?: \DateTimeImmutable::createFromFormat('Y-m-d', $proposal['approved_at'], $timezone);

    if ($parsed instanceof \DateTimeImmutable) {
        $approvedAt = $parsed->format('F j, Y');
    }
}

$blocks = [
    [
        'label' => 'Submitted by:',
        'role' => $isExtension ? 'Faculty' : 'Faculty',
        'signature_url' => $applicantSignatureUrl,
        'name' => $applicantName,
    ],
    [
        'label' => 'Received and evaluated by:',
        'role' => $coordinatorSignatory['role'],
        'signature_url' => $coordinatorSignatory['signature_url'],
        'date' => $coordinatorSignatory['date'],
        'name' => $coordinatorSignatory['name'],
    ],
    [
        'label' => 'Recommending approval:',
        'role' => $deanSignatory['role'],
        'signature_url' => $deanSignatory['signature_url'],
        'date' => $deanSignatory['date'],
        'name' => $deanSignatory['name'],
    ],
    [
        'label' => 'Recommending approval:',
        'role' => $directorSignatory['role'],
        'signature_url' => $directorSignatory['signature_url'],
        'date' => $directorSignatory['date'],
        'name' => $directorSignatory['name'],
    ],
    [
        'label' => 'Approved by:',
        'role' => 'VP Research, Innovation, Development and Extension (RIDE)',
        'signature_url' => null,
        'date' => $approvedAt,
        'name' => 'DR. LOTA A. CREENCIA',
    ],
];

?>
<section class="proposal-section proposal-signature-section manuscript-signatures">
    <h3 class="proposal-subtitle">Approval Workflow</h3>
    <?php foreach ($blocks as $block): ?>
        <?php $displayName = trim((string) ($block['name'] ?? '')); ?>
        <div class="manuscript-signature-row">
            <div class="manuscript-signature-label"><?= htmlspecialchars($block['label']) ?></div>
            <div class="manuscript-signature-block<?= $displayName === '' ? ' manuscript-signature-block--empty' : '' ?>">
                <?php if (($block['signature_url'] ?? null) !== null): ?>
                    <div class="manuscript-signature-image">
                        <img src="<?= htmlspecialchars($block['signature_url']) ?>" alt="Signature" class="proposal-signature-image">
                    </div>
                <?php else: ?>
                    <div class="manuscript-signature-image manuscript-signature-image--empty" aria-hidden="true"></div>
                <?php endif; ?>

                <div class="manuscript-signature-identity">
                    <div class="manuscript-signature-line" aria-hidden="true"></div>
                    <div class="manuscript-signature-name"><?= $displayName !== '' ? htmlspecialchars($displayName) : '&nbsp;' ?></div>
                </div>

                <div class="manuscript-signature-role"><?= htmlspecialchars($block['role']) ?></div>

                <?php if (($block['date'] ?? '') !== ''): ?>
                    <div class="manuscript-signature-date"><?= htmlspecialchars($block['date']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($showSignatureHint) && $applicantSignatureUrl === null): ?>
        <p class="proposal-section-note manuscript-signature-note">
            Upload your signature in
            <a href="<?= base_url('profile') ?>">My Profile</a>
            to display it above your printed name.
        </p>
    <?php endif; ?>
</section>
