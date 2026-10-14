<?php

/** @var array<string, mixed> $user */

/** @var list<array<string, mixed>> $colleges */

/** @var bool $hasSignature */

/** @var bool $hasAvatar */

/** @var string|null $avatarUrl */

$pageTitle = 'Account Settings — RIDE IMS';

$pageHeading = 'Account Settings';

$pageSubtitle = 'Update your profile details, photo, college/program, signature, and password.';

$userId = (int) ($user['id'] ?? 0);

?>

<div class="grid grid-2">

    <div class="card">
        <h2>Profile</h2>
        <p class="muted">Your name and email appear across the system. College and program pre-fill new proposals.</p>

        <form method="post" action="<?= base_url('profile/update') ?>" enctype="multipart/form-data" class="profile-account-form">
            <?= csrf_field() ?>

            <div class="profile-avatar-block">
                <?php if ($hasAvatar && $avatarUrl): ?>
                    <img
                        src="<?= htmlspecialchars($avatarUrl) ?>"
                        alt="Your profile picture"
                        class="profile-avatar-image"
                    >
                <?php else: ?>
                    <div class="profile-avatar-fallback" aria-hidden="true"><?= htmlspecialchars(user_initials($user)) ?></div>
                <?php endif; ?>

                <div class="profile-avatar-actions">
                    <label for="profile_avatar">Profile picture</label>
                    <input
                        id="profile_avatar"
                        type="file"
                        name="avatar"
                        accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                    >
                    <p class="muted profile-avatar-hint">JPG, PNG, or WebP · max 2 MB. Choose a file, then click Save Profile.</p>
                </div>
            </div>

            <label for="profile_first_name">First Name</label>
            <input
                id="profile_first_name"
                name="first_name"
                value="<?= old('profile_first_name', (string) ($user['first_name'] ?? '')) ?>"
                required
                maxlength="80"
            >

            <label for="profile_last_name">Last Name</label>
            <input
                id="profile_last_name"
                name="last_name"
                value="<?= old('profile_last_name', (string) ($user['last_name'] ?? '')) ?>"
                required
                maxlength="80"
            >

            <label for="profile_email">Email</label>
            <input
                id="profile_email"
                type="email"
                name="email"
                value="<?= old('profile_email', (string) ($user['email'] ?? '')) ?>"
                required
                maxlength="150"
            >

            <label for="profile_college_id">College</label>
            <select id="profile_college_id" name="college_id">
                <option value="">Select college</option>
                <?php
                $selectedCollegeId = old('profile_college_id', (string) ($user['college_id'] ?? ''));
                foreach ($colleges as $college):
                    ?>
                    <option
                        value="<?= (int) $college['id'] ?>"
                        <?= $selectedCollegeId === (string) $college['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars((string) $college['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="profile_program">Program</label>
            <input
                id="profile_program"
                name="program"
                value="<?= old('profile_program', (string) ($user['program'] ?? '')) ?>"
                maxlength="150"
                placeholder="e.g. BS Computer Engineering"
            >

            <div class="actions">
                <button type="submit" class="btn btn-accent">Save Profile</button>
            </div>
        </form>

        <?php if ($hasAvatar): ?>
            <form method="post" action="<?= base_url('profile/avatar/remove') ?>" class="profile-avatar-remove">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm">Remove Picture</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Signature</h2>
        <p class="muted">Upload a clear image of your handwritten signature (JPG or PNG, max 2 MB).</p>

        <?php if ($hasSignature): ?>
            <div class="profile-signature-preview">
                <img
                    src="<?= base_url('signatures/' . $userId) ?>"
                    alt="Your signature"
                    class="profile-signature-image"
                >
            </div>
        <?php else: ?>
            <p class="profile-signature-empty">No signature uploaded yet.</p>
        <?php endif; ?>

        <form method="post" action="<?= base_url('profile/signature') ?>" enctype="multipart/form-data" class="profile-signature-form">
            <?= csrf_field() ?>
            <label for="profile_signature">Choose signature image</label>
            <input id="profile_signature" type="file" name="signature" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required>
            <div class="actions">
                <button type="submit" class="btn btn-accent"><?= $hasSignature ? 'Replace Signature' : 'Upload Signature' ?></button>
            </div>
        </form>

        <?php if ($hasSignature): ?>
            <form method="post" action="<?= base_url('profile/signature/remove') ?>" class="profile-signature-remove">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm">Remove Signature</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card" id="researcher-profiles">
        <h2>Researcher Profiles</h2>
        <p class="muted">Link your research profiles so they appear with your publications. All fields are optional.</p>

        <form method="post" action="<?= base_url('profile/researcher') ?>" data-capitalize="off">
            <?= csrf_field() ?>

            <label for="profile_orcid_id">ORCID iD</label>
            <input
                id="profile_orcid_id"
                name="orcid_id"
                value="<?= old('profile_orcid_id', (string) ($user['orcid_id'] ?? '')) ?>"
                maxlength="60"
                placeholder="0000-0002-1825-0097"
                autocomplete="off"
            >
            <p class="muted profile-field-hint">Your 16-digit iD from orcid.org. You can paste the full link.</p>

            <label for="profile_google_scholar_url">Google Scholar profile</label>
            <input
                id="profile_google_scholar_url"
                type="url"
                name="google_scholar_url"
                value="<?= old('profile_google_scholar_url', (string) ($user['google_scholar_url'] ?? '')) ?>"
                maxlength="255"
                placeholder="https://scholar.google.com/citations?user=..."
            >
            <p class="muted profile-field-hint">Open your Google Scholar profile and copy the address from the browser.</p>

            <label for="profile_researchgate_url">ResearchGate profile</label>
            <input
                id="profile_researchgate_url"
                type="url"
                name="researchgate_url"
                value="<?= old('profile_researchgate_url', (string) ($user['researchgate_url'] ?? '')) ?>"
                maxlength="255"
                placeholder="https://www.researchgate.net/profile/Your-Name"
            >
            <p class="muted profile-field-hint">Open your ResearchGate profile and copy the address from the browser.</p>

            <label for="profile_github_url">GitHub</label>
            <input
                id="profile_github_url"
                name="github_url"
                value="<?= old('profile_github_url', (string) ($user['github_url'] ?? '')) ?>"
                maxlength="255"
                placeholder="https://github.com/username"
                autocomplete="off"
            >
            <p class="muted profile-field-hint">Your GitHub username or profile link.</p>

            <div class="actions">
                <button type="submit" class="btn btn-accent">Save Researcher Profiles</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Change Password</h2>
        <p class="muted">Enter your current password, then choose a new one (at least 8 characters).</p>

        <form method="post" action="<?= base_url('profile/password') ?>" class="profile-password-form">
            <?= csrf_field() ?>

            <label for="profile_current_password">Current Password</label>
            <input id="profile_current_password" type="password" name="current_password" required autocomplete="current-password">

            <label for="profile_password">New Password</label>
            <input id="profile_password" type="password" name="password" required minlength="8" autocomplete="new-password">

            <label for="profile_password_confirmation">Confirm New Password</label>
            <input id="profile_password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">

            <div class="actions">
                <button type="submit" class="btn btn-accent">Update Password</button>
            </div>
        </form>
    </div>

</div>
