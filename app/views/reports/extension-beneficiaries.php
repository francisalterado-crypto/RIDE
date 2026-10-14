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

    return $sortDir === 'asc' ? ' ▲' : ' ▼';
};

$pageTitle = 'Extension Beneficiaries Report | RIDE';
$pageHeading = 'Extension Beneficiaries Report';
$pageSubtitle = 'Accreditation report: extension projects with beneficiary counts (past ' . $years . ' years).';
$formLabels = [
    'manuscript' => 'Manuscript Writing',
    'completed_researches' => 'Completed Researches',
    'ongoing_researches' => 'Ongoing Researches',
    'research_output_published' => 'Research Output Published',
    'research_output_presented' => 'Research Output Presented',
    'commercialized' => 'Commercialized',
    'resulted_in_extension' => 'Resulted in Extension',
    'journal_citation' => 'Journal Citation',
    'book_citation' => 'Book Citation',
    'inventions_um_copyrights' => 'Inventions / UM / Copyrights',
    'linkages' => 'Linkages',
    'consolidated_linkages' => 'Consolidated Linkages',
    'consolidated_completed_researches' => 'Consolidated Completed Researches',
    'consolidated_ongoing_researches' => 'Consolidated Ongoing Researches',
    'consolidated_research_output_published' => 'Consolidated Research Output Published',
    'consolidated_research_output_presented' => 'Consolidated Research Output Presented',
    'consolidated_commercialized' => 'Consolidated Commercialized',
    'consolidated_resulted_in_extension' => 'Consolidated Resulted in Extension',
    'consolidated_journal_citation' => 'Consolidated Journal Citation',
    'consolidated_book_citation' => 'Consolidated Book Citation',
    'consolidated_inventions_um_copyrights' => 'Consolidated Inventions, UM, Copyrights',
    'progress_report' => 'Progress Report',
    'terminal_report' => 'Terminal Report',
    'terminal_report_assessment_form' => 'Terminal Report Assessment Form',
    'obr_matrix' => 'OBR Matrix',
    'trainings_conducted' => 'Trainings Conducted',
    'technical_advisory' => 'Technical Advisory',
    'extension_linkages' => 'Extension Linkages',
    'outreach_activities' => 'Outreach Activities',
    'technology_adoption' => 'Technology Adoption',
    'accomplishment_report' => 'Accomplishment Report',
    'technical_advisory_ar' => 'Technical Advisory AR',
    'outreach_activities_ar' => 'Outreach Activities AR',
    'ebalwasyon_ng_gawain' => 'Ebalwasyon ng Gawain',
    'attendance_sheet' => 'Attendance Sheet',
];
?>

