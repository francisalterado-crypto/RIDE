<?php
$pageTitle = (string) $project['title'] . ' | RIDE';
$pageHeading = htmlspecialchars($project['title']);
$pageSubtitle = 'Code: ' . htmlspecialchars($project['project_code'] ?? '—')
    . ' · ' . status_label((string) $project['status']);
$tabs = ['overview' => 'Overview', 'milestones' => 'Milestones', 'reports' => 'Reports', 'documents' => 'Documents'];
if ($innovation) $tabs['innovation'] = 'Innovation';
if ($extension) $tabs['extension'] = 'Extension';
?>
<?php if ($canManage && $project['status'] === 'ongoing'): ?>
<div class="page-actions-bar">
    <form method="post" action="<?= base_url('projects/' . $project['id'] . '/complete') ?>" onsubmit="return confirm('Mark project as completed?');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline">Mark Completed</button>
    </form>
</div>
<?php endif; ?>

<nav class="tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="tab <?= $tab === $key ? 'active' : '' ?>" href="<?= base_url('projects/' . $project['id'] . '?tab=' . $key) ?>"><?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($tab === 'overview'): ?>
<div class="grid grid-2">
    <div class="card"><h3>Milestones</h3><p><?= count($milestones) ?> total, <?= count(array_filter($milestones, fn($m) => $m['status'] === 'overdue')) ?> overdue</p></div>
    <div class="card"><h3>Reports</h3><p><?= count($reports) ?> submitted</p></div>
    <div class="card"><h3>Documents</h3><p><?= count($documents) ?> files</p></div>
    <?php if ($innovation): ?>
    <div class="card"><h3>Innovation</h3>
        <p><?= (int) $innovation['summary']['patents'] ?> patents, <?= (int) $innovation['summary']['prototypes'] ?> prototypes</p>
    </div>
    <?php endif; ?>
    <?php if ($extension): ?>
    <div class="card"><h3>Extension Impact</h3>
        <p><?= (int) $extension['summary']['total_beneficiaries'] ?> beneficiaries, <?= (int) $extension['summary']['people_trained'] ?> trained</p>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($tab === 'milestones'): ?>
<div class="card">
    <h3>Milestones</h3>
    <?php if ($canManage): ?>
    <form method="post" action="<?= base_url('projects/' . $project['id'] . '/milestones') ?>" class="inline-form">
        <?= csrf_field() ?>
        <input name="title" placeholder="Milestone title" required>
        <input type="date" name="due_date" required value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
        <button type="submit" class="btn btn-sm">Add</button>
    </form>
    <?php endif; ?>
    <table style="margin-top:1rem;">
        <thead><tr><th>Title</th><th>Due</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($milestones as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['title']) ?></td>
                <td><?= htmlspecialchars($m['due_date']) ?></td>
                <td><span class="badge badge-<?= $m['status'] === 'overdue' ? 'suspended' : ($m['status'] === 'completed' ? 'completed' : 'draft') ?>"><?= htmlspecialchars($m['status']) ?></span></td>
                <td>
                    <?php if ($canManage && $m['status'] !== 'completed'): ?>
                    <form method="post" action="<?= base_url('projects/' . $project['id'] . '/milestones/' . $m['id'] . '/complete') ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-accent">Complete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($tab === 'reports'): ?>
<div class="card">
    <div style="display:flex;justify-content:space-between;">
        <h3>Progress Reports</h3>
        <?php if ($canManage): ?>
            <a class="btn btn-sm btn-accent" href="<?= base_url('projects/' . $project['id'] . '/reports/create') ?>">New Report</a>
        <?php endif; ?>
    </div>
    <table>
        <thead><tr><th>Period</th><th>Type</th><th>Status</th><th>Due</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($reports as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['period_label']) ?></td>
                <td><?= htmlspecialchars($r['report_type']) ?></td>
                <td><?= htmlspecialchars($r['status']) ?></td>
                <td><?= htmlspecialchars($r['due_date'] ?? '—') ?></td>
                <td><a class="btn btn-sm" href="<?= base_url('projects/' . $project['id'] . '/reports/' . $r['id']) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($tab === 'documents'): ?>
