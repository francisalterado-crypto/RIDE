<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\Cluster;
use App\Models\College;
use App\Models\Role;
use App\Models\User;

final class AccountController
{
    public function index(): void
    {
        $this->assertAdmin();

        $facultyRole = Role::findBySlug('faculty');

        view('admin.accounts.index', [
            'accounts' => User::allActiveWithRoles(),
            'inactiveAccounts' => User::allInactiveWithRoles(),
            'pendingRegistrations' => User::allPendingRegistrations(),
            'roles' => Role::all(),
            'colleges' => College::all(),
            'clusters' => Cluster::all(),
            'collegeScopedSlugs' => array_flip(Role::collegeScopedSlugs()),
            'defaultFacultyRoleId' => $facultyRole !== null ? (int) $facultyRole['id'] : 0,
            'currentUserId' => (int) ($_SESSION['user_id'] ?? 0),
        ]);
    }

    public function store(): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts');
        }

        $data = [
            'email' => trim((string) ($_POST['email'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'college_id' => (int) ($_POST['college_id'] ?? 0) ?: null,
            'program' => trim((string) ($_POST['program'] ?? '')),
            'campus_id' => null,
        ];
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $_SESSION['_old'] = [
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'college_id' => (string) ($data['college_id'] ?? ''),
            'program' => $data['program'],
            'role_id' => (string) $roleId,
        ];

        if (
            $data['email'] === ''
            || $data['first_name'] === ''
            || $data['last_name'] === ''
            || $data['password'] === ''
        ) {
            set_flash('error', 'Please complete all required account fields.');
            redirect('admin/accounts');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please provide a valid email address.');
            redirect('admin/accounts');
        }

        if (strlen($data['password']) < 8) {
            set_flash('error', 'Password must be at least 8 characters.');
            redirect('admin/accounts');
        }

        if (User::emailExists($data['email'])) {
            set_flash('error', 'That email address is already in use.');
            redirect('admin/accounts');
        }

        $role = Role::findById($roleId);
        if ($role === null) {
            set_flash('error', 'Please choose a valid role.');
            redirect('admin/accounts');
        }

        $roleSlug = (string) $role['slug'];
        if (Role::requiresCollege($roleSlug) && $data['college_id'] === null) {
            set_flash('error', 'Selected role requires a college assignment.');
            redirect('admin/accounts');
        }

        $userId = User::create($data);
        User::assignRole($userId, $roleId, $data['college_id']);

        AuditLog::record('user', $userId, 'create', [
            'email' => $data['email'],
            'role' => $roleSlug,
        ]);

        unset($_SESSION['_old']);
        set_flash('success', 'Account added successfully.');
        redirect('admin/accounts');
    }

    public function edit(int $id): void
    {
        $this->assertAdmin();

        $account = User::findActiveWithPrimaryRoleById($id);
        if ($account === null) {
            set_flash('error', 'Account not found.');
            redirect('admin/accounts');
        }

        view('admin.accounts.edit', [
            'account' => $account,
            'roles' => Role::all(),
            'colleges' => College::all(),
            'collegeScopedSlugs' => array_flip(Role::collegeScopedSlugs()),
            'currentUserId' => (int) ($_SESSION['user_id'] ?? 0),
        ]);
    }

    public function update(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        $account = User::findActiveWithPrimaryRoleById($id);
        if ($account === null) {
            set_flash('error', 'Account not found.');
            redirect('admin/accounts');
        }

        $data = [
            'email' => trim((string) ($_POST['email'] ?? '')),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'college_id' => (int) ($_POST['college_id'] ?? 0) ?: null,
            'program' => trim((string) ($_POST['program'] ?? '')),
            'campus_id' => null,
        ];
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $_SESSION['_old'] = [
            'edit_email' => $data['email'],
            'edit_first_name' => $data['first_name'],
            'edit_last_name' => $data['last_name'],
            'edit_college_id' => (string) ($data['college_id'] ?? ''),
            'edit_program' => $data['program'],
            'edit_role_id' => (string) $roleId,
        ];

        if ($data['email'] === '' || $data['first_name'] === '' || $data['last_name'] === '') {
            set_flash('error', 'Please complete all required account fields.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please provide a valid email address.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        if (User::emailExistsExcept($data['email'], $id)) {
            set_flash('error', 'That email address is already in use.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        $role = Role::findById($roleId);
        if ($role === null) {
            set_flash('error', 'Please choose a valid role.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        $currentRoles = User::roleSlugs($id);
        $nextRoleSlug = (string) $role['slug'];

        if (Role::requiresCollege($nextRoleSlug) && $data['college_id'] === null) {
            set_flash('error', 'Selected role requires a college assignment.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        if ($currentUserId === $id && $nextRoleSlug !== 'ride_admin') {
            set_flash('error', 'You cannot remove admin access from the account you are currently using.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        if (
            in_array('ride_admin', $currentRoles, true)
            && $nextRoleSlug !== 'ride_admin'
            && User::countActiveUsersByRoleSlug('ride_admin') <= 1
        ) {
            set_flash('error', 'At least one active admin account must remain.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        User::updateProfile($id, $data);
        User::replaceRoles($id, $roleId, $data['college_id']);

        if ($currentUserId === $id) {
            $this->refreshCurrentSessionRoles();
        }

        unset($_SESSION['_old']);
        AuditLog::record('user', $id, 'update', [
            'email' => $data['email'],
            'role' => $nextRoleSlug,
            'college_id' => $data['college_id'],
            'program' => $data['program'],
        ]);

        set_flash('success', 'Account updated successfully.');
        redirect('admin/accounts/' . $id . '/edit');
    }

    public function resetPassword(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        $account = User::findActiveWithPrimaryRoleById($id);
        if ($account === null) {
            set_flash('error', 'Account not found.');
            redirect('admin/accounts');
        }

        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($password === '' || $passwordConfirmation === '') {
            set_flash('error', 'Enter and confirm the new password.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        if (strlen($password) < 8) {
            set_flash('error', 'Password must be at least 8 characters.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        if ($password !== $passwordConfirmation) {
            set_flash('error', 'Password confirmation does not match.');
            redirect('admin/accounts/' . $id . '/edit');
        }

        User::updatePassword($id, $password);

        AuditLog::record('user', $id, 'reset_password', [
            'email' => $account['email'],
        ]);

        set_flash('success', 'Password reset successfully.');
        redirect('admin/accounts/' . $id . '/edit');
    }

    public function destroy(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts');
        }

        $account = User::findAnyById($id);
        if ($account === null || !(bool) $account['is_active']) {
            set_flash('error', 'Account not found.');
            redirect('admin/accounts');
        }

        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        if ($currentUserId === $id) {
            set_flash('error', 'You cannot remove the account you are currently using.');
            redirect('admin/accounts');
        }

        $roleSlugs = User::roleSlugs($id);
        if (in_array('ride_admin', $roleSlugs, true) && User::countActiveUsersByRoleSlug('ride_admin') <= 1) {
            set_flash('error', 'At least one active admin account must remain.');
            redirect('admin/accounts');
        }

        if (!User::deactivate($id)) {
            set_flash('error', 'Unable to remove that account right now.');
            redirect('admin/accounts');
        }

        AuditLog::record('user', $id, 'deactivate', [
            'email' => $account['email'],
            'roles' => $roleSlugs,
        ]);

        set_flash('success', 'Account removed successfully.');
        redirect('admin/accounts');
    }

    public function approve(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts');
        }

        $pending = User::findPendingById($id);
        if ($pending === null) {
            set_flash('error', 'Pending registration not found.');
            redirect('admin/accounts');
        }

        $roleId = (int) ($_POST['role_id'] ?? 0);
        $collegeId = (int) ($_POST['college_id'] ?? 0) ?: null;
        $program = trim((string) ($_POST['program'] ?? ''));

        $role = Role::findById($roleId);
        if ($role === null) {
            set_flash('error', 'Please choose a valid role.');
            redirect('admin/accounts');
        }

        $roleSlug = (string) $role['slug'];
        if (Role::requiresCollege($roleSlug) && $collegeId === null) {
            set_flash('error', 'Selected role requires a college assignment.');
            redirect('admin/accounts');
        }

        if (!User::approveRegistration($id, $roleId, $collegeId, $program)) {
            set_flash('error', 'Unable to approve that registration right now.');
            redirect('admin/accounts');
        }

        AuditLog::record('user', $id, 'approve_registration', [
            'email' => $pending['email'],
            'role' => $roleSlug,
            'college_id' => $collegeId,
        ]);

        set_flash('success', 'Registration approved. The user can now sign in.');
        redirect('admin/accounts');
    }

    public function reject(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts');
        }

        $pending = User::findPendingById($id);
        if ($pending === null) {
            set_flash('error', 'Pending registration not found.');
            redirect('admin/accounts');
        }

        if (!User::rejectRegistration($id)) {
            set_flash('error', 'Unable to reject that registration right now.');
            redirect('admin/accounts');
        }

        AuditLog::record('user', $id, 'reject_registration', [
            'email' => $pending['email'],
        ]);

        set_flash('success', 'Registration request rejected and removed.');
        redirect('admin/accounts');
    }

    public function restore(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts');
        }

        $account = User::findAnyById($id);
        if ($account === null || (bool) $account['is_active']) {
            set_flash('error', 'Inactive account not found.');
            redirect('admin/accounts');
        }

        if (!User::hasRoles($id)) {
            set_flash('error', 'This account is awaiting registration approval, not deactivation.');
            redirect('admin/accounts');
        }

        if (!User::activate($id)) {
            set_flash('error', 'Unable to restore that account right now.');
            redirect('admin/accounts');
        }

        AuditLog::record('user', $id, 'restore', [
            'email' => $account['email'],
            'roles' => User::roleSlugs($id),
        ]);

        set_flash('success', 'Account restored successfully.');
        redirect('admin/accounts');
    }

    public function storeCollege(): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts');
        }

        $code = strtoupper(trim((string) ($_POST['college_code'] ?? '')));
        $name = trim((string) ($_POST['college_name'] ?? ''));
        $clusterId = (int) ($_POST['cluster_id'] ?? 0) ?: null;

        if ($code === '' || $name === '') {
            set_flash('error', 'College code and name are required.');
            $_SESSION['_old'] = ['college_code' => $code, 'college_name' => $name];
            redirect('admin/accounts');
        }

        if (strlen($code) > 20 || !preg_match('/^[A-Z0-9._-]+$/', $code)) {
            set_flash('error', 'College code must be 20 characters or less and use letters, numbers, dots, dashes, or underscores.');
            $_SESSION['_old'] = ['college_code' => $code, 'college_name' => $name];
            redirect('admin/accounts');
        }

        if (strlen($name) > 150) {
            set_flash('error', 'College name must be 150 characters or less.');
            $_SESSION['_old'] = ['college_code' => $code, 'college_name' => $name];
            redirect('admin/accounts');
        }

        if (College::codeExists($code)) {
            set_flash('error', 'That college code is already in use.');
            $_SESSION['_old'] = ['college_code' => $code, 'college_name' => $name];
            redirect('admin/accounts');
        }

        $collegeId = College::create($code, $name, $clusterId);

        AuditLog::record('college', $collegeId, 'create', [
            'code' => $code,
            'name' => $name,
        ]);

        set_flash('success', 'College added successfully.');
        redirect('admin/accounts');
    }

    public function editCollege(int $id): void
    {
        $this->assertAdmin();

        $college = College::findById($id);
        if ($college === null) {
            set_flash('error', 'College not found.');
            redirect('admin/accounts');
        }

        view('admin.accounts.college-edit', [
            'college' => $college,
            'clusters' => Cluster::all(),
        ]);
    }

    public function updateCollege(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts/colleges/' . $id . '/edit');
        }

        $college = College::findById($id);
        if ($college === null) {
            set_flash('error', 'College not found.');
            redirect('admin/accounts');
        }

        $code = strtoupper(trim((string) ($_POST['college_code'] ?? '')));
        $name = trim((string) ($_POST['college_name'] ?? ''));
        $clusterId = (int) ($_POST['cluster_id'] ?? 0) ?: null;

        $_SESSION['_old'] = [
            'edit_college_code' => $code,
            'edit_college_name' => $name,
        ];

        if ($code === '' || $name === '') {
            set_flash('error', 'College code and name are required.');
            redirect('admin/accounts/colleges/' . $id . '/edit');
        }

        if (strlen($code) > 20 || !preg_match('/^[A-Z0-9._-]+$/', $code)) {
            set_flash('error', 'College code must be 20 characters or less and use letters, numbers, dots, dashes, or underscores.');
            redirect('admin/accounts/colleges/' . $id . '/edit');
        }

        if (strlen($name) > 150) {
            set_flash('error', 'College name must be 150 characters or less.');
            redirect('admin/accounts/colleges/' . $id . '/edit');
        }

        if (College::codeExists($code, $id)) {
            set_flash('error', 'That college code is already in use.');
            redirect('admin/accounts/colleges/' . $id . '/edit');
        }

        College::update($id, $code, $name, $clusterId);

        AuditLog::record('college', $id, 'update', [
            'code' => $code,
            'name' => $name,
            'previous_code' => $college['code'],
            'previous_name' => $college['name'],
        ]);

        unset($_SESSION['_old']);
        set_flash('success', 'College updated successfully.');
        redirect('admin/accounts/colleges/' . $id . '/edit');
    }

    public function destroyCollege(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts');
        }

        $college = College::findById($id);
        if ($college === null) {
            set_flash('error', 'College not found.');
            redirect('admin/accounts');
        }

        $reason = College::deleteBlockingReason($id);
        if ($reason !== null) {
            set_flash('error', $reason);
            redirect('admin/accounts');
        }

        if (!College::delete($id)) {
            set_flash('error', 'Unable to delete that college right now.');
            redirect('admin/accounts');
        }

        AuditLog::record('college', $id, 'delete', [
            'code' => $college['code'],
            'name' => $college['name'],
        ]);

        set_flash('success', 'College deleted successfully.');
        redirect('admin/accounts');
    }

    public function storeCluster(): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts');
        }

        $name = trim((string) ($_POST['cluster_name'] ?? ''));

        if ($name === '') {
            set_flash('error', 'Cluster name is required.');
            redirect('admin/accounts');
        }

        if (strlen($name) > 100) {
            set_flash('error', 'Cluster name must be 100 characters or less.');
            redirect('admin/accounts');
        }

        if (Cluster::nameExists($name)) {
            set_flash('error', 'That cluster name is already in use.');
            redirect('admin/accounts');
        }

        $clusterId = Cluster::create($name);

        AuditLog::record('cluster', $clusterId, 'create', ['name' => $name]);

        set_flash('success', 'Cluster added successfully.');
        redirect('admin/accounts');
    }

    public function editCluster(int $id): void
    {
        $this->assertAdmin();

        $cluster = Cluster::findById($id);
        if ($cluster === null) {
            set_flash('error', 'Cluster not found.');
            redirect('admin/accounts');
        }

        view('admin.accounts.cluster-edit', [
            'cluster' => $cluster,
            'colleges' => College::all(),
            'memberCollegeIds' => array_map(static fn (array $c): int => (int) $c['id'], Cluster::colleges($id)),
        ]);
    }

    public function updateCluster(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts/clusters/' . $id . '/edit');
        }

        $cluster = Cluster::findById($id);
        if ($cluster === null) {
            set_flash('error', 'Cluster not found.');
            redirect('admin/accounts');
        }

        $name = trim((string) ($_POST['cluster_name'] ?? ''));

        if ($name === '') {
            set_flash('error', 'Cluster name is required.');
            redirect('admin/accounts/clusters/' . $id . '/edit');
        }

        if (strlen($name) > 100) {
            set_flash('error', 'Cluster name must be 100 characters or less.');
            redirect('admin/accounts/clusters/' . $id . '/edit');
        }

        if (Cluster::nameExists($name, $id)) {
            set_flash('error', 'That cluster name is already in use.');
            redirect('admin/accounts/clusters/' . $id . '/edit');
        }

        $collegeIds = array_map('intval', (array) ($_POST['college_ids'] ?? []));

        Cluster::update($id, $name);
        Cluster::syncColleges($id, $collegeIds);

        AuditLog::record('cluster', $id, 'update', [
            'name' => $name,
            'previous_name' => $cluster['name'],
            'college_ids' => $collegeIds,
        ]);

        set_flash('success', 'Cluster updated successfully.');
        redirect('admin/accounts/clusters/' . $id . '/edit');
    }

    public function destroyCluster(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts');
        }

        $cluster = Cluster::findById($id);
        if ($cluster === null) {
            set_flash('error', 'Cluster not found.');
            redirect('admin/accounts');
        }

        Cluster::delete($id);

        AuditLog::record('cluster', $id, 'delete', ['name' => $cluster['name']]);

        set_flash('success', 'Cluster deleted successfully. Its colleges are now unassigned.');
        redirect('admin/accounts');
    }

    public function addDefaultCenters(): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/accounts');
        }

        $centers = [
            ['code' => 'TPSP', 'name' => 'The Palawan Scientists and Publications (TPS&P)'],
            ['code' => 'BDLEC', 'name' => 'BioDiscovery Learning Center (BDLeC)'],
            ['code' => 'ABBA', 'name' => 'Aquatic, Biodiversity, Biotechnology and Aquaculture (ABBA) R&D Center'],
            ['code' => 'TBTI', 'name' => 'Palawan Too-Big-To-Ignore (TBTI Hub)'],
            ['code' => 'HALAL', 'name' => 'Halal Research and Training (Halal) Center'],
            ['code' => 'TELP', 'name' => 'Tribal Ecosystems and Livelihood Partnerships (TELP)'],
            ['code' => 'COTLEC', 'name' => 'Community-based Technology and Learning Center (CotLeC)'],
            ['code' => 'SSRDEC', 'name' => 'Social Science Research and Development Center (SSRDeC)'],
            ['code' => 'ASL', 'name' => 'Agritourism Showcase Leisure (ASL Park)'],
            ['code' => 'FPC', 'name' => 'Food Processing Center'],
            ['code' => 'ATBI', 'name' => 'Agri-Aqua Technology Business Incubation (ATBI) Center'],
            ['code' => 'AMCEN', 'name' => 'Regional Advanced Manufacturing Center (AMCen)'],
            ['code' => 'MEIC', 'name' => 'Metals & Engineering Innovation Center (MEIC)'],
            ['code' => 'KIH', 'name' => 'Knowledge and Innovation Hub'],
            ['code' => 'CRAFIT', 'name' => 'Cashew Research and Food Technology Innovation Center (CRAFIT)'],
            ['code' => 'PALBIORECC', 'name' => 'Palawan Biodiversity Research and Conservation Center (PALBioReCC)'],
            ['code' => 'GREAT', 'name' => 'Agro-Ecological Farming System (GREAT) Center'],
            ['code' => 'BMRS', 'name' => 'Binduyan Marine Research Station (BMRS)'],
            ['code' => 'ICENAQUA', 'name' => 'Inland Center for Aquaculture R&D (ICEN-AQUA)'],
            ['code' => 'WPS-CORP', 'name' => 'West Philippine Sea Conservation, Research & Policy (WPS-CoRP) Center'],
        ];

        $added = 0;
        foreach ($centers as $center) {
            $code = strtoupper(trim((string) $center['code']));
            $name = trim((string) $center['name']);

            if ($code === '' || $name === '') {
                continue;
            }

            if (College::codeExists($code)) {
                continue;
            }

            College::create($code, $name);
            $added++;
        }

        if ($added === 0) {
            set_flash('success', 'All listed centers are already in College Settings.');
            redirect('admin/accounts');
        }

        set_flash('success', $added . ' center(s) added to College Settings.');
        redirect('admin/accounts');
    }

    private function assertAdmin(): void
    {
        if (!Auth::hasRole('ride_admin')) {
            http_response_code(403);
            view('errors.403');
            exit;
        }
    }

    private function refreshCurrentSessionRoles(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return;
        }

        $_SESSION['user_roles'] = User::roleSlugs((int) $userId);
    }
}
