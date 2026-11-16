<?php

/**
 * Add or update all demo accounts (safe for existing databases).
 * Run: c:\xampp\php\php.exe scripts\seed-demo-accounts.php
 *
 * Accounts and roles come from app/config/demo_accounts.php. College Coordinators also get the Faculty role.
 * RIDE coordinators are linked to their clusters. For the demo, the demo colleges are placed in research clusters
 * when they have none yet (CET and CCAS in Research Cluster I, CBEHM in Research Cluster II).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Models\User;

$hash = password_hash('password123', PASSWORD_DEFAULT);
$pdo = \App\Core\Database::pdo();
$accounts = require dirname(__DIR__) . '/app/config/demo_accounts.php';

$collegeId = static function (string $code) use ($pdo): ?int {
    $stmt = $pdo->prepare('SELECT id FROM colleges WHERE code = ?');
    $stmt->execute([$code]);
    $id = $stmt->fetchColumn();

    return $id !== false ? (int) $id : null;
};
$clusterId = static function (string $name) use ($pdo): ?int {
    $stmt = $pdo->prepare('SELECT id FROM clusters WHERE name = ?');
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();

    return $id !== false ? (int) $id : null;
};

// Demo research-cluster links for the demo colleges (kept if the admin already set one).
foreach (['CET' => 'Research Cluster I', 'CCAS' => 'Research Cluster I', 'CBEHM' => 'Research Cluster II'] as $code => $cluster) {
    $pdo->prepare('UPDATE colleges SET research_cluster_id = ? WHERE code = ? AND research_cluster_id IS NULL')
        ->execute([$clusterId($cluster), $code]);
}

// Old demo extension-worker accounts became the Field Extensionists under new emails.
foreach (['extensionworker.cet@ride.local' => 'coord.extension.group1@ride.local', 'extensionworker.cas@ride.local' => 'coord.extension.group2@ride.local'] as $old => $new) {
    $exists = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
    $exists->execute([$new]);
    if (!$exists->fetchColumn()) {
        $pdo->prepare('UPDATE users SET email = ? WHERE email = ?')->execute([$new, $old]);
    }
}

// Any other old demo extension-worker account is retired.
$pdo->exec("UPDATE users SET is_active = 0 WHERE email LIKE 'extensionworker.%@ride.local'");

// Faculty demo accounts are no longer tagged by track: one Faculty account per college.
foreach (['cet', 'cas', 'cbm'] as $code) {
    $exists = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
    $exists->execute(["faculty.{$code}@ride.local"]);
    if (!$exists->fetchColumn()) {
        $pdo->prepare('UPDATE users SET email = ? WHERE email = ?')
            ->execute(["faculty.{$code}@ride.local", "faculty.research.{$code}@ride.local"]);
    }
}
// Old track-tagged faculty accounts are removed when they own no records, otherwise deactivated.
$pdo->exec("DELETE FROM users WHERE (email LIKE 'faculty.research.%@ride.local' OR email LIKE 'faculty.extension.%@ride.local')
    AND id NOT IN (SELECT user_id FROM proposals)");
$pdo->exec("UPDATE users SET is_active = 0 WHERE (email LIKE 'faculty.research.%@ride.local' OR email LIKE 'faculty.extension.%@ride.local')");

$upsert = $pdo->prepare(
    'INSERT INTO users (email, password_hash, first_name, last_name, college_id, program, is_active)
     VALUES (?, ?, ?, ?, ?, ?, 1)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), first_name = VALUES(first_name),
       last_name = VALUES(last_name), college_id = VALUES(college_id), program = VALUES(program), is_active = 1'
);
$findUser = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$findRole = $pdo->prepare('SELECT id FROM roles WHERE slug = ?');

foreach ($accounts as $account) {
    [$first, $last] = array_pad(explode(' ', $account['name'], 2), 2, '');
    $college = isset($account['college']) ? $collegeId($account['college']) : null;
    $upsert->execute([$account['email'], $hash, $first, $last, $college, $account['program'] ?? null]);
    $findUser->execute([$account['email']]);
    $userId = (int) $findUser->fetchColumn();
    $findRole->execute([$account['role']]);
    $roleId = (int) $findRole->fetchColumn();
    if ($userId === 0 || $roleId === 0) {
        echo "SKIP: {$account['email']} (role {$account['role']} missing)\n";
        continue;
    }
    User::replaceRoles($userId, $roleId, $college);
    User::setClusters($userId, array_values(array_filter(array_map($clusterId, $account['clusters'] ?? []))));
    echo "OK: {$account['email']} -> {$account['role']}\n";
}

echo "\nDone. Password for all: password123\n";
echo "Open: http://localhost/RIDE/public/login\n";
