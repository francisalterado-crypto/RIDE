<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Ebalwasyon ng Gawain | RIDE';
$pageHeading = 'Ebalwasyon ng Gawain';
$pageSubtitle = $isEdit
    ? 'Update the activity evaluation before saving or resubmitting.'
    : 'Complete the activity evaluation (WPU-QSF-RIDE-ESO-16).';
$user = \App\Core\Auth::user() ?? [];

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$field = static function (string $key, string $fallback = '') use ($summaryData): string {
    if (isset($summaryData[$key]) && is_string($summaryData[$key])) {
        return $summaryData[$key];
    }

    return $fallback;
};

$userName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
$paksangGawainValue = $field('paksang_gawain');
$pangalanValue = $field('pangalan_ng_dumalo', $userName);
$petsaValue = $field('petsa');
$kasarianValue = $field('kasarian');
$komentoValue = $field('karagdagang_komento');
$lagdaValue = $field('lagda', $pangalanValue);
$ratings = is_array($summaryData['ratings'] ?? null) ? $summaryData['ratings'] : [];
$average = ebalwasyon_ng_gawain_average($ratings);
$markaDisplay = $average !== null ? number_format($average, 2, '.', '') : $field('marka');
$legendDisplay = ebalwasyon_ng_gawain_legend($average);
$collegeDisplay = is_string($summaryData['college_name'] ?? null) && $summaryData['college_name'] !== ''
    ? $summaryData['college_name']
    : $collegeName;
$scale = ebalwasyon_ng_gawain_scale();
?>

