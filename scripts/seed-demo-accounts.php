<?php

/**
 * Add or update all sample accounts (safe for existing databases).
 * Run: c:\xampp\php\php.exe scripts\seed-demo-accounts.php
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$hash = '$2y$10$1UFSLUmOVn4oH5uXD3thxOOGUJ68FZadbLaN18VrdfvrR1u1.RjNi';
$pdo = \App\Core\Database::pdo();

$accounts = require dirname(__DIR__) . '/app/config/demo_accounts.php';

$roleMap = [
    'RIDE Admin (system)' => 'ride_admin',
    'Admin / VPRIDE' => 'vpride',
    'Director of Research' => 'director_research',
    'Director of Extension' => 'director_extension',
    'Coordinator of Research' => 'coordinator_research',
    'Coordinator of Extension' => 'coordinator_extension',
    'College Dean' => 'dean',
    'Faculty (Research)' => 'faculty',
    'Faculty (Extension)' => 'faculty',
    'Extension Worker' => 'extension_worker',
];

$collegeMap = ['CET' => 1, 'CAS' => 2, 'CBM' => 3];
$campusMap = ['CET' => 1, 'CAS' => 3, 'CBM' => 4];

$userStmt = $pdo->prepare(
    'INSERT INTO users (email, password_hash, first_name, last_name, college_id, program, campus_id)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       password_hash = VALUES(password_hash),
       first_name = VALUES(first_name),
       last_name = VALUES(last_name),
       college_id = VALUES(college_id),
       program = VALUES(program),
       campus_id = VALUES(campus_id),
       is_active = 1'
);

$findUser = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$roleStmt = $pdo->prepare('SELECT id FROM roles WHERE slug = ?');
$clearRoles = $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?');
$assignStmt = $pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id, college_id) VALUES (?, ?, ?)');

foreach ($accounts as $acc) {
    $parts = explode(' ', $acc['name'], 2);
    $first = $parts[0];
    $last = $parts[1] ?? '';

    $collegeKey = $acc['college'];
    if (preg_match('/\b(CET|CAS|CBM)\b/', $collegeKey, $match)) {
        $collegeKey = $match[1];
    }
    $collegeId = $collegeMap[$collegeKey] ?? null;
    $campusId = $collegeId ? ($campusMap[$collegeKey] ?? null) : null;

    $program = trim((string) ($acc['program'] ?? '')) ?: null;

    $userStmt->execute([$acc['email'], $hash, $first, $last, $collegeId, $program, $campusId]);
    $findUser->execute([$acc['email']]);
    $userId = (int) $findUser->fetchColumn();

    $roleSlug = $roleMap[$acc['role']] ?? null;
    if (!$roleSlug || !$userId) {
        echo "SKIP: {$acc['email']}\n";
        continue;
    }

    $roleStmt->execute([$roleSlug]);
    $roleId = (int) $roleStmt->fetchColumn();
    $roleCollege = str_contains($collegeKey, 'University') || str_contains($collegeKey, 'View-only')
        ? ($collegeMap['CET'] ?? null)
        : $collegeId;

    if (in_array($roleSlug, ['ride_admin', 'vpride', 'director_research', 'director_extension'], true)) {
        $roleCollege = null;
    }

    $clearRoles->execute([$userId]);
    $assignStmt->execute([$userId, $roleId, $roleCollege]);
    echo "OK: {$acc['email']} → {$roleSlug}\n";
}

echo "\nDone. Password for all: password123\n";
echo "Open: http://localhost/RIDE/public/login\n";
