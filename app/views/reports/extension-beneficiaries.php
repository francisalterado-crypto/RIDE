<?php
$years = (int) ($years ?? 3);
$proposalDetails = isset($proposalDetails) && is_array($proposalDetails) ? $proposalDetails : [];
$proposalFilters = isset($proposalFilters) && is_array($proposalFilters) ? $proposalFilters : [];
$proposalFilterOptions = isset($proposalFilterOptions) && is_array($proposalFilterOptions) ? $proposalFilterOptions : [];
$selectedFormType = (string) ($proposalFilters['form_type'] ?? '');
$selectedProjectType = (string) ($proposalFilters['project_type'] ?? '');
$selectedStatus = (string) ($proposalFilters['status'] ?? '');
$selectedCollege = (string) ($proposalFilters['college'] ?? '');
$searchQuery = (string) ($proposalFilters['q'] ?? '');
$sortBy = (string) ($proposalFilters['sort_by'] ?? 'updated_at');
$sortDir = (string) ($proposalFilters['sort_dir'] ?? 'desc');
$formTypeOptions = is_array($proposalFilterOptions['form_types'] ?? null) ? $proposalFilterOptions['form_types'] : [];
$projectTypeOptions = is_array($proposalFilterOptions['project_types'] ?? null) ? $proposalFilterOptions['project_types'] : [];
$statusOptions = is_array($proposalFilterOptions['statuses'] ?? null) ? $proposalFilterOptions['statuses'] : [];
$collegeOptions = is_array($proposalFilterOptions['colleges'] ?? null) ? $proposalFilterOptions['colleges'] : [];
$csvExportUrl = base_url('reports/extension-beneficiaries?' . http_build_query([
    'years' => $years,
    'form_type' => $selectedFormType,
    'project_type' => $selectedProjectType,
    'status' => $selectedStatus,
    'college' => $selectedCollege,
    'q' => $searchQuery,
    'sort_by' => $sortBy,
    'sort_dir' => $sortDir,
    'export' => 'csv',
]));
$sortLink = static function (string $column) use ($years, $selectedFormType, $selectedProjectType, $selectedStatus, $selectedCollege, $searchQuery, $sortBy, $sortDir): string {
    $nextDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';
    return base_url('reports/extension-beneficiaries?' . http_build_query([
        'years' => $years,
        'form_type' => $selectedFormType,
        'project_type' => $selectedProjectType,
        'status' => $selectedStatus,
        'college' => $selectedCollege,
        'q' => $searchQuery,
        'sort_by' => $column,
        'sort_dir' => $nextDir,
    ]));
};
$sortIndicator = static function (string $column) use ($sortBy, $sortDir): string {
    if ($sortBy !== $column) {
        return '';
    }

    return ' <i class="fas fa-sort-' . ($sortDir === 'asc' ? 'up' : 'down') . '" aria-hidden="true"></i>';
};

$pageTitle = 'Reports | RIDE Hub';
$pageHeading = 'Reports';
$pageSubtitle = 'Extension beneficiaries for accreditation, and every proposal form on file.';
// Same form names as the Proposals and Monitoring pages.
$formLabelOf = static fn (string $formType, string $projectType = ''): string => proposal_form_label(['summary' => json_encode(['form_type' => $formType]), 'project_type' => $projectType]);
?>


