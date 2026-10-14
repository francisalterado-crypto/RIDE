<?php
/** @var array<string, mixed> $profileUser */
/** @var list<array<string, mixed>> $papers */
/** @var list<array<string, mixed>> $presentations */
/** @var array<int, list<array<string, mixed>>> $paperAttachments */
/** @var array<int, list<array<string, mixed>>> $presentationAttachments */

$pageTitle = 'My Scholarly Output | RIDE';
$pageHeading = 'Published Papers & Presentations';
$pageSubtitle = 'Keep a record of your published papers and conference presentations for university monitoring.';

$statusLabels = ['published' => 'Published', 'accepted' => 'Accepted', 'in_press' => 'In Press'];
$typeLabels = ['oral' => 'Oral', 'poster' => 'Poster', 'virtual' => 'Virtual', 'other' => 'Other'];
$fileAccept = '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip';

$e = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''));
$formatDate = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '';
    }
    $time = strtotime($date);

    return $time ? date('M j, Y', $time) : $date;
};
$doiUrl = static function (string $doi): string {
    $doi = trim($doi);
    if (preg_match('#^https?://#i', $doi)) {
        return $doi;
    }

    return 'https://doi.org/' . preg_replace('#^doi:\s*#i', '', $doi);
};
$isWebLink = static fn (string $url): bool => (bool) preg_match('#^https?://#i', $url);

$profileLinks = array_filter([
    'ORCID' => !empty($profileUser['orcid_id']) ? 'https://orcid.org/' . $profileUser['orcid_id'] : '',
    'Google Scholar' => (string) ($profileUser['google_scholar_url'] ?? ''),
    'ResearchGate' => (string) ($profileUser['researchgate_url'] ?? ''),
    'GitHub' => (string) ($profileUser['github_url'] ?? ''),
]);

/** Paper fields shared by the add form and each edit form. */
$paperFields = static function (array $paper, string $prefix) use ($e, $statusLabels): void {
    $status = (string) ($paper['status'] ?? 'published');
    ?>
    <div class="scholarly-form-grid">
        <div class="scholarly-field scholarly-field--full">
            <label for="<?= $prefix ?>_title">Paper title <span class="required">*</span></label>
            <input id="<?= $prefix ?>_title" name="title" required maxlength="500" value="<?= $e($paper['title'] ?? '') ?>">
        </div>
        <div class="scholarly-field scholarly-field--full">
            <label for="<?= $prefix ?>_authors">Authors</label>
            <input id="<?= $prefix ?>_authors" name="authors" maxlength="500" value="<?= $e($paper['authors'] ?? '') ?>" placeholder="e.g. Dela Cruz, J., Santos, M.">
        </div>
        <div class="scholarly-field scholarly-field--full">
            <label for="<?= $prefix ?>_journal">Journal name <span class="required">*</span></label>
            <input id="<?= $prefix ?>_journal" name="journal_name" required maxlength="255" value="<?= $e($paper['journal_name'] ?? '') ?>">
        </div>
        <div class="scholarly-field">
            <label for="<?= $prefix ?>_date">Publication date</label>
            <input id="<?= $prefix ?>_date" type="date" name="publication_date" value="<?= $e($paper['publication_date'] ?? '') ?>">
        </div>
        <div class="scholarly-field">
            <label for="<?= $prefix ?>_year">Publication year</label>
            <input id="<?= $prefix ?>_year" type="number" name="publication_year" min="1900" max="2100" value="<?= $e($paper['publication_year'] ?? date('Y')) ?>">
        </div>
        <div class="scholarly-field">
            <label for="<?= $prefix ?>_status">Status</label>
            <select id="<?= $prefix ?>_status" name="status">
                <?php foreach ($statusLabels as $value => $label): ?>
                    <option value="<?= $value ?>"<?= $status === $value ? ' selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="scholarly-field">
            <label for="<?= $prefix ?>_indexing">Indexing</label>
            <input id="<?= $prefix ?>_indexing" name="indexing" maxlength="120" value="<?= $e($paper['indexing'] ?? '') ?>" placeholder="e.g. Scopus, Web of Science">
        </div>
        <div class="scholarly-field">
            <label for="<?= $prefix ?>_doi">DOI</label>
            <input id="<?= $prefix ?>_doi" name="doi" maxlength="120" value="<?= $e($paper['doi'] ?? '') ?>" placeholder="e.g. 10.1000/xyz123">
        </div>
        <div class="scholarly-field">
            <label for="<?= $prefix ?>_link">Link to the paper</label>
            <input id="<?= $prefix ?>_link" type="url" name="link" maxlength="500" value="<?= $e($paper['link'] ?? '') ?>" placeholder="https://">
        </div>
        <div class="scholarly-field scholarly-field--full">
            <label for="<?= $prefix ?>_notes">Notes</label>
            <textarea id="<?= $prefix ?>_notes" name="notes" rows="2" placeholder="Optional, e.g. volume and issue, page numbers, or funding source"><?= $e($paper['notes'] ?? '') ?></textarea>
        </div>
    </div>
    <?php
};

