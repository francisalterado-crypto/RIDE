<?php
$pageTitle = 'Ongoing Projects | RIDE';
$pageHeading = 'Ongoing Projects';
$pageSubtitle = 'Approved projects under monitoring (milestones, reports, documents).';
?>

<div class="card list-card">
    <?php if (empty($projects)): ?>
        <p>No ongoing projects yet. Projects appear here after final approval.</p>
    <?php else: ?>
    <div class="data-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Title</th>
                <th>Type</th>
                <th>Leader</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($projects as $p): ?>
            <tr>
                <td><code class="proposal-registry-code"><?= htmlspecialchars((string) ($p['project_code'] ?? '')) ?></code></td>
                <td><a href="<?= base_url('projects/' . $p['id']) ?>"><?= htmlspecialchars($p['title']) ?></a></td>
                <td><?= htmlspecialchars(ucfirst((string) $p['project_type'])) ?></td>
                <td><?= htmlspecialchars(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')) ?></td>
                <td><span class="badge badge-<?= htmlspecialchars($p['status']) ?>"><?= htmlspecialchars(status_label((string) $p['status'])) ?></span></td>
                <td><a class="btn btn-sm" href="<?= base_url('projects/' . $p['id']) ?>">Monitor</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
