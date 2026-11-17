<?php
/**
 * The Terminal Report Assessment (WPU-QSF-RDE-VPRDE-22) saved with the RDO Research Coordinator's approval.
 *
 * @var array $proposal
 */

use App\Support\TerminalReportAssessment;

$assessment = TerminalReportAssessment::saved($proposal);
if ($assessment === null) {
    return;
}
$assessmentValues = $assessment['values'];
$judgmentKey = (string) ($assessmentValues['judgment'] ?? '');
$assessmentComments = trim((string) ($assessmentValues['comments'] ?? ''));
?>
<div class="webform wf-view">
    <section class="webform-card">
        <header class="webform-card-head">
            <span class="webform-card-num"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i></span>
            <div>
                <h2>Terminal Report Assessment</h2>
                <p>Form WPU-QSF-RDE-VPRDE-22, entered by the Research Coordinator (RIDE-RDO) with their approval.</p>
            </div>
        </header>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Parameter</th>
                        <th scope="col">Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (TerminalReportAssessment::PARAMETERS as $number => $text): ?>
                        <?php $score = (string) ($assessmentValues['score_' . $number] ?? ''); ?>
                        <tr>
                            <td><?= (int) $number ?>. <?= htmlspecialchars($text) ?></td>
                            <td><?= htmlspecialchars(TerminalReportAssessment::SCORES[$score] ?? 'Not rated') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="webform-grid">
            <div class="wf-view-field webform-field--full">
                <span class="wf-view-label">Judgment</span>
                <div class="wf-view-value"><?= htmlspecialchars(TerminalReportAssessment::JUDGMENTS[$judgmentKey] ?? 'Not provided') ?></div>
            </div>
            <div class="wf-view-field webform-field--full">
                <span class="wf-view-label">Comments and suggested revisions</span>
                <div class="wf-view-value"><?= $assessmentComments !== '' ? nl2br(htmlspecialchars($assessmentComments)) : '<span class="wf-view-empty">None</span>' ?></div>
            </div>
            <div class="wf-view-field">
                <span class="wf-view-label">Assessed by</span>
                <div class="wf-view-value"><?= htmlspecialchars($assessment['assessor'] !== '' ? $assessment['assessor'] : 'Research Coordinator (RIDE-RDO)') ?></div>
            </div>
            <div class="wf-view-field">
                <span class="wf-view-label">Date</span>
                <div class="wf-view-value"><?= htmlspecialchars($assessment['date']) ?></div>
            </div>
        </div>
    </section>
</div>