<section class="card list-card">
    <div class="list-card-head">
        <div>
            <h2>Extension Beneficiaries</h2>
            <p>Accreditation report: extension projects with beneficiary counts for the past <?= $years ?> year<?= $years > 1 ? 's' : '' ?>.</p>
        </div>
        <form method="get" class="list-filters" action="<?= base_url('reports/extension-beneficiaries') ?>">
            <select name="years" aria-label="Period" onchange="this.form.submit()">
                <?php foreach ([1, 2, 3, 5] as $y): ?>
                    <option value="<?= $y ?>" <?= $years === $y ? 'selected' : '' ?>>Past <?= $y ?> year<?= $y > 1 ? 's' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <p>No extension beneficiary data for this period.</p>
        </div>
    <?php else: ?>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>College</th>
                        <th>Year</th>
                        <th>Beneficiaries</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?php if (!empty($r['project_code'])): ?><code class="proposal-registry-code"><?= htmlspecialchars((string) $r['project_code']) ?></code><?php endif; ?></td>
                        <td><?= htmlspecialchars((string) $r['title']) ?></td>
                        <td><?= htmlspecialchars((string) $r['college_name']) ?></td>
                        <td><?= (int) $r['period_year'] ?></td>
                        <td><?= (int) $r['total_beneficiaries'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card list-card">
    <div class="list-card-head">
        <div>
            <h2>All Proposal Form Details</h2>
            <p><?= count($proposalDetails) ?> record<?= count($proposalDetails) === 1 ? '' : 's' ?> from research, extension and report forms.</p>
        </div>
        <a class="btn btn-sm btn-outline" href="<?= htmlspecialchars($csvExportUrl) ?>"><i class="fas fa-download" aria-hidden="true"></i> Export CSV</a>
    </div>
    <form method="get" class="list-filters list-filters--block" action="<?= base_url('reports/extension-beneficiaries') ?>">
        <input type="hidden" name="years" value="<?= $years ?>">
        <input type="search" name="q" class="no-capitalize" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search code, title, lead" aria-label="Search">
        <select name="form_type" aria-label="Filter by form">
            <option value="">All forms</option>
            <?php foreach ($formTypeOptions as $option): ?>
                <option value="<?= htmlspecialchars((string) $option) ?>" <?= $selectedFormType === (string) $option ? 'selected' : '' ?>><?= htmlspecialchars($formLabelOf((string) $option)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="project_type" aria-label="Filter by track">
            <option value="">All tracks</option>
            <?php foreach ($projectTypeOptions as $option): ?>
                <option value="<?= htmlspecialchars((string) $option) ?>" <?= $selectedProjectType === (string) $option ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst((string) $option)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" aria-label="Filter by status">
            <option value="">All statuses</option>
            <?php foreach ($statusOptions as $option): ?>
                <option value="<?= htmlspecialchars((string) $option) ?>" <?= $selectedStatus === (string) $option ? 'selected' : '' ?>><?= htmlspecialchars(status_label((string) $option)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="college" aria-label="Filter by college">
            <option value="">All colleges</option>
            <?php foreach ($collegeOptions as $option): ?>
                <option value="<?= htmlspecialchars((string) $option) ?>" <?= $selectedCollege === (string) $option ? 'selected' : '' ?>><?= htmlspecialchars((string) $option) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-accent">Apply</button>
        <a href="<?= base_url('reports/extension-beneficiaries?years=' . $years) ?>" class="btn btn-sm btn-outline">Clear</a>
    </form>
    <?php if (empty($proposalDetails)): ?>
        <div class="empty-state">
            <p>No proposal records match the selected filters.</p>
        </div>
    <?php else: ?>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th><a class="sort-link" href="<?= htmlspecialchars($sortLink('form_type')) ?>">Form<?= $sortIndicator('form_type') ?></a></th>
                        <th>Track</th>
                        <th><a class="sort-link" href="<?= htmlspecialchars($sortLink('college_name')) ?>">College<?= $sortIndicator('college_name') ?></a></th>
                        <th>Lead</th>
                        <th><a class="sort-link" href="<?= htmlspecialchars($sortLink('status')) ?>">Status<?= $sortIndicator('status') ?></a></th>
                        <th>Waiting on</th>
                        <th><a class="sort-link" href="<?= htmlspecialchars($sortLink('updated_at')) ?>">Updated<?= $sortIndicator('updated_at') ?></a></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($proposalDetails as $item): ?>
                    <?php
                    $rawFormType = (string) ($item['form_type'] ?? '');
                    $status = (string) ($item['status'] ?? '');
                    $stepValue = (string) ($item['current_step'] ?? '');
                    $asOf = (string) ($item['report_as_of'] ?? '');
                    ?>
                    <tr>
                        <td><?php if (!empty($item['project_code'])): ?><code class="proposal-registry-code"><?= htmlspecialchars((string) $item['project_code']) ?></code><?php endif; ?></td>
                        <td><a href="<?= base_url('proposals/' . (int) ($item['id'] ?? 0)) ?>"><?= htmlspecialchars((string) ($item['title'] ?? '-')) ?></a></td>
                        <td>
                            <?= htmlspecialchars($formLabelOf($rawFormType, (string) ($item['project_type'] ?? ''))) ?>
                            <?php if ($asOf !== ''): ?><div class="data-table-muted">As of <?= htmlspecialchars($asOf) ?></div><?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(ucfirst((string) ($item['project_type'] ?? '')) ?: '-') ?></td>
                        <td><?= htmlspecialchars((string) (($item['college_name'] ?? '') !== '' ? $item['college_name'] : '-')) ?></td>
                        <td><?= htmlspecialchars((string) (($item['leader_name'] ?? '') !== '' ? $item['leader_name'] : '-')) ?></td>
                        <td><?php if ($status !== ''): ?><span class="badge badge-<?= htmlspecialchars($status) ?>"><?= htmlspecialchars(status_label($status)) ?></span><?php endif; ?></td>
                        <td class="<?= $stepValue === '' ? 'data-table-muted' : '' ?>"><?= htmlspecialchars($stepValue !== '' ? \App\Support\AccessControl::stepLabel($stepValue) : '-') ?></td>
                        <td class="data-table-muted"><?= htmlspecialchars((string) (($item['updated_at'] ?? '') !== '' ? substr((string) $item['updated_at'], 0, 10) : '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
