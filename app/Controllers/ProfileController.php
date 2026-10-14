<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAvatar;
use App\Models\UserSignature;
use App\Support\ResearcherProfiles;

final class ProfileController
{
    public function index(): void
    {
        $user = Auth::user();
        if ($user === null) {
            redirect('login');
        }

        $userId = (int) $user['id'];
        $avatarUrl = UserAvatar::url($userId);

        view('profile.index', [
            'user' => $user,
            'colleges' => College::all(),
            'hasSignature' => UserSignature::filePath($userId) !== null,
            'hasAvatar' => $avatarUrl !== null,
            'avatarUrl' => $avatarUrl,
        ]);
    }

    public function updateResearcherProfiles(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('profile');
        }

        $user = Auth::user();
        if ($user === null) {
            redirect('login');
        }

        $validators = [
            'orcid_id' => [ResearcherProfiles::class, 'orcid'],
            'google_scholar_url' => [ResearcherProfiles::class, 'googleScholar'],
            'researchgate_url' => [ResearcherProfiles::class, 'researchGate'],
            'github_url' => [ResearcherProfiles::class, 'github'],
        ];
        $profiles = [];
        $errors = [];
        $old = [];
        foreach ($validators as $field => $validator) {
            $raw = trim((string) ($_POST[$field] ?? ''));
            $old['profile_' . $field] = $raw;
            [$profiles[$field], $error] = $validator($raw);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        if ($errors !== []) {
            $_SESSION['_old'] = $old;
            set_flash('error', implode(' ', $errors));
            redirect('profile#researcher-profiles');
        }

        unset($_SESSION['_old']);
        $userId = (int) $user['id'];
        User::updateResearcherProfiles($userId, $profiles);
        AuditLog::record('user', $userId, 'researcher_profiles_update', $profiles);

        set_flash('success', 'Researcher profiles saved.');
        redirect('profile#researcher-profiles');
    }

    public function update(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('profile');
        }

        $user = Auth::user();
        if ($user === null) {
            redirect('login');
        }

        $userId = (int) $user['id'];
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $collegeId = (int) ($_POST['college_id'] ?? 0) ?: null;
        $program = trim((string) ($_POST['program'] ?? ''));

        $_SESSION['_old'] = [
            'profile_first_name' => $firstName,
            'profile_last_name' => $lastName,
            'profile_email' => $email,
            'profile_college_id' => (string) ($collegeId ?? ''),
            'profile_program' => $program,
        ];

        if ($firstName === '' || $lastName === '' || $email === '') {
            set_flash('error', 'First name, last name, and email are required.');
            redirect('profile');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please enter a valid email address.');
            redirect('profile');
        }

        if (User::emailExistsExcept($email, $userId)) {
            set_flash('error', 'That email is already in use by another account.');
            redirect('profile');
        }

        $roleSlugs = User::roleSlugs($userId);
        $requiresCollege = false;
        foreach ($roleSlugs as $slug) {
            if (Role::requiresCollege($slug)) {
                $requiresCollege = true;
                break;
            }
        }

        if ($requiresCollege && $collegeId === null) {
            set_flash('error', 'Please select your college.');
            redirect('profile');
        }

        $avatarUploaded = false;
        $avatarError = (int) (($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE));
        if ($avatarError !== UPLOAD_ERR_NO_FILE) {
            try {
                UserAvatar::store($userId, $_FILES['avatar'] ?? []);
                $avatarUploaded = true;
                AuditLog::record('user', $userId, 'avatar_upload');
            } catch (\Throwable $e) {
                set_flash('error', $e->getMessage());
                redirect('profile');
            }
        }

        User::updateProfile($userId, [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'college_id' => $collegeId,
            'program' => $program,
            'campus_id' => $user['campus_id'] ?? null,
        ]);

        unset($_SESSION['_old']);

        AuditLog::record('user', $userId, 'profile_update', [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'college_id' => $collegeId,
            'program' => $program,
        ]);

        set_flash(
            'success',
            $avatarUploaded ? 'Profile and picture saved.' : 'Profile saved.'
        );
        redirect('profile');
    }

    public function updatePassword(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('profile');
        }

        $user = Auth::user();
        if ($user === null) {
            redirect('login');
        }

        $userId = (int) $user['id'];
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($currentPassword === '' || $password === '' || $passwordConfirmation === '') {
            set_flash('error', 'Enter your current password and confirm the new password.');
            redirect('profile');
        }

        if (!password_verify($currentPassword, (string) ($user['password_hash'] ?? ''))) {
            set_flash('error', 'Current password is incorrect.');
            redirect('profile');
        }

        if (strlen($password) < 8) {
            set_flash('error', 'New password must be at least 8 characters.');
            redirect('profile');
        }

        if ($password !== $passwordConfirmation) {
            set_flash('error', 'New password confirmation does not match.');
            redirect('profile');
        }

        User::updatePassword($userId, $password);
        AuditLog::record('user', $userId, 'password_change');
        set_flash('success', 'Password updated.');
        redirect('profile');
    }

    public function uploadAvatar(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('profile');
        }

        $user = Auth::user();
        if ($user === null) {
            redirect('login');
        }

        $userId = (int) $user['id'];

        try {
            UserAvatar::store($userId, $_FILES['avatar'] ?? []);
            AuditLog::record('user', $userId, 'avatar_upload');
            set_flash('success', 'Profile picture saved.');
        } catch (\Throwable $e) {
            set_flash('error', $e->getMessage());
        }

        redirect('profile');
    }

    public function removeAvatar(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('profile');
        }

        $user = Auth::user();
        if ($user === null) {
            redirect('login');
        }

        $userId = (int) $user['id'];
        UserAvatar::remove($userId);
        AuditLog::record('user', $userId, 'avatar_remove');
        set_flash('success', 'Profile picture removed.');

        redirect('profile');
    }

    public function showAvatar(int $userId): void
    {
        $path = UserAvatar::filePath($userId);
        if ($path === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Not found';
            exit;
        }

        $mime = UserAvatar::mimeType($userId) ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: public, max-age=3600');
        readfile($path);
        exit;
    }

    public function uploadSignature(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('profile');
        }

        $user = Auth::user();
        if ($user === null) {
            redirect('login');
        }

        $userId = (int) $user['id'];

        try {
            UserSignature::store($userId, $_FILES['signature'] ?? []);
            AuditLog::record('user', $userId, 'signature_upload');
            set_flash('success', 'Signature saved. It will appear on your proposals automatically.');
        } catch (\Throwable $e) {
            set_flash('error', $e->getMessage());
        }

        redirect('profile');
    }

    public function removeSignature(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('profile');
        }

        $user = Auth::user();
        if ($user === null) {
            redirect('login');
        }

        $userId = (int) $user['id'];
        UserSignature::remove($userId);
        AuditLog::record('user', $userId, 'signature_remove');
        set_flash('success', 'Signature removed.');

        redirect('profile');
    }

    public function showSignature(int $userId): void
    {
        if (!Auth::check()) {
            http_response_code(403);
            exit;
        }

        $path = UserSignature::filePath($userId);
        if ($path === null) {
            http_response_code(404);
            exit;
        }

        $mime = UserSignature::mimeType($userId) ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }
}