<div class="card">
    <h3>Document Repository</h3>
    <?php
    $documentCategories = [
        'completed_researches' => 'WPU-QSF-RIDE-RDO Completed Researches',
        'ongoing_researches' => 'WPU-QSF-RIDE-RDO Ongoing Researches',
        'research_output_published' => 'WPU-QSF-RIDE-RDO Research Output Published',
        'research_output_presented' => 'WPU-QSF-RIDE-RDO Research Output Presented',
        'commercialized' => 'WPU-QSF-RIDE-RDO Commercialized',
        'resulted_in_extension' => 'WPU-QSF-RIDE-RDO Resulted in Extension',
        'journal_citation' => 'WPU-QSF-RIDE-RDO Journal Citation',
        'book_citation' => 'WPU-QSF-RIDE-RDO Book Citation',
        'inventions_um_copyrights' => 'WPU-QSF-RIDE-RDO Inventions, UM, Copyrights',
        'linkages' => 'WPU-QSF-RIDE-RDO Linkages',
        'consolidated_linkages' => 'WPU-QSF-RIDE-RDO Consolidated Linkages',
        'consolidated_completed_researches' => 'WPU-QSF-RIDE-RDO Consolidated Completed Researches',
        'consolidated_ongoing_researches' => 'WPU-QSF-RIDE-RDO Consolidated Ongoing Researches',
        'consolidated_research_output_published' => 'WPU-QSF-RIDE-RDO Consolidated Research Output Published',
        'consolidated_research_output_presented' => 'WPU-QSF-RIDE-RDO Consolidated Research Output Presented',
        'consolidated_commercialized' => 'WPU-QSF-RIDE-RDO Consolidated Commercialized',
        'consolidated_resulted_in_extension' => 'WPU-QSF-RIDE-RDO Consolidated Resulted in Extension',
        'consolidated_journal_citation' => 'WPU-QSF-RIDE-RDO Consolidated Journal Citation',
        'consolidated_book_citation' => 'WPU-QSF-RIDE-RDO Consolidated Book Citation',
        'consolidated_inventions_um_copyrights' => 'WPU-QSF-RIDE-RDO Consolidated Inventions, UM, Copyrights',
        'progress_report' => 'WPU-QSF-RIDE-RDO Progress Report',
        'terminal_report' => 'WPU-QSF-RIDE-RDO Terminal Report',
        'terminal_report_assessment_form' => 'WPU-QSF-RIDE-RDO Terminal Report Assessment Form',
        'obr_matrix' => 'WPU-QSF-RIDE-RDO OBR Matrix',
        'trainings_conducted' => 'WPU-QSF-RIDE-ESO Trainings Conducted',
        'technical_advisory' => 'WPU-QSF-RIDE-ESO Technical Advisory',
        'extension_linkages' => 'WPU-QSF-RIDE-ESO Extension Linkages',
        'outreach_activities' => 'WPU-QSF-RIDE-ESO Outreach Activities',
        'technology_adoption' => 'WPU-QSF-RIDE-ESO Technology Adoption',
        'accomplishment_report' => 'WPU-QSF-RIDE-ESO Accomplishment Report',
        'technical_advisory_ar' => 'WPU-QSF-RIDE-ESO Technical Advisory AR',
        'outreach_activities_ar' => 'WPU-QSF-RIDE-ESO Outreach Activities AR',
        'ebalwasyon_ng_gawain' => 'WPU-QSF-RIDE-ESO Ebalwasyon ng Gawain',
        'attendance_sheet' => 'WPU-QSF-RIDE-ESO Attendance Sheet',
    ];
    $selectedDocumentCategory = $selectedDocumentCategory ?? 'completed_researches';
    ?>
    <?php if ($canManage): ?>
    <form method="post" action="<?= base_url('projects/' . $project['id'] . '/documents') ?>" enctype="multipart/form-data" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="category" value="<?= htmlspecialchars($selectedDocumentCategory) ?>">
        <span class="muted">Upload to: <?= htmlspecialchars($documentCategories[$selectedDocumentCategory] ?? 'Document Repository') ?></span>
        <input type="file" name="document" required>
        <button type="submit" class="btn btn-sm">Upload</button>
    </form>
    <?php endif; ?>
    <form method="get" class="inline-form" style="margin-top: .75rem;">
        <input type="hidden" name="tab" value="documents">
        <select name="document_category" onchange="this.form.submit()">
            <?php foreach ($documentCategories as $categoryKey => $categoryLabel): ?>
                <option value="<?= htmlspecialchars($categoryKey) ?>" <?= $selectedDocumentCategory === $categoryKey ? 'selected' : '' ?>>
                    <?= htmlspecialchars($categoryLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="btn btn-sm">Show Files</button></noscript>
    </form>
    <table style="margin-top:1rem;">
        <thead><tr><th>Name</th><th>Category</th><th>Uploaded</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($documents)): ?>
            <tr><td colspan="4" class="muted">No files found for the selected document type.</td></tr>
        <?php endif; ?>
        <?php foreach ($documents as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['original_name']) ?></td>
                <td><?= htmlspecialchars($documentCategories[$d['category']] ?? (string) $d['category']) ?></td>
                <td><?= htmlspecialchars($d['created_at']) ?></td>
                <td>
                    <a class="btn btn-sm" href="<?= base_url('projects/' . $project['id'] . '/documents/' . $d['id']) ?>">Download</a>
                    <?php if ($canManage): ?>
                        <form method="post" action="<?= base_url('projects/' . $project['id'] . '/documents/' . $d['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this document?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($tab === 'innovation' && $innovation): ?>
