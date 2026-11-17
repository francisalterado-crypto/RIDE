<?php
/** @var list<array<string, string>> $rows */
/** @var bool $readOnly */
$readOnly = $readOnly ?? false;
?>
<section class="proposal-section trainings-conducted-section obr-matrix-section">
    <h3 class="proposal-subtitle">Research Matrix Entries</h3>
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap obr-matrix-table-wrap">
        <table class="proposal-table obr-matrix-table trainings-conducted-table">
            <colgroup>
                <col class="obr-col-thrust">
                <col class="obr-col-thrust">
                <col class="obr-col-area">
                <col class="obr-col-title">
                <col class="obr-col-results">
                <col class="obr-col-extension">
                <col class="obr-col-outcomes">
                <col class="obr-col-sixps">
                <?php if (!$readOnly): ?>
                    <col class="obr-col-actions">
                <?php endif; ?>
            </colgroup>
            <thead>
                <tr>
                    <th>University Thrusts &amp; Priorities</th>
                    <th>College Thrusts &amp; Priorities</th>
                    <th>Research Area(s)</th>
                    <th>Research Study/Project/Program Title</th>
                    <th>Research Results/Output (Product/Processes)</th>
                    <th>Research Output Utilization as Extension Program(s)</th>
                    <th>Outcome(s)/ Commercialization, etc.</th>
                    <th>6Ps</th>
                    <?php if (!$readOnly): ?>
                        <th class="completed-researches-actions-col"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="obr-matrix-rows">
                <?php if ($rows === []): ?>
                    <?php $rows = [[], [], [], [], []]; ?>
                <?php endif; ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr class="obr-matrix-row">
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][university_thrusts]' ?>"><?= htmlspecialchars((string) ($row['university_thrusts'] ?? '')) ?></textarea></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][college_thrusts]' ?>"><?= htmlspecialchars((string) ($row['college_thrusts'] ?? '')) ?></textarea></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][research_areas]' ?>"><?= htmlspecialchars((string) ($row['research_areas'] ?? '')) ?></textarea></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][study_title]' ?>"><?= htmlspecialchars((string) ($row['study_title'] ?? '')) ?></textarea></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][research_results]' ?>"><?= htmlspecialchars((string) ($row['research_results'] ?? '')) ?></textarea></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][extension_utilization]' ?>"><?= htmlspecialchars((string) ($row['extension_utilization'] ?? '')) ?></textarea></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][outcomes]' ?>"><?= htmlspecialchars((string) ($row['outcomes'] ?? '')) ?></textarea></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][six_ps]' ?>"><?= htmlspecialchars((string) ($row['six_ps'] ?? '')) ?></textarea></td>
                        <?php if (!$readOnly): ?>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-sm btn-outline obr-matrix-remove-row" title="Remove row">Remove</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="proposal-coauthors-actions completed-researches-add-row-wrap">
            <button type="button" class="btn btn-sm btn-outline" id="obr-matrix-add-row">Add Row</button>
        </div>
    <?php endif; ?>
</section>
