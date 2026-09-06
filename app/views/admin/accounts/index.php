<?php

/** @var list<array<string, mixed>> $accounts */

/** @var list<array<string, mixed>> $pendingRegistrations */

/** @var list<array<string, mixed>> $roles */

/** @var list<array<string, mixed>> $colleges */

/** @var array<string, int> $collegeScopedSlugs */

/** @var int $defaultFacultyRoleId */

/** @var int $currentUserId */

$pageTitle = 'Manage Accounts — RIDE IMS';

$pageHeading = 'Manage Accounts';

$pageSubtitle = 'Review self-registrations, assign roles, and manage active accounts.';



$pendingCount = count($pendingRegistrations);

$activeCount = count($accounts);

$collegeCount = count($colleges);

?>



<div class="accounts-page">

    <div class="accounts-stats grid grid-4">

        <div class="card stat accounts-stat">

            <div class="value"><?= $pendingCount ?></div>

            <div class="label">Pending Registrations</div>

        </div>

        <div class="card stat accounts-stat">

            <div class="value"><?= $activeCount ?></div>

            <div class="label">Active Accounts</div>

        </div>

        <div class="card stat accounts-stat">

            <div class="value"><?= $collegeCount ?></div>

            <div class="label">Colleges</div>

        </div>

        <div class="card stat accounts-stat">

            <div class="value"><?= count($roles) ?></div>

            <div class="label">Roles</div>

        </div>

    </div>



    <?php if ($pendingCount > 0): ?>

    <div class="card accounts-pending-card">

        <div class="accounts-card-header">

            <div>

                <h2>Pending Registrations</h2>

                <p class="muted">These users registered from the sign-in page and cannot access the system until you approve them and assign a role. Faculty is the typical first role for new registrants.</p>

            </div>

            <span class="accounts-count-badge"><?= $pendingCount ?> awaiting</span>

        </div>



        <div class="accounts-pending-list">

            <?php foreach ($pendingRegistrations as $pending): ?>

                <?php

                $pendingName = trim((string) (($pending['first_name'] ?? '') . ' ' . ($pending['last_name'] ?? '')));

                $pendingId = (int) $pending['id'];

                ?>

                <article class="accounts-pending-item">

                    <div class="accounts-pending-meta">

                        <strong class="accounts-pending-name"><?= htmlspecialchars($pendingName) ?></strong>

                        <span class="accounts-pending-email"><code><?= htmlspecialchars((string) $pending['email']) ?></code></span>

                        <span class="accounts-pending-detail">

                            <i class="fas fa-university" aria-hidden="true"></i>

                            <?= htmlspecialchars((string) ($pending['college_name'] ?? '—')) ?>

                        </span>

                        <span class="accounts-pending-detail">

                            <i class="fas fa-clock" aria-hidden="true"></i>

                            Requested <?= htmlspecialchars((string) ($pending['created_at'] ?? '—')) ?>

                        </span>

                    </div>



                    <div class="accounts-pending-actions">

                        <form method="post" action="<?= base_url('admin/accounts/' . $pendingId . '/approve') ?>" class="accounts-approval-form">

                            <?= csrf_field() ?>



                            <div class="accounts-form-field">

                                <label for="pending_role_<?= $pendingId ?>">Approve as</label>

                                <select id="pending_role_<?= $pendingId ?>" name="role_id" required>

                                    <?php foreach ($roles as $role): ?>

                                        <?php

                                        $roleId = (int) $role['id'];

                                        $selected = $defaultFacultyRoleId > 0

                                            ? $roleId === $defaultFacultyRoleId

                                            : false;

                                        $needsCollege = isset($collegeScopedSlugs[(string) $role['slug']]);

                                        ?>

                                        <option value="<?= $roleId ?>" <?= $selected ? 'selected' : '' ?>>

                                            <?= htmlspecialchars((string) $role['name']) ?><?= $needsCollege ? ' (college required)' : '' ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>



                            <div class="accounts-form-field">

                                <label for="pending_college_<?= $pendingId ?>">College</label>

                                <select id="pending_college_<?= $pendingId ?>" name="college_id">

                                    <option value="">University-wide / none</option>

                                    <?php foreach ($colleges as $college): ?>

                                        <option value="<?= (int) $college['id'] ?>" <?= (int) ($pending['college_id'] ?? 0) === (int) $college['id'] ? 'selected' : '' ?>>

                                            <?= htmlspecialchars((string) $college['name']) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>



                            <div class="accounts-form-field">

                                <label for="pending_program_<?= $pendingId ?>">Program</label>

                                <input

                                    id="pending_program_<?= $pendingId ?>"

                                    name="program"

                                    maxlength="150"

                                    placeholder="Optional"

                                    value="<?= htmlspecialchars((string) ($pending['program'] ?? '')) ?>"

                                >

                            </div>



                            <div class="accounts-approval-buttons">

                                <button type="submit" class="btn btn-sm btn-accent">Approve</button>

                                <button

                                    type="submit"

                                    class="btn btn-sm btn-danger"

                                    formaction="<?= base_url('admin/accounts/' . $pendingId . '/reject') ?>"

                                    formmethod="post"

                                    formnovalidate

                                    onclick="return confirm('Reject and remove this registration request?');"

                                >Reject</button>

                            </div>

                        </form>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </div>

    <?php endif; ?>



    <div class="accounts-management-grid">

        <div class="card accounts-form-card">

            <h2>Add Account</h2>

            <p class="muted">Accounts added here become active immediately. College is required for coordinators, dean, faculty, and external partner roles. Program is optional.</p>



            <form method="post" action="<?= base_url('admin/accounts') ?>" class="accounts-form-grid">

                <?= csrf_field() ?>



                <div class="accounts-form-field">

                    <label for="account_first_name">First Name</label>

                    <input id="account_first_name" name="first_name" value="<?= old('first_name') ?>" required maxlength="80">

                </div>



                <div class="accounts-form-field">

                    <label for="account_last_name">Last Name</label>

                    <input id="account_last_name" name="last_name" value="<?= old('last_name') ?>" required maxlength="80">

                </div>



                <div class="accounts-form-field">

                    <label for="account_email">Email</label>

                    <input id="account_email" type="email" name="email" value="<?= old('email') ?>" required maxlength="150">

                </div>



                <div class="accounts-form-field">

                    <label for="account_password">Password</label>

                    <input id="account_password" type="password" name="password" required minlength="8">

                </div>



                <div class="accounts-form-field">

                    <label for="account_role_id">Role</label>

                    <select id="account_role_id" name="role_id" required>

                        <option value="">Select role</option>

                        <?php foreach ($roles as $role): ?>

                            <?php

                            $selected = old('role_id') === (string) $role['id'];

                            $needsCollege = isset($collegeScopedSlugs[(string) $role['slug']]);

                            ?>

                            <option value="<?= (int) $role['id'] ?>" <?= $selected ? 'selected' : '' ?>>

                                <?= htmlspecialchars((string) $role['name']) ?><?= $needsCollege ? ' (college required)' : '' ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <div class="accounts-form-field">

                    <label for="account_college_id">College</label>

                    <select id="account_college_id" name="college_id">

                        <option value="">University-wide / none</option>

                        <?php foreach ($colleges as $college): ?>

                            <option value="<?= (int) $college['id'] ?>" <?= old('college_id') === (string) $college['id'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars((string) $college['name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <div class="accounts-form-field accounts-form-field-span-2">

                    <label for="account_program">Program</label>

                    <input id="account_program" name="program" value="<?= old('program') ?>" maxlength="150" placeholder="e.g. BS Computer Engineering">

                </div>



                <div class="accounts-form-actions accounts-form-field-span-2">

                    <button type="submit" class="btn btn-accent">Add Account</button>

                </div>

            </form>

        </div>



        <div class="card accounts-college-card">

            <h2>College Settings</h2>

            <p class="muted">Add colleges for account assignment and proposal prefill. A college can only be deleted when no accounts or proposals are linked to it.</p>



            <form method="post" action="<?= base_url('admin/accounts/colleges/default-centers') ?>" class="accounts-inline-action">

                <?= csrf_field() ?>

                <button type="submit" class="btn btn-outline btn-sm">Add Listed Centers</button>

            </form>



            <form method="post" action="<?= base_url('admin/accounts/colleges') ?>" class="accounts-form-grid accounts-college-add-form">

                <?= csrf_field() ?>



                <div class="accounts-form-field">

                    <label for="college_code">College Code</label>

                    <input id="college_code" name="college_code" value="<?= old('college_code') ?>" required maxlength="20" placeholder="e.g. CIT" class="no-capitalize">

                </div>



                <div class="accounts-form-field">

                    <label for="college_name">College Name</label>

                    <input id="college_name" name="college_name" value="<?= old('college_name') ?>" required maxlength="150" placeholder="e.g. College of Information Technology">

                </div>



                <div class="accounts-form-actions accounts-form-field-span-2">

                    <button type="submit" class="btn btn-accent">Add College</button>

                </div>

            </form>



            <?php if ($collegeCount === 0): ?>

                <p class="muted accounts-empty-note">No colleges added yet.</p>

            <?php else: ?>

                <div class="accounts-table-wrap accounts-college-table-wrap">

                    <table class="accounts-table accounts-college-table">

                        <thead>

                            <tr>

                                <th>Code</th>

                                <th>Name</th>

                                <th class="col-actions">Actions</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($colleges as $college): ?>

                            <tr>

                                <td><code><?= htmlspecialchars((string) $college['code']) ?></code></td>

                                <td><?= htmlspecialchars((string) $college['name']) ?></td>

                                <td class="col-actions">

                                    <div class="accounts-row-actions">

                                        <a class="btn btn-sm icon-btn" href="<?= base_url('admin/accounts/colleges/' . $college['id'] . '/edit') ?>" title="Edit college" aria-label="Edit college">
                                            <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                                        </a>

                                        <form

                                            method="post"

                                            action="<?= base_url('admin/accounts/colleges/' . $college['id'] . '/delete') ?>"

                                            onsubmit="return confirm('Delete this college?');"

                                        >

                                            <?= csrf_field() ?>

                                            <button type="submit" class="btn btn-sm btn-danger icon-btn" title="Delete college" aria-label="Delete college">
                                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>



    <div class="card accounts-list-card">

        <div class="accounts-card-header">

            <div>

                <h2>Active Accounts</h2>

                <p class="muted">Only active accounts are shown below. Removing an account deactivates it instead of deleting related records.</p>

            </div>

        </div>



        <?php if ($activeCount === 0): ?>

            <p class="accounts-empty-note">No active accounts found.</p>

        <?php else: ?>

            <div class="accounts-list-toolbar">

                <span class="muted accounts-list-count" id="accounts-list-count"><?= $activeCount ?> account<?= $activeCount === 1 ? '' : 's' ?></span>

                <input

                    type="search"

                    id="accounts-search"

                    class="accounts-search no-capitalize"

                    placeholder="Search name, email, role, college…"

                    aria-label="Search active accounts"

                >

            </div>



            <div class="accounts-table-wrap">

                <table class="accounts-table" id="accounts-table">

                    <thead>

                        <tr>

                            <th>Name</th>

                            <th class="col-email">Email</th>

                            <th>Role</th>

                            <th>College</th>

                            <th class="col-program">Program</th>

                            <th class="col-created">Created</th>

                            <th class="col-actions">Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($accounts as $account): ?>

                        <?php

                        $isCurrentUser = $currentUserId === (int) $account['id'];

                        $accountName = trim((string) (($account['first_name'] ?? '') . ' ' . ($account['last_name'] ?? '')));

                        $searchText = strtolower(implode(' ', [

                            $accountName,

                            (string) ($account['email'] ?? ''),

                            (string) ($account['role_names'] ?? ''),

                            (string) ($account['college_name'] ?? ''),

                            (string) ($account['program'] ?? ''),

                        ]));

                        ?>

                        <tr data-search="<?= htmlspecialchars($searchText) ?>">

                            <td><?= htmlspecialchars($accountName) ?></td>

                            <td class="col-email"><code><?= htmlspecialchars((string) $account['email']) ?></code></td>

                            <td><?= htmlspecialchars((string) ($account['role_names'] ?? '—')) ?></td>

                            <td><?= htmlspecialchars((string) ($account['college_name'] ?? 'University-wide')) ?></td>

                            <td class="col-program" title="<?= htmlspecialchars((string) ($account['program'] ?? '')) ?>"><?= htmlspecialchars((string) ($account['program'] ?? '—')) ?></td>

                            <td class="col-created"><?= htmlspecialchars((string) ($account['created_at'] ?? '—')) ?></td>

                            <td class="col-actions">

                                <div class="accounts-row-actions">

                                    <a class="btn btn-sm icon-btn" href="<?= base_url('admin/accounts/' . $account['id'] . '/edit') ?>" title="Edit account" aria-label="Edit account">
                                        <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                                    </a>

                                    <?php if ($isCurrentUser): ?>

                                        <span class="muted accounts-current-tag">You</span>

                                    <?php else: ?>

                                        <form method="post" action="<?= base_url('admin/accounts/' . $account['id'] . '/delete') ?>" onsubmit="return confirm('Remove this account?');">

                                            <?= csrf_field() ?>

                                            <button type="submit" class="btn btn-sm btn-danger icon-btn" title="Remove account" aria-label="Remove account">
                                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


