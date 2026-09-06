<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Document
{
    private const ALLOWED = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'];
    private const MAX_BYTES = 10 * 1024 * 1024;

    /** @return list<array> */
    public static function forProposal(int $proposalId, ?string $category = null): array
    {
        $sql = 'SELECT d.*, u.first_name, u.last_name FROM documents d
                INNER JOIN users u ON u.id = d.user_id
                WHERE d.proposal_id = ?';
        $params = [$proposalId];
        if ($category) {
            $sql .= ' AND d.category = ?';
            $params[] = $category;
        }
        $sql .= ' ORDER BY d.created_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public static function store(int $proposalId, int $userId, array $file, string $category): int
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed.');
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new \RuntimeException('File exceeds 10 MB limit.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED, true)) {
            throw new \RuntimeException('File type not allowed.');
        }

        $dir = STORAGE_PATH . '/projects/' . $proposalId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $stored;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Could not save file.');
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO documents (proposal_id, user_id, category, original_name, stored_name, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $proposalId,
            $userId,
            $category,
            $file['name'],
            $stored,
            $file['type'] ?? null,
            (int) $file['size'],
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function copyToCategory(int $documentId, string $category): int
    {
        $document = self::find($documentId);
        if (!$document) {
            throw new \RuntimeException('Source document not found.');
        }

        $sourcePath = self::filePath($document);
        if (!is_file($sourcePath)) {
            throw new \RuntimeException('Source file is missing.');
        }

        $proposalId = (int) $document['proposal_id'];
        $dir = STORAGE_PATH . '/projects/' . $proposalId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = strtolower(pathinfo((string) $document['stored_name'], PATHINFO_EXTENSION));
        $stored = bin2hex(random_bytes(16)) . ($ext !== '' ? '.' . $ext : '');
        $dest = $dir . '/' . $stored;
        if (!copy($sourcePath, $dest)) {
            throw new \RuntimeException('Could not copy file.');
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO documents (proposal_id, user_id, category, original_name, stored_name, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $proposalId,
            (int) $document['user_id'],
            $category,
            $document['original_name'],
            $stored,
            $document['mime_type'] ?? null,
            (int) $document['file_size'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function mirrorRequiredFilesIntoRepository(int $proposalId): void
    {
        $documents = self::forProposal($proposalId);
        $repositorySignaturesByCategory = [];

        foreach ($documents as $document) {
            $category = (string) ($document['category'] ?? '');
            if ($category === '' || str_starts_with($category, 'required_file:')) {
                continue;
            }

            if (!isset($repositorySignaturesByCategory[$category])) {
                $repositorySignaturesByCategory[$category] = [];
            }
            $repositorySignaturesByCategory[$category][self::signature($document)] = true;
        }

        foreach ($documents as $document) {
            $category = (string) ($document['category'] ?? '');
            if (!str_starts_with($category, 'required_file:')) {
                continue;
            }

            $targetCategory = substr($category, strlen('required_file:'));
            if ($targetCategory === '') {
                continue;
            }

            if (!isset($repositorySignaturesByCategory[$targetCategory])) {
                $repositorySignaturesByCategory[$targetCategory] = [];
            }

            $signature = self::signature($document);
            if (isset($repositorySignaturesByCategory[$targetCategory][$signature])) {
                continue;
            }

            // Do not block project/document pages when a legacy/missing required file cannot be mirrored.
            try {
                self::copyToCategory((int) $document['id'], $targetCategory);
                $repositorySignaturesByCategory[$targetCategory][$signature] = true;
            } catch (\Throwable $e) {
                continue;
            }
        }
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM documents WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function filePath(array $doc): string
    {
        return STORAGE_PATH . '/projects/' . $doc['proposal_id'] . '/' . $doc['stored_name'];
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM documents WHERE id = ?');
        $stmt->execute([$id]);
    }

    private static function signature(array $doc): string
    {
        return (string) ($doc['original_name'] ?? '') . '|' . (string) ((int) ($doc['file_size'] ?? 0));
    }
}
