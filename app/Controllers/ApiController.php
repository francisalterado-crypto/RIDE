<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Extension;
use App\Models\Proposal;
use App\Models\User;
use App\Support\MonitoringRoles;

final class ApiController
{
    public function login(): void
    {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
        $email = trim($input['email'] ?? $_POST['email'] ?? '');
        $password = $input['password'] ?? $_POST['password'] ?? '';

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            json_response(['error' => 'Invalid credentials'], 401);
        }

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $stmt = \App\Core\Database::pdo()->prepare(
            'INSERT INTO api_tokens (user_id, token_hash, label, expires_at) VALUES (?, ?, ?, ' . \App\Core\Sql::nowPlusDays(7) . ')'
        );
        $stmt->execute([(int) $user['id'], $hash, 'java-client']);

        json_response([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'roles' => User::roleSlugs((int) $user['id']),
            ],
        ]);
    }

    public function proposals(): void
    {
        $this->requireApiAuth();
        $user = Auth::user();
        $scopeType = MonitoringRoles::proposalScopeType();
        if (MonitoringRoles::isVpride() || Auth::hasRole('ride_reporter')) {
            $data = $scopeType !== null ? Proposal::all(null, $scopeType) : Proposal::all();
        } elseif ($scopeType !== null) {
            $data = Proposal::all(null, $scopeType);
        } elseif ($user['college_id']) {
            $data = Proposal::forCollege((int) $user['college_id']);
        } else {
            $data = Proposal::forUser((int) $user['id']);
        }
        json_response(['data' => $data]);
    }

    public function stats(): void
    {
        $this->requireApiAuth();
        json_response(['data' => Proposal::stats()]);
    }

    public function projects(): void
    {
        $this->requireApiAuth();
        $user = Auth::user();
        $scopeType = MonitoringRoles::proposalScopeType();
        if (MonitoringRoles::isVpride() || Auth::hasRole('ride_reporter')) {
            $data = $scopeType !== null ? Proposal::ongoing(null, null, $scopeType) : Proposal::ongoing();
        } elseif ($scopeType !== null) {
            $data = Proposal::ongoing(null, null, $scopeType);
        } elseif ($user['college_id']) {
            $data = Proposal::ongoing((int) $user['college_id']);
        } else {
            $data = Proposal::ongoing(null, (int) $user['id']);
        }
        json_response(['data' => $data]);
    }

    public function extensionBeneficiaries(): void
    {
        $this->requireApiAuth();
        if (!MonitoringRoles::isVpride() && !Auth::hasRole('ride_reporter')) {
            json_response(['error' => 'Forbidden'], 403);
        }
        $years = (int) ($_GET['years'] ?? 3);
        json_response(['data' => Extension::beneficiaryReport($years), 'years' => $years]);
    }

    private function requireApiAuth(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            json_response(['error' => 'Unauthorized'], 401);
        }
        $hash = hash('sha256', $m[1]);
        $stmt = \App\Core\Database::pdo()->prepare(
            'SELECT user_id FROM api_tokens WHERE token_hash = ? AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1'
        );
        $stmt->execute([$hash]);
        $userId = $stmt->fetchColumn();
        if (!$userId) {
            json_response(['error' => 'Invalid token'], 401);
        }
        $user = User::findById((int) $userId);
        if (!$user) {
            json_response(['error' => 'User not found'], 401);
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_roles'] = User::roleSlugs((int) $user['id']);
    }
}
