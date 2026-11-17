<?php

declare(strict_types=1);

/**
 * Remove all sample/demo transactional data and non-admin accounts.
 * Keeps reference data (colleges, campuses, roles, permissions) and admin@ride.local.
 *
 * Run: c:\xampp\php\php.exe scripts\clean-sample-data.php
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

$tableExists = static function (PDO $pdo, string $table): bool {
    $check = $pdo->prepare('SHOW TABLES LIKE ?');
    $check->execute([$table]);

    return (bool) $check->fetchColumn();
};

$deleteAll = static function (PDO $pdo, string $table) use ($tableExists): int {
    if (!$tableExists($pdo, $table)) {
        return 0;
    }

    return (int) $pdo->exec('DELETE FROM `' . str_replace('`', '', $table) . '`');
};

$counts = [];

$pdo->beginTransaction();

try {
    $counts['proposals'] = $deleteAll($pdo, 'proposals');
    $counts['audit_logs'] = $deleteAll($pdo, 'audit_logs');
    $counts['notifications'] = $deleteAll($pdo, 'notifications');
    $counts['published_papers'] = $deleteAll($pdo, 'published_papers');
    $counts['paper_presentations'] = $deleteAll($pdo, 'paper_presentations');
    $counts['scholarly_attachments'] = $deleteAll($pdo, 'scholarly_attachments');
    $counts['admin_messages'] = $deleteAll($pdo, 'admin_messages');
    $counts['direct_messages'] = $deleteAll($pdo, 'direct_messages');
    $counts['global_message_reads'] = $deleteAll($pdo, 'global_message_reads');

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

$storageDirs = [
    dirname(__DIR__) . '/storage/projects',
    dirname(__DIR__) . '/storage/signatures',
    dirname(__DIR__) . '/storage/scholarly',
];

foreach ($storageDirs as $baseDir) {
    if (!is_dir($baseDir)) {
        continue;
    }

    foreach (glob($baseDir . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $name = basename($dir);
        if ($baseDir === dirname(__DIR__) . '/storage/signatures' && (int) $name === $adminId) {
            continue;
        }

        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($dir);
    }
}

$remainingUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$remainingProposals = (int) $pdo->query('SELECT COUNT(*) FROM proposals')->fetchColumn();

echo "Sample data cleanup complete.\n";
echo "Proposals removed: {$counts['proposals']}\n";
echo "Audit logs removed: {$counts['audit_logs']}\n";
echo "Notifications removed: {$counts['notifications']}\n";
echo "Remaining users: {$remainingUsers}\n";
echo "Remaining proposals: {$remainingProposals}\n";
echo "Admin: {$adminEmail} (id {$adminId})\n";
