<?php
/** @var list<array> $proposals */
/** @var int $currentUserId */
/** @var string $selectedFormType */
$selectedFormType = $selectedFormType ?? '';
$isProgressRegistry = $selectedFormType === 'progress_report';
$isAssessmentRegistry = $selectedFormType === 'terminal_report_assessment_form';
$pageTitle = (
    $isProgressRegistry
        ? 'Progress Report Registry'
        : ($isAssessmentRegistry ? 'Terminal Report Assessment Registry' : 'Terminal Report Registry')
) . ' — RIDE IMS';
$pageHeading = $isProgressRegistry
    ? 'Progress Report Registry'
    : ($isAssessmentRegistry ? 'Terminal Report Assessment Registry' : 'Terminal Report Registry');
$pageSubtitle = $isProgressRegistry
    ? 'List of all progress report records.'
    : ($isAssessmentRegistry
        ? 'List of all terminal report assessment form records.'
        : 'List of all terminal report records.');
?>

<div class="card list-card">
    <section>
        <div class="list-card-head">
            <div>
                <h2><?= htmlspecialchars($pageHeading) ?></h2>
                <p>Total records: <?= count($proposals) ?></p>
            </div>
        </div>

        <?php if (empty($proposals)): ?>
            <div class="proposal-empty-state">
                <p>
                    <?= htmlspecialchars(
                        $isAssessmentRegistry
                            ? 'No terminal report assessment form records found.'
                            : ($isProgressRegistry
                                ? 'No progress report records found.'
                                : 'No terminal report records found.')
                    ) ?>
                </p>
            </div>
        <?php else: ?>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Lead Researcher</th>
                            <th>Status</th>
                            <th>Current Step</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($proposals as $p): ?>
                        <tr>
                            <td><code class="proposal-registry-code"><?= htmlspecialchars((string) ($p['project_code'] ?? '')) ?></code></td>
                            <td><?= htmlspecialchars($p['title']) ?></td>
                            <td><?= htmlspecialchars(ucfirst((string) $p['project_type'])) ?></td>
                            <td><?= htmlspecialchars(trim((string) (($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')))) ?></td>
                            <td><span class="badge badge-<?= htmlspecialchars($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></span></td>
                            <td><?= htmlspecialchars(str_replace('_', ' ', $p['current_step'] ?? '—')) ?></td>
                            <td>
                                <div class="proposal-row-actions">
                                    <a class="btn btn-sm" href="<?= base_url('proposals/' . $p['id']) ?>">Open</a>
                                    <?php
                                    $canDelete = \App\Core\Auth::hasRole('faculty')
                                        && (int) ($p['user_id'] ?? 0) === $currentUserId
                                        && in_array((string) ($p['status'] ?? ''), ['draft', 'returned'], true);
                                    ?>
                                    <?php if ($canDelete): ?>
                                        <form method="post" action="<?= base_url('proposals/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('Delete this proposal?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-danger proposal-delete-icon-btn" title="Delete proposal" aria-label="Delete proposal">
                                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
