<?php
/** @var list<array<string, mixed>> $attachments */
/** @var string $downloadBase e.g. scholarly/attachments or admin/scholarly/attachments */
/** @var bool $canManage */
/** @var string|null $uploadAction optional POST URL to add more files */
?>
<?php if (!empty($attachments)): ?>
    <ul class="scholarly-attachments" style="margin: 0.35rem 0 0; padding-left: 1.1rem;">
        <?php foreach ($attachments as $attachment): ?>
            <li>
                <a href="<?= base_url($downloadBase . '/' . (int) $attachment['id']) ?>">
                    <?= htmlspecialchars((string) $attachment['original_name']) ?>
                </a>
                <?php if ($canManage): ?>
                    <form method="post" action="<?= base_url('scholarly/attachments/' . (int) $attachment['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Remove this supporting document?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline" style="margin-left: 0.25rem;" aria-label="Remove this document">Remove</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($canManage && !empty($uploadAction)): ?>
    <form method="post" action="<?= htmlspecialchars($uploadAction) ?>" enctype="multipart/form-data" class="inline-form" style="margin-top: 0.5rem;">
        <?= csrf_field() ?>
        <input type="file" name="supporting_documents[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
        <button type="submit" class="btn btn-sm">Add documents</button>
    </form>
<?php endif; ?>
