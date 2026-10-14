<?php
/** @var array<string, mixed> $account */
/** @var list<array<string, mixed>> $roles */
/** @var list<array<string, mixed>> $colleges */
/** @var array<string, int> $collegeScopedSlugs */
/** @var int $currentUserId */
$pageTitle = 'Edit Account | RIDE';
$pageHeading = 'Edit Account';
$pageSubtitle = 'Update account details, role assignment, college assignment, or reset the password.';
$isCurrentUser = $currentUserId === (int) $account['id'];
?>

<div class="accounts-page">
<div class="page-actions-bar">
    <a class="btn btn-outline" href="<?= base_url('admin/accounts') ?>">Back to Accounts</a>
</div>

<div class="accounts-management-grid">
    <div class="card accounts-form-card">
        <h2>Account Details</h2>
        <p class="muted">
            Editing
            <strong><?= htmlspecialchars((string) $account['email']) ?></strong>
            <?php if ($isCurrentUser): ?>
                (current account)
            <?php endif; ?>
        </p>

        <form method="post" action="<?= base_url('admin/accounts/' . $account['id'] . '/update') ?>" class="accounts-form-grid">
            <?= csrf_field() ?>

            <div class="accounts-form-field">
                <label for="edit_account_first_name">First Name</label>
                <input
                    id="edit_account_first_name"
                    name="first_name"
                    value="<?= old('edit_first_name', (string) ($account['first_name'] ?? '')) ?>"
                    required
                    maxlength="80"
                >
            </div>

            <div class="accounts-form-field">
                <label for="edit_account_last_name">Last Name</label>
                <input
                    id="edit_account_last_name"
                    name="last_name"
                    value="<?= old('edit_last_name', (string) ($account['last_name'] ?? '')) ?>"
                    required
                    maxlength="80"
                >
            </div>

            <div class="accounts-form-field accounts-form-field-span-2">
                <label for="edit_account_email">Email</label>
                <input
                    id="edit_account_email"
                    type="email"
                    name="email"
                    value="<?= old('edit_email', (string) ($account['email'] ?? '')) ?>"
                    required
                    maxlength="150"
                >
            </div>

            <div class="accounts-form-field">
                <label for="edit_account_role_id">Role</label>
                <select id="edit_account_role_id" name="role_id" required>
                    <option value="">Select role</option>
                    <?php foreach ($roles as $role): ?>
                        <?php
                        $selectedRoleId = old('edit_role_id', (string) ($account['role_id'] ?? ''));
                        $isSelected = $selectedRoleId === (string) $role['id'];
                        $needsCollege = isset($collegeScopedSlugs[(string) $role['slug']]);
                        ?>
                        <option value="<?= (int) $role['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $role['name']) ?><?= $needsCollege ? ' (college required)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="accounts-form-field">
                <label for="edit_account_college_id">College</label>
                <select id="edit_account_college_id" name="college_id">
                    <option value="">University-wide / none</option>
                    <?php foreach ($colleges as $college): ?>
                        <?php $selectedCollegeId = old('edit_college_id', (string) ($account['college_id'] ?? '')); ?>
                        <option value="<?= (int) $college['id'] ?>" <?= $selectedCollegeId === (string) $college['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $college['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="accounts-form-field accounts-form-field-span-2">
                <label for="edit_account_program">Program</label>
                <?php $selectedProgram = (string) ($_SESSION['_old']['edit_program'] ?? ($account['program'] ?? '')); ?>
                <select id="edit_account_program" name="program">
                    <option value="">Select program (optional)</option>
                    <?php foreach (account_program_options($selectedProgram) as $program): ?>
                        <option value="<?= htmlspecialchars($program) ?>" <?= $selectedProgram === $program ? 'selected' : '' ?>>
                            <?= htmlspecialchars($program) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="accounts-form-actions accounts-form-field-span-2">
                <button type="submit" class="btn btn-accent">Save Account</button>
            </div>
        </form>
    </div>

    <div class="card accounts-form-card">
        <h2>Reset Password</h2>
        <p class="muted">Set a new password for this account. The user can sign in with the new password immediately after reset.</p>

        <form method="post" action="<?= base_url('admin/accounts/' . $account['id'] . '/reset-password') ?>">
            <?= csrf_field() ?>

            <label for="reset_account_password">New Password</label>
            <input id="reset_account_password" type="password" name="password" required minlength="8">

            <label for="reset_account_password_confirmation">Confirm New Password</label>
            <input id="reset_account_password_confirmation" type="password" name="password_confirmation" required minlength="8">

            <div class="accounts-form-actions">
                <button type="submit" class="btn">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <h2>Account Notes</h2>
    <ul>
        <li>Changing the role replaces the previous role assignment for this account.</li>
        <li>College must be set for coordinator, dean, faculty, and external partner roles.</li>
        <li>Program is optional and can pre-fill the proposal form for faculty accounts.</li>
        <li>The current admin account cannot remove its own admin access from this screen.</li>
    </ul>
</div>
</div>
