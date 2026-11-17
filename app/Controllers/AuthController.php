<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\User;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }
        view('auth.login');
    }

    public function login(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('login');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            set_flash('error', 'Invalid email or password.');
            $_SESSION['_old'] = ['email' => $email];
            redirect('login');
        }

        $userId = (int) $user['id'];
        if (!(bool) $user['is_active']) {
            if (!User::hasRoles($userId)) {
                set_flash('error', 'Your registration is pending admin approval. You will be able to sign in once an administrator approves your account.');
            } else {
                set_flash('error', 'This account has been deactivated. Contact an administrator if you need access restored.');
            }
            $_SESSION['_old'] = ['email' => $email];
            redirect('login');
        }

        if (!User::hasRoles($userId)) {
            set_flash('error', 'Your account has no assigned role yet. Contact an administrator.');
            $_SESSION['_old'] = ['email' => $email];
            redirect('login');
        }

        Auth::login($user);
        AuditLog::record('user', (int) $user['id'], 'login');
        redirect('dashboard');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }
        view('auth.register', ['colleges' => College::all()]);
    }

    public function register(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('register');
        }

        $data = [
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'college_id' => (int) ($_POST['college_id'] ?? 0) ?: null,
            'campus_id' => (int) ($_POST['campus_id'] ?? 0) ?: null,
        ];

        $_SESSION['_old'] = $data;

        if ($data['email'] === '' || $data['password'] === '' || strlen($data['password']) < 8) {
            set_flash('error', 'Please complete all required fields (password min 8 characters).');
            redirect('register');
        }

        if (User::emailExists($data['email'])) {
            set_flash('error', 'Email is already registered.');
            redirect('register');
        }

        if ($data['college_id'] === null) {
            set_flash('error', 'Please select a college.');
            redirect('register');
        }

        User::create($data, false);

        unset($_SESSION['_old']);
        set_flash('success', 'Registration submitted. An administrator will review your request and assign access. You can sign in after approval.');
        redirect('login');
    }

    public function logout(): void
    {
        AuditLog::record('user', (int) ($_SESSION['user_id'] ?? 0), 'logout');
        Auth::logout();
        redirect('login');
    }
}
