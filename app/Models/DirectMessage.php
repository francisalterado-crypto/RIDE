<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Sql;

final class DirectMessage
{
    public static function send(int $senderId, int $recipientId, string $body): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO direct_messages (sender_id, recipient_id, body)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([$senderId, $recipientId, $body]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public static function thread(int $userId, int $partnerId, int $limit = 100): array
    {
        $limit = max(1, min($limit, 200));
        $stmt = Database::pdo()->prepare(
            'SELECT dm.id, dm.sender_id, dm.recipient_id, dm.body, dm.read_at, dm.created_at,
                    s.first_name AS sender_first_name, s.last_name AS sender_last_name,
                    r.first_name AS recipient_first_name, r.last_name AS recipient_last_name
             FROM direct_messages dm
             INNER JOIN users s ON s.id = dm.sender_id
             INNER JOIN users r ON r.id = dm.recipient_id
             WHERE (dm.sender_id = ? AND dm.recipient_id = ?)
                OR (dm.sender_id = ? AND dm.recipient_id = ?)
             ORDER BY dm.created_at ASC, dm.id ASC
             LIMIT ?'
        );
        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $partnerId, \PDO::PARAM_INT);
        $stmt->bindValue(3, $partnerId, \PDO::PARAM_INT);
        $stmt->bindValue(4, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(5, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public static function markThreadRead(int $userId, int $partnerId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE direct_messages
             SET read_at = COALESCE(read_at, NOW())
             WHERE recipient_id = ? AND sender_id = ? AND read_at IS NULL'
        );
        $stmt->execute([$userId, $partnerId]);
    }

    public static function unreadCountForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM direct_messages WHERE recipient_id = ? AND read_at IS NULL'
        );
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function conversationSummaries(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $stmt = Database::pdo()->prepare(
            'SELECT partner_id,
                    MAX(created_at) AS last_at,
                    SUM(CASE WHEN recipient_id = ? AND read_at IS NULL THEN 1 ELSE 0 END) AS unread_count,
                    ' . Sql::substringIndex(
                        Sql::groupConcat('body', "\x1e", 'created_at DESC, id DESC'),
                        "\x1e",
                        1
                    ) . ' AS last_body,
                    ' . Sql::substringIndex(
                        Sql::groupConcat(
                            'CASE WHEN sender_id = ? THEN \'out\' ELSE \'in\' END',
                            "\x1e",
                            'created_at DESC, id DESC'
                        ),
                        "\x1e",
                        1
                    ) . ' AS last_direction
             FROM (
                 SELECT CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END AS partner_id,
                        sender_id, recipient_id, body, read_at, created_at, id
                 FROM direct_messages
                 WHERE sender_id = ? OR recipient_id = ?
             ) t
             GROUP BY partner_id
             ORDER BY last_at DESC'
        );
        $stmt->execute([$userId, $userId, $userId, $userId, $userId]);
        $rows = $stmt->fetchAll() ?: [];

        if ($rows === []) {
            return [];
        }

        $partnerIds = array_map(static fn (array $row): int => (int) ($row['partner_id'] ?? 0), $rows);
        $partners = self::usersByIds($partnerIds);
        $summaries = [];

        foreach ($rows as $row) {
            $partnerId = (int) ($row['partner_id'] ?? 0);
            if ($partnerId <= 0 || !isset($partners[$partnerId])) {
                continue;
            }
            $partner = $partners[$partnerId];
            $summaries[] = [
                'partner_id' => $partnerId,
                'partner' => $partner,
                'partner_name' => trim((string) ($partner['first_name'] ?? '') . ' ' . (string) ($partner['last_name'] ?? '')),
                'partner_role_label' => \App\Support\DirectMessaging::isFacultyUser($partnerId)
                    ? 'Faculty'
                    : \App\Support\DirectMessaging::staffRoleLabelForUser($partnerId),
                'last_at' => (string) ($row['last_at'] ?? ''),
                'last_body' => (string) ($row['last_body'] ?? ''),
                'last_direction' => (string) ($row['last_direction'] ?? ''),
                'unread_count' => (int) ($row['unread_count'] ?? 0),
            ];
        }

        return $summaries;
    }

    /**
     * @param list<int> $ids
     * @return array<int, array<string, mixed>>
     */
    private static function usersByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT u.id, u.first_name, u.last_name, u.email, u.college_id, c.name AS college_name
             FROM users u
             LEFT JOIN colleges c ON c.id = u.college_id
             WHERE u.id IN ($placeholders) AND u.is_active = 1"
        );
        $stmt->execute($ids);
        $map = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $map[(int) $row['id']] = $row;
        }

        return $map;
    }
}