/** Presentation fields shared by the add form and each edit form. */
$presentationFields = static function (array $presentation, string $prefix) use ($e, $typeLabels): void {
    $type = (string) ($presentation['presentation_type'] ?? 'oral');
    ?>
    <div class="scholarly-form-grid">
        <div class="scholarly-field scholarly-field--full">
            <label for="<?= $prefix ?>_title">Presentation title <span class="required">*</span></label>
            <input id="<?= $prefix ?>_title" name="title" required maxlength="500" value="<?= $e($presentation['title'] ?? '') ?>">
        </div>
        <div class="scholarly-field scholarly-field--full">
            <label for="<?= $prefix ?>_conference">Conference or event <span class="required">*</span></label>
            <input id="<?= $prefix ?>_conference" name="conference_name" required maxlength="255" value="<?= $e($presentation['conference_name'] ?? '') ?>">
        </div>
        <div class="scholarly-field">
            <label for="<?= $prefix ?>_type">Type</label>
            <select id="<?= $prefix ?>_type" name="presentation_type">
                <?php foreach ($typeLabels as $value => $label): ?>
                    <option value="<?= $value ?>"<?= $type === $value ? ' selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="scholarly-field">
            <label for="<?= $prefix ?>_date">Presentation date</label>
            <input id="<?= $prefix ?>_date" type="date" name="presentation_date" value="<?= $e($presentation['presentation_date'] ?? '') ?>">
        </div>
        <div class="scholarly-field scholarly-field--full">
            <label for="<?= $prefix ?>_location">Location</label>
            <input id="<?= $prefix ?>_location" name="location" maxlength="255" value="<?= $e($presentation['location'] ?? '') ?>" placeholder="City, country, or venue">
        </div>
        <div class="scholarly-field scholarly-field--full">
            <label class="proposal-checkbox-row">
                <input type="checkbox" name="is_international" value="1"<?= !empty($presentation['is_international']) ? ' checked' : '' ?>>
                International presentation
            </label>
        </div>
        <div class="scholarly-field scholarly-field--full">
            <label for="<?= $prefix ?>_notes">Notes</label>
            <textarea id="<?= $prefix ?>_notes" name="notes" rows="2" placeholder="Optional, e.g. award received or session name"><?= $e($presentation['notes'] ?? '') ?></textarea>
        </div>
    </div>
    <?php
};
?>

<div class="card scholarly-profiles">
    <div>
        <h2>Researcher Profiles</h2>
        <?php if ($profileLinks === []): ?>
            <p class="muted">Add your ORCID iD, Google Scholar, ResearchGate, and GitHub links so they show with your publications.</p>
        <?php else: ?>
            <div class="scholarly-profile-links">
                <?php foreach ($profileLinks as $label => $url): ?>
                    <a href="<?= $e($url) ?>" target="_blank" rel="noopener noreferrer"><?= $e($label) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <a class="btn btn-outline btn-sm" href="<?= base_url('profile#researcher-profiles') ?>"><?= $profileLinks === [] ? 'Add profile links' : 'Edit profile links' ?></a>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2>Add Published Paper</h2>
        <form method="post" action="<?= base_url('scholarly/papers') ?>" enctype="multipart/form-data" data-capitalize="off">
            <?= csrf_field() ?>
            <?php $paperFields([], 'paper_new'); ?>
            <label for="paper_new_documents">Supporting documents</label>
            <input id="paper_new_documents" type="file" name="supporting_documents[]" multiple accept="<?= $fileAccept ?>">
            <p class="muted scholarly-hint">Optional. Copies, acceptance letters, or other proof (PDF, Word, Excel, images, or ZIP, up to 10 MB each).</p>
            <div class="actions">
                <button type="submit" class="btn btn-accent">Save Paper</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Add Paper Presentation</h2>
        <form method="post" action="<?= base_url('scholarly/presentations') ?>" enctype="multipart/form-data" data-capitalize="off">
            <?= csrf_field() ?>
            <?php $presentationFields([], 'presentation_new'); ?>
            <label for="presentation_new_documents">Supporting documents</label>
            <input id="presentation_new_documents" type="file" name="supporting_documents[]" multiple accept="<?= $fileAccept ?>">
            <p class="muted scholarly-hint">Optional. Certificates, programs, or other proof (PDF, Word, Excel, images, or ZIP, up to 10 MB each).</p>
            <div class="actions">
                <button type="submit" class="btn btn-accent">Save Presentation</button>
            </div>
        </form>
    </div>