<form method="get" class="inline-form" style="margin-bottom:1rem;">    <label>Years</label>
    <select name="years" onchange="this.form.submit()">
        <?php foreach ([1, 2, 3, 5] as $y): ?>
            <option value="<?= $y ?>" <?= $years === $y ? 'selected' : '' ?>><?= $y ?> year<?= $y > 1 ? 's' : '' ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="card">
    <?php if (empty($rows)): ?>
        <p>No extension beneficiary data for this period.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Project Code</th>
                <th>Title</th>
                <th>College</th>
                <th>Year</th>
                <th>Beneficiaries</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['project_code'] ?? '—') ?></td>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['college_name']) ?></td>
                <td><?= (int) $r['period_year'] ?></td>
                <td><?= (int) $r['total_beneficiaries'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p style="margin-top:1rem;font-size:0.85rem;color:var(--muted);">Export to PDF/Excel planned for Phase 3.</p>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:1rem;">
    <h3 style="margin-top:0;">All Proposal Form Details</h3>
    <p style="margin-top:0.25rem;color:var(--muted);">
        Includes ongoing proposals from research, extension, and other proposal forms, with direct links to full records.
    </p>
    <form method="get" class="inline-form" style="margin-bottom:1rem;">
        <input type="hidden" name="years" value="<?= $years ?>">
        <div>
            <label>Form Type</label>
            <select name="form_type">
                <option value="">All</option>
                <?php foreach ($formTypeOptions as $option): ?>
                    <?php $label = $formLabels[$option] ?? ucwords(str_replace('_', ' ', (string) $option)); ?>
                    <option value="<?= htmlspecialchars((string) $option) ?>" <?= $selectedFormType === (string) $option ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Project Type</label>
            <select name="project_type">
                <option value="">All</option>
                <?php foreach ($projectTypeOptions as $option): ?>
                    <option value="<?= htmlspecialchars((string) $option) ?>" <?= $selectedProjectType === (string) $option ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst((string) $option)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Status</label>
            <select name="status">
                <option value="">All</option>
                <?php foreach ($statusOptions as $option): ?>
                    <option value="<?= htmlspecialchars((string) $option) ?>" <?= $selectedStatus === (string) $option ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $option))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>College</label>
            <select name="college">
                <option value="">All</option>
                <?php foreach ($collegeOptions as $option): ?>
                    <option value="<?= htmlspecialchars((string) $option) ?>" <?= $selectedCollege === (string) $option ? 'selected' : '' ?>><?= htmlspecialchars((string) $option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Search</label>
            <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Code, title, lead">
        </div>
        <button type="submit" class="btn btn-sm">Apply Filters</button>
        <a href="<?= htmlspecialchars($csvExportUrl) ?>" class="btn btn-sm">Export CSV</a>
        <a href="<?= base_url('reports/extension-beneficiaries?years=' . $years) ?>" class="btn btn-sm btn-outline">Reset</a>
    </form>
    <?php if (empty($proposalDetails)): ?>
        <p>No proposal records match the selected filters.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Project Code</th>
                    <th><a href="<?= htmlspecialchars($sortLink('form_type')) ?>">Form<?= htmlspecialchars($sortIndicator('form_type')) ?></a></th>
                    <th>Type</th>
                    <th>Title</th>
                    <th><a href="<?= htmlspecialchars($sortLink('college_name')) ?>">College<?= htmlspecialchars($sortIndicator('college_name')) ?></a></th>
                    <th>Lead</th>
                    <th><a href="<?= htmlspecialchars($sortLink('status')) ?>">Status<?= htmlspecialchars($sortIndicator('status')) ?></a></th>
                    <th>Current Step</th>
                    <th>As Of</th>
                    <th><a href="<?= htmlspecialchars($sortLink('updated_at')) ?>">Updated<?= htmlspecialchars($sortIndicator('updated_at')) ?></a></th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($proposalDetails as $item): ?>
                <?php
                $rawFormType = (string) ($item['form_type'] ?? '');
                $formLabel = $formLabels[$rawFormType] ?? ($rawFormType !== '' ? ucwords(str_replace('_', ' ', $rawFormType)) : 'General Proposal');
                $typeLabel = ucfirst((string) ($item['project_type'] ?? ''));
                $statusLabel = ucwords(str_replace('_', ' ', (string) ($item['status'] ?? '')));
                $stepValue = (string) ($item['current_step'] ?? '');
                $stepLabel = $stepValue !== '' ? ucwords(str_replace('_', ' ', $stepValue)) : '—';
                ?>
                <tr>
                    <td><?= htmlspecialchars((string) (($item['project_code'] ?? '') !== '' ? $item['project_code'] : '—')) ?></td>
                    <td><?= htmlspecialchars($formLabel) ?></td>
                    <td><?= htmlspecialchars($typeLabel !== '' ? $typeLabel : '—') ?></td>
                    <td><?= htmlspecialchars((string) ($item['title'] ?? '—')) ?></td>
                    <td><?= htmlspecialchars((string) (($item['college_name'] ?? '') !== '' ? $item['college_name'] : '—')) ?></td>
                    <td><?= htmlspecialchars((string) (($item['leader_name'] ?? '') !== '' ? $item['leader_name'] : '—')) ?></td>
                    <td><?= htmlspecialchars($statusLabel !== '' ? $statusLabel : '—') ?></td>
                    <td><?= htmlspecialchars($stepLabel) ?></td>
                    <td><?= htmlspecialchars((string) (($item['report_as_of'] ?? '') !== '' ? $item['report_as_of'] : '—')) ?></td>
                    <td><?= htmlspecialchars((string) (($item['updated_at'] ?? '') !== '' ? $item['updated_at'] : '—')) ?></td>
                    <td><a href="<?= base_url('proposals/' . (int) ($item['id'] ?? 0)) ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
