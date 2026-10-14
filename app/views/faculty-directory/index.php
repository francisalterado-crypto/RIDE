<?php
/** @var list<array<string, mixed>> $members */
/** @var int $matchCount */
/** @var int $totalFaculty */
/** @var int $page */
/** @var int $pages */
/** @var int $perPage */
/** @var list<array<string, mixed>> $colleges */
/** @var list<array{name: string, count: int}> $popularSpecializations */
/** @var string $query */
/** @var string $selectedSpecialization */
/** @var int $selectedCollege */

$pageTitle = 'Faculty Directory | RIDE';
$pageHeading = 'Faculty Directory';
$pageSubtitle = 'Find faculty by name, college, or specialization.';

$e = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''));
$url = static function (array $overrides = []) use ($query, $selectedSpecialization, $selectedCollege): string {
    $params = array_merge([
        'q' => $query,
        'specialization' => $selectedSpecialization,
        'college' => $selectedCollege ?: '',
        'page' => '',
    ], $overrides);
    if ((int) $params['page'] <= 1) {
        $params['page'] = ''; // page 1 is the default
    }
    $params = array_filter($params, static fn ($value): bool => $value !== '' && $value !== null && $value !== 0);

    return base_url('faculty-directory' . ($params !== [] ? '?' . http_build_query($params) : ''));
};
$initials = static fn (array $m): string => mb_strtoupper(mb_substr(trim((string) ($m['first_name'] ?? '')), 0, 1) . mb_substr(trim((string) ($m['last_name'] ?? '')), 0, 1));
$isFiltered = $query !== '' || $selectedSpecialization !== '' || $selectedCollege > 0;
$from = $matchCount === 0 ? 0 : ($page - 1) * $perPage + 1;
$to = min($page * $perPage, $matchCount);

// Page links: first, last, and two either side of the current page.
$pageLinks = [];
for ($p = 1; $p <= $pages; $p++) {
    if ($p === 1 || $p === $pages || abs($p - $page) <= 2) {
        $pageLinks[] = $p;
    } elseif (end($pageLinks) !== '…gap') {
        $pageLinks[] = '…gap';
    }
}
?>

<div class="dir">
    <section class="card list-card">
        <div class="list-card-head">
            <div>
                <h2><?= $isFiltered ? 'Matching faculty' : 'All faculty' ?></h2>
                <p><?= $isFiltered ? (int) $matchCount . ' of ' . (int) $totalFaculty . ' faculty match' : (int) $totalFaculty . ' faculty' ?></p>
            </div>
            <form method="get" class="mon-filters" action="<?= base_url('faculty-directory') ?>">
                <?php if ($selectedSpecialization !== ''): ?><input type="hidden" name="specialization" value="<?= $e($selectedSpecialization) ?>"><?php endif; ?>
                <input type="search" name="q" value="<?= $e($query) ?>" class="no-capitalize" placeholder="Search name, email, program, specialization" aria-label="Search faculty">
                <select name="college" aria-label="Filter by college" onchange="this.form.submit()">
                    <option value="">All colleges</option>
                    <?php foreach ($colleges as $college): ?>
                        <option value="<?= (int) $college['id'] ?>"<?= $selectedCollege === (int) $college['id'] ? ' selected' : '' ?>><?= $e($college['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm">Search</button>
                <?php if ($isFiltered): ?>
                    <a class="btn btn-sm btn-outline" href="<?= base_url('faculty-directory') ?>">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($popularSpecializations !== []): ?>
            <div class="dir-specializations" aria-label="Filter by specialization">
                <span class="chip-suggestions-label">Specializations:</span>
                <?php foreach ($popularSpecializations as $spec): ?>
                    <?php $active = mb_strtolower($spec['name']) === mb_strtolower($selectedSpecialization); ?>
                    <a class="dir-filter-chip<?= $active ? ' is-active' : '' ?>" href="<?= $e($url(['specialization' => $active ? '' : $spec['name']])) ?>">
                        <?= $e($spec['name']) ?> <span><?= (int) $spec['count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($members === []): ?>
            <p class="fd-empty">No faculty match these filters.</p>
        <?php else: ?>
            <div class="data-table-wrap">
                <table class="data-table dir-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>College</th>
                            <th>Program</th>
                            <th>Specializations</th>
                            <th>Profiles</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($members as $member): ?>
                        <?php
                        $links = array_filter([
                            'ORCID' => !empty($member['orcid_id']) ? 'https://orcid.org/' . $member['orcid_id'] : '',
                            'Scholar' => (string) ($member['google_scholar_url'] ?? ''),
                            'ResearchGate' => (string) ($member['researchgate_url'] ?? ''),
                            'GitHub' => (string) ($member['github_url'] ?? ''),
                        ]);
                        ?>
                        <tr>
                            <td>
                                <div class="dir-person">
                                    <?php if (!empty($member['avatar_url'])): ?>
                                        <img class="dir-avatar dir-avatar--sm" src="<?= $e($member['avatar_url']) ?>" alt="">
                                    <?php else: ?>
                                        <span class="dir-avatar dir-avatar--sm dir-avatar--initials" aria-hidden="true"><?= $e($initials($member)) ?></span>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= $e(trim($member['first_name'] . ' ' . $member['last_name'])) ?></strong>
                                        <a class="dir-email" href="mailto:<?= $e($member['email']) ?>"><?= $e($member['email']) ?></a>
                                    </div>
                                </div>
                            </td>
                            <td><?= $e($member['college_name'] ?? '') ?></td>
                            <td><?= $e($member['program'] ?? '') ?></td>
                            <td>
                                <?php if ($member['specializations'] === []): ?>
                                    <span class="data-table-muted">None listed</span>
                                <?php else: ?>
                                    <div class="dir-tags">
                                        <?php foreach ($member['specializations'] as $spec): ?>
                                            <a class="dir-tag" href="<?= $e($url(['specialization' => $spec, 'page' => ''])) ?>"><?= $e($spec) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($links === []): ?>
                                    <span class="data-table-muted">None</span>
                                <?php else: ?>
                                    <div class="dir-links-inline">
                                        <?php foreach ($links as $label => $link): ?>
                                            <a href="<?= $e($link) ?>" target="_blank" rel="noopener noreferrer"><?= $e($label) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pager">
                <span class="pager-summary">Showing <?= $from ?>-<?= $to ?> of <?= (int) $matchCount ?></span>
                <?php if ($pages > 1): ?>
                    <nav class="pager-links" aria-label="Pages">
                        <?php if ($page > 1): ?>
                            <a href="<?= $e($url(['page' => $page - 1])) ?>" rel="prev">Previous</a>
                        <?php else: ?>
                            <span class="is-disabled">Previous</span>
                        <?php endif; ?>
                        <?php foreach ($pageLinks as $link): ?>
                            <?php if ($link === '…gap'): ?>
                                <span class="pager-gap">...</span>
                            <?php elseif ($link === $page): ?>
                                <span class="is-current" aria-current="page"><?= $link ?></span>
                            <?php else: ?>
                                <a href="<?= $e($url(['page' => $link])) ?>"><?= $link ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($page < $pages): ?>
                            <a href="<?= $e($url(['page' => $page + 1])) ?>" rel="next">Next</a>
                        <?php else: ?>
                            <span class="is-disabled">Next</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
