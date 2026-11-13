<?php
/**
 * The rows of a quarterly report, read-only, grouped by section. The rows come from Conducted Activities (ESO)
 * or Research Accomplishments (RDO); the office paper layout is only used for Print Preview.
 *
 * @var string $reportType
 * @var array<string, mixed> $summaryData
 */
use App\Support\ExtensionActivityTypes;

$rowsDefinition = ExtensionActivityTypes::get($reportType) ?? ['sections' => []];
$rowsOffice = ExtensionActivityTypes::officeOf($reportType);
$readPath = static function (array $data, array $path): array {
    foreach ($path as $key) {
        $data = is_array($data[$key] ?? null) ? $data[$key] : [];
    }
    return array_values(array_filter($data, 'is_array'));
};
$cellValue = static function (array $fieldDef, string $value): string {
    if ($value !== '' && isset($fieldDef['options'][$value])) {
        return (string) $fieldDef['options'][$value];
    }
    if ($value !== '' && ($fieldDef['type'] ?? '') === 'date' && strtotime($value) !== false) {
        return date('M j, Y', (int) strtotime($value));
    }
    return $value;
};
$sectionCount = count($rowsDefinition['sections']);
?>
<?php foreach ($rowsDefinition['sections'] as $sectionKey => $sectionDef): ?>
    <?php
    $rows = $readPath($summaryData, $sectionDef['path']);
    $fields = ExtensionActivityTypes::fieldsFor($reportType, (string) $sectionKey);
    ?>
    <div class="report-rows">
        <?php if ($sectionCount > 1 && (string) $sectionDef['label'] !== ''): ?>
            <h3 class="report-rows-title"><?= htmlspecialchars((string) $sectionDef['label']) ?> <span><?= count($rows) ?></span></h3>
        <?php endif; ?>
        <?php if ($rows === []): ?>
            <p class="report-rows-empty">No entries for this period.</p>
        <?php else: ?>
            <div class="webform-table-wrap">
                <table class="webform-table report-rows-table">
                    <thead>
                        <tr>
                            <th class="report-rows-num">#</th>
                            <?php foreach ($fields as $fieldDef): ?>
                                <th><?= htmlspecialchars((string) $fieldDef['label']) ?></th>
                            <?php endforeach; ?>
                            <?php if ($reportType === 'trainings_conducted'): ?><th>Weighted persons trained</th><?php endif; ?>
                            <?php if ($rowsOffice === 'rdo'): ?><th>Evidence</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $i => $row): ?>
                            <tr>
                                <td class="report-rows-num"><?= $i + 1 ?></td>
                                <?php foreach ($fields as $field => $fieldDef): ?>
                                    <?php $value = $cellValue($fieldDef, trim((string) ($row[$field] ?? ''))); ?>
                                    <td class="<?= $value === '' ? 'report-rows-blank' : '' ?>"><?= $value === '' ? '-' : nl2br(htmlspecialchars($value)) ?></td>
                                <?php endforeach; ?>
                                <?php if ($reportType === 'trainings_conducted'): ?>
                                    <td><?= htmlspecialchars((string) ($row['persons_trained_weighted'] ?? '')) ?></td>
                                <?php endif; ?>
                                <?php if ($rowsOffice === 'rdo'): ?>
                                    <td>
                                        <?php $link = (string) ($row['google_drive_link'] ?? ''); ?>
                                        <?php if (preg_match('#^https?://#', $link) === 1): ?>
                                            <a href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener">Open</a>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
