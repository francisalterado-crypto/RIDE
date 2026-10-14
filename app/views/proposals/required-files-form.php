<?php
/** @var array|null $proposal */
/** @var array<string, string> $requiredFileList */
/** @var array<string, list<array<string, mixed>>> $requiredDocuments */

$hasProposal = $proposal !== null;
$pageTitle = 'Required File List | RIDE';
$pageHeading = 'Required File List';
$pageSubtitle = $hasProposal
    ? 'Upload all required supporting documents for your research proposal.'
    : 'Save your research proposal from Applicant\'s Information first, then return here to upload required files.';
?>

<?php if (!$hasProposal): ?>
    <section class="proposal-section proposal-paper">
        <p class="proposal-section-note">You need a saved research proposal draft before you can upload required files.</p>
        <div class="actions proposal-form-actions">
            <a class="btn" href="<?= base_url('proposals/create') ?>">Go to Applicant&apos;s Information</a>
            <a class="btn btn-outline" href="<?= base_url('proposals') ?>">View Proposals</a>
        </div>
    </section>
<?php else: ?>
    <form class="proposal-paper" method="post" enctype="multipart/form-data" action="<?= base_url('proposals/' . $proposal['id'] . '/required-files') ?>">
        <?= csrf_field() ?>

        <section class="proposal-section">
            <h2 class="proposal-section-title">Required File List</h2>
            <p class="proposal-section-note">
                Proposal: <?= htmlspecialchars((string) $proposal['title']) ?>
            </p>

            <table class="proposal-table">
                <tr>
                    <th style="width: 50%;">File Name</th>
                    <th style="width: 25%;">Upload File</th>
                    <th style="width: 25%;">Uploaded File</th>
                </tr>
                <?php foreach ($requiredFileList as $fileKey => $fileName): ?>
                    <?php $documents = is_array($requiredDocuments[$fileKey] ?? null) ? $requiredDocuments[$fileKey] : []; ?>
                    <tr>
                        <td><?= htmlspecialchars($fileName) ?></td>
                        <td>
                            <input type="file" name="required_files[<?= htmlspecialchars($fileKey) ?>]">
                        </td>
                        <td>
                            <?php if ($documents === []): ?>
                                <span class="proposal-section-note-inline">No file uploaded yet.</span>
                            <?php else: ?>
                                <?php foreach ($documents as $document): ?>
                                    <div>
                                        <a href="<?= base_url('projects/' . (int) $proposal['id'] . '/documents/' . (int) $document['id']) ?>">
                                            <?= htmlspecialchars((string) $document['original_name']) ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p class="proposal-section-note">Supported formats: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, ZIP (max 10MB each).</p>
        </section>

        <div class="actions proposal-form-actions">
            <button type="submit" class="btn">Save Files</button>
            <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id']) ?>">Back to Proposal</a>
            <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Applicant&apos;s Information</a>
        </div>
    </form>
<?php endif; ?>
