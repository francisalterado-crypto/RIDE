<?php
/**
 * Proposal page block for forms that print from the office Word template (RDO-20, RDO request forms): the
 * "Print Preview (.docx)" download, available once the required fields are filled.
 */
/** @var array $proposal */
/** @var bool $canEdit */
/** @var string $editUrl */
$formMissing = \App\Support\FormPreviewRequirements::missing($proposal);
$formLabel = \App\Support\ResearchRequestForms::typeOf($proposal) !== null
    ? (string) (\App\Support\ResearchRequestForms::get((string) \App\Support\ResearchRequestForms::typeOf($proposal))['code'] ?? '')
    : 'WPU-QSF-RIDE-RDO-20';
?>
<div class="app-panel form-docx-card">
    <section class="proposal-section">
        <h2 class="proposal-section-title">Printable Form</h2>
        <?php if ($formMissing === []): ?>
            <p class="proposal-section-note">The filled <?= htmlspecialchars($formLabel) ?> form, on the office letterhead. Open it in Word to view, print or save as PDF.</p>
            <a class="btn btn-accent" href="<?= base_url('proposals/' . (int) $proposal['id'] . '/docx') ?>">Print Preview (.docx)</a>
        <?php else: ?>
            <p class="proposal-section-note">Print Preview is available once the form is filled in. Still missing:</p>
            <ul class="form-docx-missing">
                <?php foreach ($formMissing as $label): ?>
                    <li><?= htmlspecialchars($label) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn btn-accent" disabled>Print Preview (.docx)</button>
            <?php if ($canEdit): ?>
                <a class="btn btn-outline" href="<?= htmlspecialchars($editUrl) ?>">Complete the form</a>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
