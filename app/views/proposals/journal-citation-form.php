<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Journal Citation | RIDE';
$pageHeading = 'Journal Citation';
$pageSubtitle = $isEdit
    ? 'Update your journal citation report before saving or resubmitting.'
    : 'Quarterly report of journal citations in refereed journal articles.';
$user = \App\Core\Auth::user() ?? [];

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$reportAsOfValue = is_string($summaryData['report_as_of'] ?? null) ? $summaryData['report_as_of'] : '';
$collegeDisplay = is_string($summaryData['college_name'] ?? null) && $summaryData['college_name'] !== ''
    ? $summaryData['college_name']
    : $collegeName;
$entries = is_array($summaryData['entries'] ?? null) ? $summaryData['entries'] : [];
if ($entries === []) {
    $entries[] = [];
}
?>

<form class="proposal-paper completed-researches-paper trainings-conducted-paper journal-citation-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/journal-citation') : base_url('proposals/journal-citation') ?>">
    <?= csrf_field() ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <p class="completed-researches-header-line">Quarterly Report of Accomplishments in Research</p>
        <h2 class="completed-researches-title">RESEARCH ARTICLE AS CITED BY OTHER RESEARCHER(S) IN REFEREED JOURNAL ARTICLES</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-11 Rev.00 (09.15.25)</p>
    </header>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Report Information</h2>
        <table class="proposal-table">
                <?php
                $summaryData = [
                    'form_type' => 'journal_citation',
                    'report_as_of' => $reportAsOfValue ?? '',
                    'reporting_period' => quarterly_reporting_period_from_summary(['report_as_of' => $reportAsOfValue ?? '']),
                ];
                require APP_PATH . '/views/partials/quarterly-reporting-period.php';
                ?>
            <tr>
                <th>College</th>
                <td>
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
                        <input type="hidden" name="college_name" id="journal-citation-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <section class="proposal-section trainings-conducted-section journal-citation-section">
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap journal-citation-table-wrap">
            <table class="proposal-table journal-citation-table trainings-conducted-table">
                <thead>
                    <tr>
                        <th>Author(s) of Original Article</th>
                        <th>Title of Original Research Article Cited</th>
                        <th>Title of Refereed Journal where the Original Research Article was Being Cited was Published</th>
                        <th>Title of New Publication by the Original Research Article was Cited</th>
                        <th>Author(s) of the New Article who Cited the Original Research Article</th>
                        <th>Title of Refereed Journal where the New Research Article Was Published</th>
                        <th>Vol. No./Issue no.</th>
                        <th>Number of Pages</th>
                        <th>Year of Publication</th>
                        <th>Publisher</th>
                        <th>Google Drive Link</th>
                        <th class="completed-researches-actions-col">Action</th>
                    </tr>
                </thead>
                <tbody id="journal-citation-rows">
                    <?php foreach ($entries as $index => $row): ?>
                        <tr class="journal-citation-row">
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= $index ?>][authors_original_article]"><?= htmlspecialchars((string) ($row['authors_original_article'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= $index ?>][title_original_article_cited]"><?= htmlspecialchars((string) ($row['title_original_article_cited'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= $index ?>][title_refereed_journal_original]"><?= htmlspecialchars((string) ($row['title_refereed_journal_original'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= $index ?>][title_new_research_article]"><?= htmlspecialchars((string) ($row['title_new_research_article'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= $index ?>][authors_new_article]"><?= htmlspecialchars((string) ($row['authors_new_article'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= $index ?>][title_refereed_journal_new]"><?= htmlspecialchars((string) ($row['title_refereed_journal_new'] ?? '')) ?></textarea></td>
                            <td><input type="text" name="entries[<?= $index ?>][volume_issue]" value="<?= htmlspecialchars((string) ($row['volume_issue'] ?? '')) ?>"></td>
                            <td><input type="text" name="entries[<?= $index ?>][pages]" value="<?= htmlspecialchars((string) ($row['pages'] ?? '')) ?>"></td>
                            <td><input type="number" min="1900" max="2100" step="1" name="entries[<?= $index ?>][year_publication]" value="<?= htmlspecialchars((string) ($row['year_publication'] ?? '')) ?>"></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= $index ?>][publisher]"><?= htmlspecialchars((string) ($row['publisher'] ?? '')) ?></textarea></td>
                            <td class="jc-col-drive-link-cell"><input type="url" name="entries[<?= $index ?>][google_drive_link]" value="<?= htmlspecialchars((string) ($row['google_drive_link'] ?? '')) ?>" placeholder="https://drive.google.com/..."></td>
                            <td class="completed-researches-actions-col"><button type="button" class="btn btn-sm btn-outline journal-citation-remove-row">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="proposal-coauthors-actions completed-researches-add-row-wrap">
            <button type="button" class="btn btn-outline journal-citation-add-row">Add Row</button>
        </div>
    </section>

    <section class="proposal-section completed-researches-signoff">
        <div class="completed-researches-signoff-grid">
            <div>
                <p class="completed-researches-signoff-label">Prepared by:</p>
                <p class="completed-researches-signoff-role">College R&amp;D Coordinator</p>
            </div>
            <div>
                <p class="completed-researches-signoff-label">Attested True and Correct:</p>
                <p class="completed-researches-signoff-role">College Dean</p>
            </div>
        </div>
        <p class="proposal-section-note">Signatures are captured through the approval workflow after submission.</p>
    </section>

    <?php
    $workflowProposal = $proposal ?? [
        'status' => 'draft',
        'project_type' => 'research',
        'summary' => json_encode(['form_type' => 'journal_citation']),
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

<template id="journal-citation-row-template">
    <tr class="journal-citation-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="authors_original_article"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="title_original_article_cited"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="title_refereed_journal_original"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="title_new_research_article"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="authors_new_article"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="title_refereed_journal_new"></textarea></td>
        <td><input type="text" data-field="volume_issue"></td>
        <td><input type="text" data-field="pages"></td>
        <td><input type="number" min="1900" max="2100" step="1" data-field="year_publication"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="publisher"></textarea></td>
        <td><input type="url" placeholder="https://drive.google.com/..." data-field="google_drive_link"></td>
        <td class="completed-researches-actions-col"><button type="button" class="btn btn-sm btn-outline journal-citation-remove-row">Remove</button></td>
    </tr>
</template>

<script>
(() => {
    const collegeSelect = document.querySelector('select[name="college_id"]');
    const collegeNameInput = document.getElementById('journal-citation-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    const fieldNames = [
        'authors_original_article',
        'title_original_article_cited',
        'title_refereed_journal_original',
        'title_new_research_article',
        'authors_new_article',
        'title_refereed_journal_new',
        'volume_issue',
        'pages',
        'year_publication',
        'publisher',
        'google_drive_link',
    ];

    const rowsBody = document.getElementById('journal-citation-rows');
    const rowTemplate = document.getElementById('journal-citation-row-template');
    const addButton = document.querySelector('.journal-citation-add-row');

    const resizeTextareas = () => {
        document.querySelectorAll('textarea.proposal-textarea').forEach((textarea) => {
            const resize = () => {
                textarea.style.height = 'auto';
                textarea.style.height = `${textarea.scrollHeight}px`;
            };
            resize();
            textarea.addEventListener('input', resize);
        });
    };

    const reindexRows = () => {
        if (!rowsBody) {
            return;
        }

        rowsBody.querySelectorAll('.journal-citation-row').forEach((row, index) => {
            fieldNames.forEach((field) => {
                const input = row.querySelector(`[name*="[${field}]"], [data-field="${field}"]`);
                if (!input) {
                    return;
                }
                input.name = `entries[${index}][${field}]`;
                input.removeAttribute('data-field');
            });
        });
    };

    const bindRemove = (row) => {
        const removeButton = row.querySelector('.journal-citation-remove-row');
        removeButton?.addEventListener('click', () => {
            if (!rowsBody) {
                return;
            }
            if (rowsBody.querySelectorAll('.journal-citation-row').length <= 1) {
                row.querySelectorAll('input, textarea').forEach((input) => {
                    if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {
                        input.value = '';
                    }
                });
                return;
            }
            row.remove();
            reindexRows();
        });
    };

    if (rowsBody) {
        rowsBody.querySelectorAll('.journal-citation-row').forEach((row) => bindRemove(row));
    }

    addButton?.addEventListener('click', () => {
        if (!rowsBody || !rowTemplate) {
            return;
        }
        const row = rowTemplate.content.firstElementChild?.cloneNode(true);
        if (!(row instanceof HTMLTableRowElement)) {
            return;
        }
        rowsBody.appendChild(row);
        bindRemove(row);
        reindexRows();
        resizeTextareas();
    });

    reindexRows();
    resizeTextareas();
})();
</script>