<form class="proposal-paper completed-researches-paper trainings-conducted-paper eso-extension-paper ebalwasyon-ng-gawain-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/ebalwasyon-ng-gawain') : base_url('proposals/ebalwasyon-ng-gawain') ?>">
    <?= csrf_field() ?>
    <?php if (proposal_nav_scope() !== null): ?>
        <input type="hidden" name="nav_scope" value="extension">
    <?php endif; ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Extension Services</p>
        <h2 class="completed-researches-title">EBALWASYON NG GAWAIN</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-16 Rev.00 (08.15.25)</p>
    </header>

    <section class="proposal-section">
        <table class="proposal-table completed-researches-meta-table">
            <tr>
                <th>College</th>
                <td colspan="3">
                    <?php if ($collegeDisplay !== ''): ?>
                        <input type="hidden" name="college_name" value="<?= htmlspecialchars($collegeDisplay) ?>">
                        <span><?= htmlspecialchars($collegeDisplay) ?></span>
                    <?php else: ?>
                        <select name="college_id" required>
                            <option value="">Select college</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= (int) $college['id'] ?>" data-name="<?= htmlspecialchars((string) $college['name']) ?>">
                                    <?= htmlspecialchars((string) $college['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="college_name" id="ebalwasyon-ng-gawain-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Paksang Gawain</th>
                <td colspan="3">
                    <textarea name="paksang_gawain" class="proposal-textarea" rows="2" placeholder="Ilagay ang paksang gawain." required><?= htmlspecialchars($paksangGawainValue) ?></textarea>
                </td>
            </tr>
            <tr>
                <th>Pangalan ng Dumalo</th>
                <td>
                    <input name="pangalan_ng_dumalo" value="<?= htmlspecialchars($pangalanValue) ?>" placeholder="Ilagay ang pangalan ng dumalo">
                </td>
                <th>Petsa</th>
                <td>
                    <input type="date" name="petsa" value="<?= htmlspecialchars($petsaValue) ?>">
                </td>
            </tr>
            <tr>
                <th>Kasarian</th>
                <td>
                    <label class="ebalwasyon-choice">
                        <input type="radio" name="kasarian" value="babae" <?= $kasarianValue === 'babae' ? 'checked' : '' ?>> Babae
                    </label>
                    <label class="ebalwasyon-choice">
                        <input type="radio" name="kasarian" value="lalaki" <?= $kasarianValue === 'lalaki' ? 'checked' : '' ?>> Lalaki
                    </label>
                </td>
                <th>Marka</th>
                <td>
                    <strong id="ebalwasyon-marka-value"><?= htmlspecialchars($markaDisplay !== '' ? $markaDisplay : '—') ?></strong>
                    <div class="proposal-table-meta" id="ebalwasyon-marka-legend"><?= htmlspecialchars($legendDisplay) ?></div>
                </td>
            </tr>
        </table>
    </section>

    <section class="proposal-section">
        <p class="ebalwasyon-instructions">
            Nais naming marinig buhat sa inyo ang inyong puna, komento o suhestiyon patungkol sa serbisyong aming ibinigay sa inyo.
            Mangyari lamang na lagyan ng tsek (√) ang isa sa mga sumusunod na pagpipilian na kakatwan sa inyong palagay. Maraming salamat po!
        </p>
        <p class="ebalwasyon-scale-line">
            <?php foreach ($scale as $score => $label): ?>
                <span><strong><?= (int) $score ?></strong> <?= htmlspecialchars($label) ?></span>
            <?php endforeach; ?>
        </p>

        <div class="proposal-table-wrap">
            <table class="proposal-table ebalwasyon-eval-table">
                <thead>
                    <tr>
                        <th>EBALWASYON</th>
                        <?php foreach (array_keys($scale) as $score): ?>
                            <th class="ebalwasyon-score-col"><?= (int) $score ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (ebalwasyon_ng_gawain_sections() as $section): ?>
                        <tr class="ebalwasyon-section-row">
                            <th colspan="6"><?= htmlspecialchars($section['heading']) ?></th>
                        </tr>
                        <?php foreach ($section['items'] as $itemKey => $itemLabel): ?>
                            <?php $selectedScore = (string) ($ratings[$itemKey] ?? ''); ?>
                            <tr>
                                <td><?= htmlspecialchars($itemLabel) ?></td>
                                <?php foreach (array_keys($scale) as $score): ?>
                                    <td class="ebalwasyon-score-col">
                                        <input type="radio" name="ratings[<?= htmlspecialchars($itemKey) ?>]" value="<?= (int) $score ?>" <?= $selectedScore === (string) $score ? 'checked' : '' ?> aria-label="<?= htmlspecialchars($itemLabel . ' — ' . $score) ?>">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="proposal-section">
        <table class="proposal-table completed-researches-meta-table">
            <tr>
                <th>Karagdagang komento o mungkahi</th>
                <td>
                    <textarea name="karagdagang_komento" class="proposal-textarea" rows="5" placeholder="Ilagay ang karagdagang komento o mungkahi."><?= htmlspecialchars($komentoValue) ?></textarea>
                </td>
            </tr>
            <tr>
                <th>Lagda</th>
                <td>
                    <input name="lagda" value="<?= htmlspecialchars($lagdaValue) ?>" placeholder="Ilagay ang pangalan / lagda ng dumalo">
                </td>
            </tr>
        </table>
        <p class="ebalwasyon-legend">
            Legend (Resulta ng Ebalwasyon):
            4.50–5.00 – Best / Lubhang Katangi-tangi;
            3.50–4.49 – Better / Katangi-tangi;
            2.50–3.49 – Good / Kasiya-siya;
            1.50–2.49 – Fair / Kainaman;
            1.00–1.49 – Poor / Mahina
        </p>
    </section>

    <?php
    $workflowProposal = $proposal ?? [
        'status' => 'draft',
        'project_type' => 'extension',
        'summary' => json_encode(['form_type' => 'ebalwasyon_ng_gawain']),
        'first_name' => (string) ($user['first_name'] ?? ''),
        'last_name' => (string) ($user['last_name'] ?? ''),
    ];
    $workflowSteps = proposal_workflow_steps($workflowProposal);
    require APP_PATH . '/views/proposals/_approval-workflow.php';
    ?>

    <div class="actions proposal-form-actions">
        <button type="submit" class="btn"><?= $isEdit ? 'Save Changes' : 'Save Draft' ?></button>
        <?php if ($isEdit): ?>
            <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id']) ?>">Cancel</a>
        <?php endif; ?>
    </div>
</form>

<script>
(() => {
    const collegeSelect = document.querySelector('select[name="college_id"]');
    const collegeNameInput = document.getElementById('ebalwasyon-ng-gawain-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    document.querySelectorAll('textarea.proposal-textarea').forEach((textarea) => {
        const resize = () => {
            textarea.style.height = 'auto';
            textarea.style.height = `${textarea.scrollHeight}px`;
        };
        resize();
        textarea.addEventListener('input', resize);
    });

    const markaValue = document.getElementById('ebalwasyon-marka-value');
    const markaLegend = document.getElementById('ebalwasyon-marka-legend');
    const legendFor = (average) => {
        if (average >= 4.5) return 'Best / Lubhang Katangi-tangi';
        if (average >= 3.5) return 'Better / Katangi-tangi';
        if (average >= 2.5) return 'Good / Kasiya-siya';
        if (average >= 1.5) return 'Fair / Kainaman';
        return 'Poor / Mahina';
    };
    const updateMarka = () => {
        const checked = [...document.querySelectorAll('.ebalwasyon-eval-table input[type="radio"]:checked')]
            .map((input) => Number(input.value))
            .filter((value) => value >= 1 && value <= 5);
        if (!markaValue || !markaLegend) {
            return;
        }
        if (checked.length === 0) {
            markaValue.textContent = '—';
            markaLegend.textContent = '';
            return;
        }
        const average = checked.reduce((sum, value) => sum + value, 0) / checked.length;
        markaValue.textContent = average.toFixed(2);
        markaLegend.textContent = legendFor(average);
    };
    document.querySelectorAll('.ebalwasyon-eval-table input[type="radio"]').forEach((input) => {
        input.addEventListener('change', updateMarka);
    });
    updateMarka();
})();
</script>
