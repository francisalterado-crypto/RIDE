<?php

declare(strict_types=1);

/**
 * Remove every user account except the admin account.
 * Run: c:\xampp\php\php.exe scripts/remove-non-admin-accounts.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$pdo = \App\Core\Database::pdo();
$adminEmail = 'admin@ride.local';

$stmt = $pdo->prepare(
    'SELECT u.id FROM users u
     INNER JOIN user_roles ur ON ur.user_id = u.id
     INNER JOIN roles r ON r.id = ur.role_id
     WHERE u.email = ? AND r.slug = \'ride_admin\'
     LIMIT 1'
);
$stmt->execute([$adminEmail]);
$adminId = (int) $stmt->fetchColumn();

if ($adminId <= 0) {
    echo "Admin account not found ({$adminEmail}). Aborting.\n";
    exit(1);
}

$pdo->beginTransaction();

try {
    $pdo->exec('DELETE FROM proposals WHERE user_id <> ' . $adminId);
    $pdo->exec('DELETE FROM progress_reports WHERE user_id <> ' . $adminId);
    $pdo->exec('DELETE FROM documents WHERE user_id <> ' . $adminId);
    $pdo->exec('DELETE FROM proposal_comments WHERE user_id <> ' . $adminId);
    $pdo->exec('DELETE FROM approval_actions WHERE user_id <> ' . $adminId);
    $pdo->exec('DELETE FROM notifications WHERE user_id <> ' . $adminId);
    $pdo->exec('DELETE FROM user_roles WHERE user_id <> ' . $adminId);
    $pdo->exec('DELETE FROM users WHERE id <> ' . $adminId);

    $pdo->commit();
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo 'Cleanup failed: ' . $e->getMessage() . "\n";
    exit(1);
}

$signaturesDir = dirname(__DIR__) . '/storage/signatures';
if (is_dir($signaturesDir)) {
    foreach (glob($signaturesDir . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $userId = (int) basename($dir);
        if ($userId !== $adminId) {
            array_map('unlink', glob($dir . '/*') ?: []);
            rmdir($dir);
        }
    }
}

$remaining = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo "Done. Remaining accounts: {$remaining}\n";
echo "Admin: {$adminEmail} (id {$adminId})\n";
