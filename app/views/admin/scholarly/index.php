<?php
/** @var list<array<string, mixed>> $colleges */
/** @var int|null $collegeId */
/** @var list<array<string, mixed>> $faculty */
/** @var array{total: int, this_year: int, faculty_count: int} $paperStats */
/** @var array{total: int, this_year: int, international: int, faculty_count: int} $presentationStats */
/** @var list<array<string, mixed>> $papers */
/** @var list<array<string, mixed>> $presentations */
/** @var list<array<string, mixed>> $facultySummary */
/** @var array<int, list<array<string, mixed>>> $paperAttachments */
/** @var array<int, list<array<string, mixed>>> $presentationAttachments */

$pageTitle = 'Faculty Scholarly Output | RIDE';
$pageHeading = 'Published Papers & Presentations';
$pageSubtitle = 'Monitor published papers and paper presentations submitted by faculty university-wide.';
?>

<form method="get" action="<?= base_url('admin/scholarly') ?>" class="card" style="margin-bottom: 1.5rem;">
    <h2>Filter by College</h2>
    <div class="grid grid-2">
        <div>
            <label for="college_id">College</label>
            <select id="college_id" name="college_id" onchange="this.form.submit()">
                <option value="">All colleges</option>
                <?php foreach ($colleges as $college): ?>
                    <option value="<?= (int) $college['id'] ?>" <?= $collegeId === (int) $college['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $college['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<div class="grid grid-4" style="margin-bottom: 1.5rem;">
    <div class="card stat">
        <div class="value"><?= (int) $paperStats['total'] ?></div>
        <div class="label">Published Papers</div>
    </div>
    <div class="card stat">
        <div class="value"><?= (int) $paperStats['this_year'] ?></div>
        <div class="label">Papers This Year</div>
    </div>
    <div class="card stat">
        <div class="value"><?= (int) $presentationStats['total'] ?></div>
        <div class="label">Paper Presentations</div>
    </div>
    <div class="card stat">
        <div class="value"><?= (int) $presentationStats['international'] ?></div>
        <div class="label">International Presentations</div>
    </div>
</div>

<?php if (!empty($facultySummary)): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <h2>Faculty Summary</h2>
    <p class="muted">Faculty with at least one published paper or paper presentation<?= $collegeId ? ' in the selected college' : '' ?>.</p>
    <div class="proposal-table-wrap">
        <table class="proposal-table">
            <thead>
                <tr>
                    <th>Faculty</th>
                    <th>College</th>
                    <th>Program</th>
                    <th>Papers</th>
                    <th>Presentations</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($facultySummary as $row): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                        <span class="muted"><?= htmlspecialchars((string) $row['email']) ?></span>
                    </td>
                    <td><?= htmlspecialchars((string) $row['college_name']) ?></td>
                    <td><?= htmlspecialchars((string) ($row['program'] ?: '—')) ?></td>
                    <td><?= (int) $row['papers'] ?></td>
                    <td><?= (int) $row['presentations'] ?></td>
                    <td><?= (int) $row['papers'] + (int) $row['presentations'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-2">
    <div class="card">
        <h2>Record Published Paper</h2>
        <p class="muted">Add a published paper on behalf of a faculty member.</p>
        <form method="post" action="<?= base_url('admin/scholarly/papers') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <label for="paper_user_id">Faculty</label>
            <select id="paper_user_id" name="user_id" required>
                <option value="">Select faculty</option>
                <?php foreach ($faculty as $member): ?>
                    <option value="<?= (int) $member['id'] ?>">
                        <?= htmlspecialchars(trim($member['first_name'] . ' ' . $member['last_name'])) ?>
                        — <?= htmlspecialchars((string) ($member['college_name'] ?? 'No college')) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="paper_title">Paper Title</label>
            <input id="paper_title" name="title" required maxlength="500">

            <label for="paper_authors">Co-authors</label>
            <input id="paper_authors" name="authors" maxlength="500" placeholder="Optional co-author list">

            <label for="paper_journal">Journal Name</label>
            <input id="paper_journal" name="journal_name" required maxlength="255">

            <label for="paper_date">Publication Date</label>
            <input id="paper_date" type="date" name="publication_date">

            <label for="paper_year">Publication Year</label>
            <input id="paper_year" type="number" name="publication_year" min="1900" max="2100" value="<?= (int) date('Y') ?>">

            <label for="paper_status">Status</label>
            <select id="paper_status" name="status">
                <option value="published">Published</option>
                <option value="accepted">Accepted</option>
                <option value="in_press">In Press</option>
            </select>

            <label for="paper_indexing">Indexing</label>
            <input id="paper_indexing" name="indexing" maxlength="120" placeholder="e.g. Scopus, Web of Science">

            <label for="paper_doi">DOI</label>
            <input id="paper_doi" name="doi" maxlength="120">

            <label for="paper_link">Link</label>
            <input id="paper_link" type="url" name="link" maxlength="500" placeholder="https://">

            <label for="paper_supporting_documents">Supporting Documents</label>
            <input id="paper_supporting_documents" type="file" name="supporting_documents[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
            <p class="muted" style="margin-top: 0.25rem;">Optional. PDF, Word, Excel, images, or ZIP; max 10 MB each.</p>

            <div class="actions">
                <button type="submit" class="btn btn-accent">Save Paper</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Record Paper Presentation</h2>
        <p class="muted">Add a conference presentation on behalf of a faculty member.</p>
        <form method="post" action="<?= base_url('admin/scholarly/presentations') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <label for="presentation_user_id">Faculty</label>
            <select id="presentation_user_id" name="user_id" required>
                <option value="">Select faculty</option>
                <?php foreach ($faculty as $member): ?>
                    <option value="<?= (int) $member['id'] ?>">
                        <?= htmlspecialchars(trim($member['first_name'] . ' ' . $member['last_name'])) ?>
                        — <?= htmlspecialchars((string) ($member['college_name'] ?? 'No college')) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="presentation_title">Presentation Title</label>
            <input id="presentation_title" name="title" required maxlength="500">

            <label for="presentation_conference">Conference / Event</label>
            <input id="presentation_conference" name="conference_name" required maxlength="255">

            <label for="presentation_type">Type</label>
            <select id="presentation_type" name="presentation_type">
                <option value="oral">Oral</option>
                <option value="poster">Poster</option>
                <option value="virtual">Virtual</option>
                <option value="other">Other</option>
            </select>

            <label for="presentation_date">Presentation Date</label>
            <input id="presentation_date" type="date" name="presentation_date">

            <label for="presentation_location">Location</label>
            <input id="presentation_location" name="location" maxlength="255" placeholder="City, country, or venue">

            <label class="proposal-checkbox-row">
                <input type="checkbox" name="is_international" value="1">
                International presentation
            </label>

            <label for="presentation_supporting_documents">Supporting Documents</label>
            <input id="presentation_supporting_documents" type="file" name="supporting_documents[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
            <p class="muted" style="margin-top: 0.25rem;">Optional. PDF, Word, Excel, images, or ZIP; max 10 MB each.</p>

            <div class="actions">
                <button type="submit" class="btn btn-accent">Save Presentation</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top: 1.5rem;">
    <h2>All Published Papers</h2>
    <?php if (empty($papers)): ?>
        <p class="muted">No published papers recorded yet.</p>
    <?php else: ?>
        <div class="proposal-table-wrap">
            <table class="proposal-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Faculty</th>
                        <th>College</th>
                        <th>Journal</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Documents</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($papers as $paper): ?>
                    <?php
                    $attachments = $paperAttachments[(int) $paper['id']] ?? [];
                    $downloadBase = 'admin/scholarly/attachments';
                    $canManage = false;
                    $uploadAction = null;
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string) $paper['title']) ?></strong>
                            <?php if (!empty($paper['indexing'])): ?>
                                <br><span class="muted"><?= htmlspecialchars((string) $paper['indexing']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(trim($paper['first_name'] . ' ' . $paper['last_name'])) ?></td>
                        <td><?= htmlspecialchars((string) ($paper['college_name'] ?? '—')) ?></td>
                        <td><?= htmlspecialchars((string) $paper['journal_name']) ?></td>
                        <td><?= htmlspecialchars((string) ($paper['publication_year'] ?? ($paper['publication_date'] ? substr((string) $paper['publication_date'], 0, 4) : '—'))) ?></td>
                        <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $paper['status']))) ?></td>
                        <td>
                            <?php if (empty($attachments)): ?>
                                <span class="muted">None</span>
                            <?php else: ?>
                                <?php include dirname(__DIR__, 2) . '/scholarly/_attachments.php'; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="<?= base_url('admin/scholarly/papers/' . (int) $paper['id'] . '/delete') ?>" onsubmit="return confirm('Remove this published paper?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card" style="margin-top: 1.5rem;">
    <h2>All Paper Presentations</h2>
    <?php if (empty($presentations)): ?>
        <p class="muted">No paper presentations recorded yet.</p>
    <?php else: ?>
        <div class="proposal-table-wrap">
            <table class="proposal-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Faculty</th>
                        <th>College</th>
                        <th>Conference</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Documents</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($presentations as $presentation): ?>
                    <?php
                    $attachments = $presentationAttachments[(int) $presentation['id']] ?? [];
                    $downloadBase = 'admin/scholarly/attachments';
                    $canManage = false;
                    $uploadAction = null;
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string) $presentation['title']) ?></strong>
                            <?php if ((int) $presentation['is_international'] === 1): ?>
                                <br><span class="muted">International</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(trim($presentation['first_name'] . ' ' . $presentation['last_name'])) ?></td>
                        <td><?= htmlspecialchars((string) ($presentation['college_name'] ?? '—')) ?></td>
                        <td><?= htmlspecialchars((string) $presentation['conference_name']) ?></td>
                        <td><?= htmlspecialchars((string) ($presentation['presentation_date'] ?? '—')) ?></td>
                        <td><?= htmlspecialchars(ucfirst((string) $presentation['presentation_type'])) ?></td>
                        <td>
                            <?php if (empty($attachments)): ?>
                                <span class="muted">None</span>
                            <?php else: ?>
                                <?php include dirname(__DIR__, 2) . '/scholarly/_attachments.php'; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="<?= base_url('admin/scholarly/presentations/' . (int) $presentation['id'] . '/delete') ?>" onsubmit="return confirm('Remove this presentation?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
