<?php
/**
 * "Office form" line on a form page: the form's code and a link to its blank form in Downloadable Forms.
 *
 * @var string $blankFormType form type key (see App\Support\OfficeForms)
 */
$blankForm = \App\Support\OfficeForms::get((string) ($blankFormType ?? ''));
$blankTemplate = $blankForm !== null ? \App\Support\OfficeForms::template((string) $blankFormType) : null;
?>
<?php if ($blankForm !== null): ?>
    <div class="blank-form-link">
        <i class="fas fa-file-word" aria-hidden="true"></i>
        <span>Office form <span class="doc-code"><?= htmlspecialchars($blankForm['code']) ?></span></span>
        <?php if ($blankTemplate !== null): ?>
            <a href="<?= base_url('forms/' . (int) $blankTemplate['id'] . '/download') ?>"><i class="fas fa-download" aria-hidden="true"></i> Download blank form</a>
        <?php else: ?>
            <span class="doc-template-missing">The blank form is not in Downloadable Forms yet.</span>
        <?php endif; ?>
    </div>
<?php endif; ?>
