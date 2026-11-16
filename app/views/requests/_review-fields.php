<?php
/**
 * Reviewer inputs for the current step of a request with a reviewer section (incentive requests: the R&D
 * Coordinator's evaluation and the RDO M&E verification). Posted as review[...] with the approval.
 *
 * @var array<string, array<string, mixed>> $reviewFields
 */
$oldReview = is_array($_SESSION['_old_review'] ?? null) ? $_SESSION['_old_review'] : [];
unset($_SESSION['_old_review']);
?>
<div class="request-review-fields webform-grid">
    <?php foreach ($reviewFields as $name => $field): ?>
        <?php
        $id = 'review-' . $name;
        $old = $oldReview[$name] ?? null;
        $required = !empty($field['required']);
        $mark = $required ? ' <span class="webform-req">*</span>' : '';
        ?>
        <div class="webform-field<?= in_array($field['type'], ['radio', 'checks', 'textarea'], true) ? ' webform-field--full' : '' ?>">
            <?php if ($field['type'] === 'radio' || $field['type'] === 'checks'): ?>
                <span class="webform-label"><?= htmlspecialchars((string) $field['label']) ?><?= $mark ?></span>
                <div class="proposal-check-grid">
                    <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                        <?php $checked = $field['type'] === 'checks' ? in_array($optionValue, is_array($old) ? $old : [], true) : (string) $old === (string) $optionValue; ?>
                        <label class="proposal-check-item">
                            <input type="<?= $field['type'] === 'checks' ? 'checkbox' : 'radio' ?>" name="review[<?= htmlspecialchars($name) ?>]<?= $field['type'] === 'checks' ? '[]' : '' ?>" value="<?= htmlspecialchars((string) $optionValue) ?>"<?= $checked ? ' checked' : '' ?><?= $required && $field['type'] === 'radio' ? ' required' : '' ?>>
                            <span><?= htmlspecialchars((string) $optionLabel) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($field['type'] === 'textarea'): ?>
                <label for="<?= $id ?>"><?= htmlspecialchars((string) $field['label']) ?><?= $mark ?></label>
                <textarea id="<?= $id ?>" name="review[<?= htmlspecialchars($name) ?>]" rows="3"<?= $required ? ' required' : '' ?>><?= htmlspecialchars(is_string($old) ? $old : '') ?></textarea>
            <?php else: ?>
                <label for="<?= $id ?>"><?= htmlspecialchars((string) $field['label']) ?><?= $mark ?></label>
                <input id="<?= $id ?>" name="review[<?= htmlspecialchars($name) ?>]"<?= $field['type'] === 'amount' ? ' inputmode="decimal" placeholder="Enter the amount in pesos"' : '' ?> value="<?= htmlspecialchars(is_string($old) ? $old : '') ?>"<?= $required ? ' required' : '' ?>>
            <?php endif; ?>
            <?php if (isset($field['hint'])): ?>
                <p class="webform-hint"><?= htmlspecialchars((string) $field['hint']) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
