<?php

$isEdit = $report !== null;

$canManage = $canManage ?? true;

$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Progress Report | RIDE';

$pageHeading = ($isEdit ? 'Edit' : 'New') . ' Progress Report';

$pageSubtitle = 'Project: ' . htmlspecialchars($project['title']);

$action = $isEdit

    ? base_url('projects/' . $project['id'] . '/reports/' . $report['id'])

    : base_url('projects/' . $project['id'] . '/reports');

?>

<div class="card">

    <?php if (!$canManage): ?>

        <p class="muted">This report is read-only.</p>

    <?php endif; ?>



    <form method="post" action="<?= $action ?>">

        <?= csrf_field() ?>



        <label>Period label</label>

        <input name="period_label" value="<?= htmlspecialchars($report['period_label'] ?? '') ?>" required <?= $canManage ? '' : 'readonly' ?>>



        <label>Report type</label>

        <select name="report_type" <?= $canManage ? '' : 'disabled' ?>>

            <?php foreach (['quarterly', 'annual', 'final'] as $t): ?>

                <option value="<?= $t ?>" <?= ($report['report_type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>

            <?php endforeach; ?>

        </select>



        <label>Due date</label>

        <input type="date" name="due_date" value="<?= htmlspecialchars($report['due_date'] ?? '') ?>" <?= $canManage ? '' : 'readonly' ?>>



        <label>Narrative</label>

        <textarea name="narrative" <?= $canManage ? '' : 'readonly' ?>><?= htmlspecialchars($report['narrative'] ?? '') ?></textarea>



        <label>Financial summary</label>

        <textarea name="financial_summary" <?= $canManage ? '' : 'readonly' ?>><?= htmlspecialchars($report['financial_summary'] ?? '') ?></textarea>



        <h3>Financial line items</h3>

        <div id="financial-lines">

            <?php

            $lines = $lines ?: [['description' => '', 'budgeted' => 0, 'spent' => 0]];

            foreach ($lines as $i => $line):

            ?>

            <div class="financial-line">

                <input name="line_description[]" placeholder="Description" value="<?= htmlspecialchars($line['description']) ?>" <?= $canManage ? '' : 'readonly' ?>>

                <input type="number" step="0.01" name="line_budgeted[]" placeholder="Budgeted" value="<?= htmlspecialchars((string) $line['budgeted']) ?>" <?= $canManage ? '' : 'readonly' ?>>

                <input type="number" step="0.01" name="line_spent[]" placeholder="Spent" value="<?= htmlspecialchars((string) $line['spent']) ?>" <?= $canManage ? '' : 'readonly' ?>>

            </div>

            <?php endforeach; ?>

        </div>

        <?php if ($canManage): ?>

            <button type="button" class="btn btn-sm btn-outline" onclick="rideAddLine()">+ Line</button>

        <?php endif; ?>



        <label>Outputs (publications, patents, trainings)</label>

        <textarea name="outputs" <?= $canManage ? '' : 'readonly' ?>><?= htmlspecialchars($report['outputs'] ?? '') ?></textarea>



        <?php if ($canManage): ?>

            <button type="submit" class="btn">Save Draft</button>

            <?php if ($isEdit): ?>

                <a class="btn btn-outline" href="<?= base_url('projects/' . $project['id'] . '?tab=reports') ?>">Back</a>

            <?php endif; ?>

        <?php else: ?>

            <a class="btn btn-outline" href="<?= base_url('projects/' . $project['id'] . '?tab=reports') ?>">Back</a>

        <?php endif; ?>

    </form>



    <?php if ($canManage && $isEdit && ($report['status'] ?? '') === 'draft'): ?>

    <form method="post" action="<?= base_url('projects/' . $project['id'] . '/reports/' . $report['id'] . '/submit') ?>" style="margin-top:1rem;">

        <?= csrf_field() ?>

        <button type="submit" class="btn btn-accent" onclick="return confirm('Submit this report?');">Submit Report</button>

    </form>

    <?php endif; ?>

</div>

<script>

function rideAddLine() {

    const div = document.createElement('div');

    div.className = 'financial-line';

    div.innerHTML = '<input name="line_description[]" placeholder="Description">' +

        '<input type="number" step="0.01" name="line_budgeted[]" placeholder="Budgeted" value="0">' +

        '<input type="number" step="0.01" name="line_spent[]" placeholder="Spent" value="0">';

    document.getElementById('financial-lines').appendChild(div);

}

</script>