</div>

<div class="card scholarly-list-card">
    <h2>My Published Papers <span class="scholarly-count"><?= count($papers) ?></span></h2>
    <?php if (empty($papers)): ?>
        <p class="muted">You have not recorded any published papers yet.</p>
    <?php endif; ?>

    <?php foreach ($papers as $paper): ?>
        <?php
        $paperId = (int) $paper['id'];
        $year = (string) ($paper['publication_year'] ?? '');
        $dateText = $formatDate($paper['publication_date'] ?? null);
        $status = (string) ($paper['status'] ?? 'published');
        $attachments = $paperAttachments[$paperId] ?? [];
        $uploadAction = base_url('scholarly/papers/' . $paperId . '/attachments');
        $downloadBase = 'scholarly/attachments';
        $canManage = true;
        ?>
        <article class="scholarly-record" id="paper-<?= $paperId ?>">
            <div class="scholarly-record-head">
                <h3><?= $e($paper['title']) ?></h3>
                <span class="scholarly-status scholarly-status--<?= $e($status) ?>"><?= $e($statusLabels[$status] ?? $status) ?></span>
            </div>
            <?php if (!empty($paper['authors'])): ?>
                <p class="scholarly-authors"><?= $e($paper['authors']) ?></p>
            <?php endif; ?>
            <p class="scholarly-meta">
                <em><?= $e($paper['journal_name']) ?></em><?= $dateText !== '' ? ', ' . $e($dateText) : ($year !== '' ? ', ' . $e($year) : '') ?>
                <?php if (!empty($paper['indexing'])): ?><span class="scholarly-tag"><?= $e($paper['indexing']) ?></span><?php endif; ?>
            </p>
            <?php if (!empty($paper['doi']) || (!empty($paper['link']) && $isWebLink((string) $paper['link']))): ?>
                <p class="scholarly-links">
                    <?php if (!empty($paper['doi'])): ?>
                        DOI: <a href="<?= $e($doiUrl((string) $paper['doi'])) ?>" target="_blank" rel="noopener noreferrer"><?= $e($paper['doi']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($paper['link']) && $isWebLink((string) $paper['link'])): ?>
                        <a href="<?= $e($paper['link']) ?>" target="_blank" rel="noopener noreferrer">View paper</a>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($paper['notes'])): ?>
                <p class="scholarly-notes"><?= nl2br($e($paper['notes'])) ?></p>
            <?php endif; ?>

            <div class="scholarly-docs">
                <span class="scholarly-docs-label">Supporting documents</span>
                <?php if (empty($attachments)): ?><span class="muted">None yet</span><?php endif; ?>
                <?php include __DIR__ . '/_attachments.php'; ?>
            </div>

            <div class="scholarly-record-actions">
                <button type="button" class="btn btn-outline btn-sm" data-scholarly-edit="paper-edit-<?= $paperId ?>" aria-expanded="false" aria-controls="paper-edit-<?= $paperId ?>">Edit</button>
                <form method="post" action="<?= base_url('scholarly/papers/' . $paperId . '/delete') ?>" onsubmit="return confirm('Remove this published paper and its supporting documents?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                </form>
            </div>

            <form class="scholarly-edit-form" id="paper-edit-<?= $paperId ?>" method="post" action="<?= base_url('scholarly/papers/' . $paperId . '/update') ?>" enctype="multipart/form-data" data-capitalize="off" hidden>
                <?= csrf_field() ?>
                <h4>Edit paper</h4>
                <?php $paperFields($paper, 'paper_' . $paperId); ?>
                <label for="paper_<?= $paperId ?>_documents">Add more supporting documents</label>
                <input id="paper_<?= $paperId ?>_documents" type="file" name="supporting_documents[]" multiple accept="<?= $fileAccept ?>">
                <p class="muted scholarly-hint">Files already attached are kept.</p>
                <div class="actions">
                    <button type="submit" class="btn btn-accent btn-sm">Save Changes</button>
                    <button type="button" class="btn btn-outline btn-sm" data-scholarly-edit="paper-edit-<?= $paperId ?>">Cancel</button>
                </div>
            </form>
        </article>
    <?php endforeach; ?>
