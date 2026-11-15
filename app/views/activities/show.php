<?php
/** @var array<string, mixed> $entry */
/** @var array<string, mixed> $definition */
/** @var array<string, array<string, string>> $checklist */
/** @var array<string, list<array<string, mixed>>> $documents */
/** @var bool $isOwner */

$type = (string) $entry['activity_type'];
$section = (string) $entry['section'];
$office = \App\Support\ExtensionActivityTypes::officeOf($type);
$basePath = \App\Support\ExtensionActivityTypes::basePath($office);
$terms = \App\Support\ExtensionActivityTypes::officeTerms($office);
$pageHeading = (string) $definition['label'];
$pageSubtitle = $terms['verb'] . ' ' . trim((string) $entry['first_name'] . ' ' . (string) $entry['last_name']) . '.';
$sectionLabel = (string) ($definition['sections'][$section]['label'] ?? '');
$fields = \App\Support\ExtensionActivityTypes::fieldsFor($type, $section);
$data = is_array($entry['data'] ?? null) ? $entry['data'] : [];

$display = static function (array $field, string $value): string {
    if ($value === '') {
        return '';
    }
    if ($field['type'] === 'select') {
        return (string) ($field['options'][$value] ?? $value);
    }
    if ($field['type'] === 'date' && ($parsed = DateTimeImmutable::createFromFormat('Y-m-d', $value)) instanceof DateTimeImmutable) {
        return $parsed->format('F j, Y');
    }

    return $value;
};
$valueHtml = static fn (string $value): string => $value !== '' ? nl2br(htmlspecialchars($value)) : '<span class="wf-view-empty">Not provided</span>';

// Fields in display blocks: consecutive fields of a group stay together; ratings get their own summary.
$ratingLevels = ['poor' => 'Poor', 'fair' => 'Fair', 'good' => 'Good', 'better' => 'Better', 'best' => 'Best'];
$isRating = static fn (string $name): bool => $name === 'trainees_surveyed' || preg_match('/^(quality|timeliness)_(' . implode('|', array_keys($ratingLevels)) . ')$/', $name) === 1;
$hasRatings = isset($fields['quality_poor']) && isset($fields['trainees_surveyed']);
$blocks = [];
foreach ($fields as $name => $field) {
    if ($hasRatings && $isRating($name)) {
        continue;
    }
    $group = (string) ($field['group'] ?? '');
    $last = array_key_last($blocks);
    if ($group !== '' && $last !== null && $blocks[$last]['group'] === $group) {
        $blocks[$last]['fields'][$name] = $field;
        continue;
    }
    $blocks[] = ['group' => $group, 'fields' => [$name => $field]];
}
?>

<div class="page-actions-bar">
    <?php if ($isOwner): ?>
        <a class="btn" href="<?= base_url($basePath . '/' . (int) $entry['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
    <a class="btn btn-outline" href="<?= base_url($basePath . '?type=' . $definition['slug']) ?>">Back to <?= htmlspecialchars($terms['page']) ?></a>
</div>

<div class="webform wf-view">
    <section class="webform-card">
        <header class="webform-card-head">
            <span class="webform-card-num">1</span>
            <div>
                <h2>Details</h2>
                <?php if ($sectionLabel !== ''): ?><p><?= htmlspecialchars($sectionLabel) ?></p><?php endif; ?>
            </div>
        </header>
        <div class="webform-grid">
            <?php foreach ($blocks as $block): ?>
                <?php if ($block['group'] !== ''): ?>
                    <div class="wf-view-group webform-field--full">
                        <span class="wf-view-group-title"><?= htmlspecialchars($block['group']) ?></span>
                        <div class="wf-view-group-fields">
                            <?php foreach ($block['fields'] as $name => $field): ?>
                                <div class="wf-view-field">
                                    <span class="wf-view-label"><?= htmlspecialchars((string) $field['label']) ?></span>
                                    <div class="wf-view-value"><?= $valueHtml($display($field, trim((string) ($data[$name] ?? '')))) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($block['fields'] as $name => $field): ?>
                        <div class="wf-view-field<?= $field['type'] === 'textarea' || $field['type'] === 'program' ? ' webform-field--full' : '' ?>">
                            <span class="wf-view-label"><?= htmlspecialchars((string) $field['label']) ?></span>
                            <div class="wf-view-value"><?= $valueHtml($display($field, trim((string) ($data[$name] ?? '')))) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($hasRatings): ?>
                <?php $surveyed = (int) ($data['trainees_surveyed'] ?? 0); ?>
                <div class="wf-view-group webform-field--full">
                    <span class="wf-view-group-title">Trainee Ratings <span class="muted">(<?= $surveyed ?> surveyed)</span></span>
                    <?php foreach (['quality' => 'Quality', 'timeliness' => 'Timeliness'] as $prefix => $scaleLabel): ?>
                        <?php
                        $counts = [];
                        foreach (array_keys($ratingLevels) as $level) {
                            $counts[$level] = (int) ($data[$prefix . '_' . $level] ?? 0);
                        }
                        $rated = array_sum($counts);
                        ?>
                        <div class="rating-summary">
                            <span class="rating-summary-label"><?= $scaleLabel ?></span>
                            <div class="rating-bar" role="img" aria-label="<?= htmlspecialchars($scaleLabel . ': ' . implode(', ', array_map(static fn (string $level, int $count): string => $count . ' ' . $level, $ratingLevels, $counts))) ?>">
                                <?php foreach ($counts as $level => $count): ?>
                                    <?php if ($count > 0 && $rated > 0): ?>
                                        <span class="rating-bar-seg rating-bar-seg--<?= $level ?>" style="flex-grow: <?= $count ?>" title="<?= $ratingLevels[$level] ?>: <?= $count ?>"></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <span class="rating-summary-counts">
                                <?php foreach ($counts as $level => $count): ?>
                                    <span class="rating-chip rating-chip--<?= $level ?>"><?= $ratingLevels[$level] ?> <strong><?= $count ?></strong></span>
                                <?php endforeach; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($checklist !== []): ?>
        <section class="webform-card">
            <header class="webform-card-head">
                <span class="webform-card-num">2</span>
                <div><h2>Documentary Evidence</h2></div>
            </header>
            <?php
            $docEditable = false;
            require APP_PATH . '/views/activities/_document-checklist.php';
            ?>
        </section>
    <?php endif; ?>
</div>