<div class="card">
    <h3>Innovation Records</h3>
    <?php if ($canManage): include __DIR__ . '/_innovation-form.php'; endif; ?>
</div>
<?php
    $sections = [
        'ip_disclosures' => 'IP Disclosures',
        'patents' => 'Patents',
        'technology_transfers' => 'Technology Transfers',
        'prototypes' => 'Prototypes',
    ];
    foreach ($sections as $key => $label):
?>
<div class="card">
    <h4><?= htmlspecialchars($label) ?></h4>
    <?php if (empty($innovation[$key])): ?><p class="muted">None yet.</p><?php else: ?>
    <ul>
        <?php foreach ($innovation[$key] as $row): ?>
            <li><?= htmlspecialchars($row['title'] ?? $row['name'] ?? $row['partner_name']) ?>
                <?php if (!empty($row['status'])): ?> (<?= htmlspecialchars($row['status']) ?>)<?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?php endforeach; endif; ?>

<?php if ($tab === 'extension' && $extension): ?>
<div class="card">
    <h3>Extension Impact</h3>
    <p>Total beneficiaries: <strong><?= (int) $extension['summary']['total_beneficiaries'] ?></strong>,
       People trained: <strong><?= (int) $extension['summary']['people_trained'] ?></strong></p>
    <?php if ($canManageExtension): include __DIR__ . '/_extension-form.php'; endif; ?>
</div>
<?php
    $extSections = [
        'beneficiaries' => ['community_beneficiaries', 'Community Beneficiaries', 'group_name'],
        'mous' => ['partner_mous', 'Partner MOUs', 'partner_name'],
        'impacts' => ['impact_metrics', 'Impact Metrics', 'period_year'],
    ];
    foreach ($extSections as $listKey => [$table, $label, $field]):
?>
<div class="card">
    <h4><?= htmlspecialchars($label) ?></h4>
    <?php $items = $extension[$listKey]; ?>
    <?php if (empty($items)): ?><p class="muted">None yet.</p><?php else: ?>
    <ul>
        <?php foreach ($items as $row): ?>
            <li>
                <?php if ($listKey === 'beneficiaries'): ?>
                    <?= htmlspecialchars($row['group_name']) ?> — <?= (int) $row['beneficiary_count'] ?> people (<?= (int) $row['period_year'] ?>)
                <?php elseif ($listKey === 'mous'): ?>
                    <?= htmlspecialchars($row['partner_name']) ?>
                <?php else: ?>
                    Year <?= (int) $row['period_year'] ?>: <?= (int) $row['people_trained'] ?> trained, <?= number_format((float) $row['income_generated'], 2) ?> income
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?php endforeach; endif; ?>