</div>

<div class="card scholarly-list-card">
    <h2>My Paper Presentations <span class="scholarly-count"><?= count($presentations) ?></span></h2>
    <?php if (empty($presentations)): ?>
        <p class="muted">You have not recorded any paper presentations yet.</p>
    <?php endif; ?>

    <?php foreach ($presentations as $presentation): ?>
        <?php
        $presentationId = (int) $presentation['id'];
        $type = (string) ($presentation['presentation_type'] ?? 'oral');
        $dateText = $formatDate($presentation['presentation_date'] ?? null);
        $attachments = $presentationAttachments[$presentationId] ?? [];
        $uploadAction = base_url('scholarly/presentations/' . $presentationId . '/attachments');
        $downloadBase = 'scholarly/attachments';
        $canManage = true;
        ?>
        <article class="scholarly-record" id="presentation-<?= $presentationId ?>">
            <div class="scholarly-record-head">
                <h3><?= $e($presentation['title']) ?></h3>
                <span class="scholarly-status"><?= $e($typeLabels[$type] ?? $type) ?></span>
            </div>
            <p class="scholarly-meta">
                <em><?= $e($presentation['conference_name']) ?></em><?= $dateText !== '' ? ', ' . $e($dateText) : '' ?><?= !empty($presentation['location']) ? ', ' . $e($presentation['location']) : '' ?>
                <?php if ((int) $presentation['is_international'] === 1): ?><span class="scholarly-tag">International</span><?php endif; ?>
            </p>
            <?php if (!empty($presentation['notes'])): ?>
                <p class="scholarly-notes"><?= nl2br($e($presentation['notes'])) ?></p>
            <?php endif; ?>

            <div class="scholarly-docs">
                <span class="scholarly-docs-label">Supporting documents</span>
                <?php if (empty($attachments)): ?><span class="muted">None yet</span><?php endif; ?>
                <?php include __DIR__ . '/_attachments.php'; ?>
            </div>

            <div class="scholarly-record-actions">
                <button type="button" class="btn btn-outline btn-sm" data-scholarly-edit="presentation-edit-<?= $presentationId ?>" aria-expanded="false" aria-controls="presentation-edit-<?= $presentationId ?>">Edit</button>
                <form method="post" action="<?= base_url('scholarly/presentations/' . $presentationId . '/delete') ?>" onsubmit="return confirm('Remove this presentation and its supporting documents?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                </form>
            </div>

            <form class="scholarly-edit-form" id="presentation-edit-<?= $presentationId ?>" method="post" action="<?= base_url('scholarly/presentations/' . $presentationId . '/update') ?>" enctype="multipart/form-data" data-capitalize="off" hidden>
                <?= csrf_field() ?>
                <h4>Edit presentation</h4>
                <?php $presentationFields($presentation, 'presentation_' . $presentationId); ?>
                <label for="presentation_<?= $presentationId ?>_documents">Add more supporting documents</label>
                <input id="presentation_<?= $presentationId ?>_documents" type="file" name="supporting_documents[]" multiple accept="<?= $fileAccept ?>">
                <p class="muted scholarly-hint">Files already attached are kept.</p>
                <div class="actions">
                    <button type="submit" class="btn btn-accent btn-sm">Save Changes</button>
                    <button type="button" class="btn btn-outline btn-sm" data-scholarly-edit="presentation-edit-<?= $presentationId ?>">Cancel</button>
                </div>
            </form>
        </article>
    <?php endforeach; ?>
</div>

<script>
(() => {
    document.querySelectorAll('[data-scholarly-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            const form = document.getElementById(button.dataset.scholarlyEdit);
            if (!form) {
                return;
            }
            form.hidden = !form.hidden;
            document.querySelectorAll(`[aria-controls="${form.id}"]`).forEach((toggle) => {
                toggle.setAttribute('aria-expanded', form.hidden ? 'false' : 'true');
            });
            if (!form.hidden) {
                form.querySelector('input:not([type="hidden"])')?.focus();
            }
        });
    });
})();
</script>
