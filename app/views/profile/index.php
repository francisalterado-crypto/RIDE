<?php

/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $colleges */
/** @var bool $hasSignature */
/** @var bool $hasAvatar */
/** @var string|null $avatarUrl */

$pageTitle = 'Account Settings | RIDE';
$pageHeading = 'Account Settings';
$pageSubtitle = 'Your profile, research links, signature, and password.';

$userId = (int) ($user['id'] ?? 0);
$selectedCollegeId = old('profile_college_id', (string) ($user['college_id'] ?? ''));
?>

<div class="settings">
    <section class="settings-card" id="profile-details">
        <header class="settings-card-head">
            <h2>Profile</h2>
            <p>Your name and email appear across the system. College and program pre-fill new proposals.</p>
        </header>

        <form method="post" action="<?= base_url('profile/update') ?>" enctype="multipart/form-data" class="settings-profile">
            <?= csrf_field() ?>

            <div class="settings-avatar">
                <?php if ($hasAvatar && $avatarUrl): ?>
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Your profile picture" class="profile-avatar-image">
                <?php else: ?>
                    <div class="profile-avatar-fallback" aria-hidden="true"><?= htmlspecialchars(user_initials($user)) ?></div>
                <?php endif; ?>
                <label for="profile_avatar" class="settings-file-label">Change picture</label>
                <input id="profile_avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                <p class="settings-hint">JPG, PNG, or WebP, up to 2 MB. Saved with the profile.</p>
            </div>

            <div class="settings-fields">
                <div class="settings-grid">
                    <div class="settings-field">
                        <label for="profile_first_name">First name</label>
                        <input id="profile_first_name" name="first_name" value="<?= old('profile_first_name', (string) ($user['first_name'] ?? '')) ?>" required maxlength="80">
                    </div>
                    <div class="settings-field">
                        <label for="profile_last_name">Last name</label>
                        <input id="profile_last_name" name="last_name" value="<?= old('profile_last_name', (string) ($user['last_name'] ?? '')) ?>" required maxlength="80">
                    </div>
                    <div class="settings-field settings-field--full">
                        <label for="profile_email">Email</label>
                        <input id="profile_email" type="email" name="email" value="<?= old('profile_email', (string) ($user['email'] ?? '')) ?>" required maxlength="150">
                    </div>
                    <div class="settings-field">
                        <label for="profile_college_id">College</label>
                        <select id="profile_college_id" name="college_id">
                            <option value="">Select college</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= (int) $college['id'] ?>" <?= $selectedCollegeId === (string) $college['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $college['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="settings-field">
                        <label for="profile_program">Program</label>
                        <input id="profile_program" name="program" value="<?= old('profile_program', (string) ($user['program'] ?? '')) ?>" maxlength="150" placeholder="e.g. BS Computer Engineering">
                    </div>
                </div>
                <div class="settings-actions">
                    <button type="submit" class="btn btn-accent">Save Profile</button>
                </div>
            </div>
        </form>

        <?php if ($hasAvatar): ?>
            <form method="post" action="<?= base_url('profile/avatar/remove') ?>" class="settings-secondary-action">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm">Remove picture</button>
            </form>
        <?php endif; ?>
    </section>

    <?php if (!empty($showSpecializations)): ?>
        <section class="settings-card" id="specializations">
            <header class="settings-card-head">
                <h2>Specializations</h2>
                <p>Your areas of expertise. They help colleagues and reviewers find you in the Faculty Directory and when building project teams.</p>
            </header>

            <form method="post" action="<?= base_url('profile/specializations') ?>" class="chip-form" data-capitalize="off">
                <?= csrf_field() ?>
                <div class="chip-input" data-chip-input data-max="<?= \App\Models\UserSpecialization::MAX_PER_USER ?>">
                    <?php foreach ($specializations as $name): ?>
                        <span class="chip-item">
                            <span class="chip-text"><?= htmlspecialchars($name) ?></span>
                            <input type="hidden" name="specializations[]" value="<?= htmlspecialchars($name) ?>">
                            <button type="button" class="chip-remove" aria-label="Remove <?= htmlspecialchars($name) ?>"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                        </span>
                    <?php endforeach; ?>
                    <input type="text" class="chip-entry" maxlength="<?= \App\Models\UserSpecialization::MAX_LENGTH ?>" placeholder="Type a specialization, then press Enter" aria-label="Add a specialization" autocomplete="off">
                </div>
                <p class="settings-hint">Press Enter or a comma to add each one, up to <?= \App\Models\UserSpecialization::MAX_PER_USER ?>. For example: Machine Learning, Coastal Ecology, Rural Sociology.</p>

                <?php if (!empty($specializationSuggestions)): ?>
                    <div class="chip-suggestions" data-chip-suggestions>
                        <span class="chip-suggestions-label">Used by other faculty:</span>
                        <?php foreach (array_slice($specializationSuggestions, 0, 40) as $suggestion): ?>
                            <button type="button" class="chip-suggestion" data-value="<?= htmlspecialchars($suggestion) ?>"><?= htmlspecialchars($suggestion) ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="settings-actions">
                    <button type="submit" class="btn btn-accent">Save Specializations</button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="settings-card" id="researcher-profiles">
        <header class="settings-card-head">
            <h2>Researcher Profiles</h2>
            <p>Link your research profiles so they appear with your publications. All fields are optional.</p>
        </header>

        <form method="post" action="<?= base_url('profile/researcher') ?>" data-capitalize="off">
            <?= csrf_field() ?>
            <div class="settings-grid">
                <div class="settings-field">
                    <label for="profile_orcid_id">ORCID iD</label>
                    <input id="profile_orcid_id" name="orcid_id" value="<?= old('profile_orcid_id', (string) ($user['orcid_id'] ?? '')) ?>" maxlength="60" placeholder="0000-0002-1825-0097" autocomplete="off">
                    <p class="settings-hint">Your 16-digit iD from orcid.org. You can paste the full link.</p>
                </div>
                <div class="settings-field">
                    <label for="profile_google_scholar_url">Google Scholar</label>
                    <input id="profile_google_scholar_url" type="url" name="google_scholar_url" value="<?= old('profile_google_scholar_url', (string) ($user['google_scholar_url'] ?? '')) ?>" maxlength="255" placeholder="https://scholar.google.com/citations?user=...">
                    <p class="settings-hint">Open your Google Scholar profile and copy the address from the browser.</p>
                </div>
                <div class="settings-field">
                    <label for="profile_researchgate_url">ResearchGate</label>
                    <input id="profile_researchgate_url" type="url" name="researchgate_url" value="<?= old('profile_researchgate_url', (string) ($user['researchgate_url'] ?? '')) ?>" maxlength="255" placeholder="https://www.researchgate.net/profile/Your-Name">
                    <p class="settings-hint">Open your ResearchGate profile and copy the address from the browser.</p>
                </div>
                <div class="settings-field">
                    <label for="profile_github_url">GitHub</label>
                    <input id="profile_github_url" name="github_url" value="<?= old('profile_github_url', (string) ($user['github_url'] ?? '')) ?>" maxlength="255" placeholder="https://github.com/username" autocomplete="off">
                    <p class="settings-hint">Your GitHub username or profile link.</p>
                </div>
            </div>
            <div class="settings-actions">
                <button type="submit" class="btn btn-accent">Save Researcher Profiles</button>
            </div>
        </form>
    </section>

    <div class="settings-pair">
        <section class="settings-card">
            <header class="settings-card-head">
                <h2>Signature</h2>
                <p>A clear image of your handwritten signature, used on printed forms (JPG or PNG, up to 2 MB).</p>
            </header>

            <div class="settings-signature">
                <?php if ($hasSignature): ?>
                    <img src="<?= base_url('signatures/' . $userId) ?>" alt="Your signature" class="profile-signature-image">
                <?php else: ?>
                    <span>No signature uploaded yet.</span>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= base_url('profile/signature') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="settings-field">
                    <label for="profile_signature">Signature image</label>
                    <input id="profile_signature" type="file" name="signature" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required>
                </div>
                <div class="settings-actions">
                    <button type="submit" class="btn btn-accent"><?= $hasSignature ? 'Replace Signature' : 'Upload Signature' ?></button>
                </div>
            </form>

            <?php if ($hasSignature): ?>
                <form method="post" action="<?= base_url('profile/signature/remove') ?>" class="settings-secondary-action">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline btn-sm">Remove signature</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="settings-card">
            <header class="settings-card-head">
                <h2>Change Password</h2>
                <p>Enter your current password, then choose a new one with at least 8 characters.</p>
            </header>

            <form method="post" action="<?= base_url('profile/password') ?>">
                <?= csrf_field() ?>
                <div class="settings-field">
                    <label for="profile_current_password">Current password</label>
                    <input id="profile_current_password" type="password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="settings-grid">
                    <div class="settings-field">
                        <label for="profile_password">New password</label>
                        <input id="profile_password" type="password" name="password" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="settings-field">
                        <label for="profile_password_confirmation">Confirm new password</label>
                        <input id="profile_password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <div class="settings-actions">
                    <button type="submit" class="btn btn-accent">Update Password</button>
                </div>
            </form>
        </section>
    </div>
</div>
