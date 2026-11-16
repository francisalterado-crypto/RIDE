<?php
/**
 * Summary strip at the top of a proposal's page: code, type, lead, status and current step.
 *
 * @var array $proposal
 */
$stripIsExtension = (string) ($proposal['project_type'] ?? '') === 'extension';
$stripLead = trim((string) ($proposal['first_name'] ?? '') . ' ' . (string) ($proposal['last_name'] ?? ''));
$stripStep = !empty($proposal['current_step'])
    ? \App\Support\AccessControl::stepLabel((string) $proposal['current_step'])
    : (in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true) ? 'Not submitted yet' : '');
?>
<div class="card proposal-status-strip">
    <dl>
        <div><dt>Code</dt><dd><?= htmlspecialchars((string) ($proposal['project_code'] ?? '') !== '' ? (string) $proposal['project_code'] : 'Not assigned') ?></dd></div>
        <div><dt>Type</dt><dd><span class="proposal-kind proposal-kind--<?= $stripIsExtension ? 'extension' : 'research' ?>"><?= $stripIsExtension ? 'Extension' : 'Research' ?></span></dd></div>
        <?php if ($stripLead !== ''): ?><div><dt>Lead</dt><dd><?= htmlspecialchars($stripLead) ?></dd></div><?php endif; ?>
        <div><dt>Status</dt><dd><span class="badge badge-<?= htmlspecialchars((string) ($proposal['status'] ?? '')) ?>"><?= htmlspecialchars(status_label((string) ($proposal['status'] ?? ''))) ?></span></dd></div>
        <?php if ($stripStep !== ''): ?><div><dt>Step</dt><dd><?= htmlspecialchars($stripStep) ?></dd></div><?php endif; ?>
    </dl>
</div>
