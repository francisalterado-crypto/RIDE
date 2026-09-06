<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ProposalCoAuthorInvitation
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT ci.*, p.title AS proposal_title, p.user_id AS lead_user_id,
                    lead.first_name AS lead_first_name, lead.last_name AS lead_last_name,
                    invitee.first_name AS invitee_first_name, invitee.last_name AS invitee_last_name
             FROM proposal_coauthor_invitations ci
             INNER JOIN proposals p ON p.id = ci.proposal_id
             INNER JOIN users lead ON lead.id = ci.invited_by_user_id
             INNER JOIN users invitee ON invitee.id = ci.invitee_user_id
             WHERE ci.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function forProposalAndInvitee(int $proposalId, int $inviteeUserId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM proposal_coauthor_invitations WHERE proposal_id = ? AND invitee_user_id = ?'
        );
        $stmt->execute([$proposalId, $inviteeUserId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public static function pendingForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $stmt = Database::pdo()->prepare(
            'SELECT ci.*, p.title AS proposal_title, p.project_type,
                    lead.first_name AS lead_first_name, lead.last_name AS lead_last_name
             FROM proposal_coauthor_invitations ci
             INNER JOIN proposals p ON p.id = ci.proposal_id
             INNER JOIN users lead ON lead.id = ci.invited_by_user_id
             WHERE ci.invitee_user_id = ? AND ci.status = ?
             ORDER BY ci.created_at DESC'
        );
        $stmt->execute([$userId, self::STATUS_PENDING]);

        return $stmt->fetchAll() ?: [];
    }

    public static function hasAcceptedAccess(int $proposalId, int $userId): bool
    {
        if ($proposalId <= 0 || $userId <= 0) {
            return false;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM proposal_coauthor_invitations
             WHERE proposal_id = ? AND invitee_user_id = ? AND status = ? LIMIT 1'
        );
        $stmt->execute([$proposalId, $userId, self::STATUS_ACCEPTED]);

        return $stmt->fetchColumn() !== false;
    }

    public static function upsertPending(int $proposalId, int $inviteeUserId, int $invitedByUserId): int
    {
        $existing = self::forProposalAndInvitee($proposalId, $inviteeUserId);
        if ($existing !== null) {
            $status = (string) ($existing['status'] ?? '');
            if ($status === self::STATUS_ACCEPTED) {
                return (int) $existing['id'];
            }
            if ($status === self::STATUS_PENDING) {
                return (int) $existing['id'];
            }

            $stmt = Database::pdo()->prepare(
                'UPDATE proposal_coauthor_invitations
                 SET status = ?, invited_by_user_id = ?, responded_at = NULL, created_at = CURRENT_TIMESTAMP
                 WHERE id = ?'
            );
            $stmt->execute([self::STATUS_PENDING, $invitedByUserId, (int) $existing['id']]);

            return (int) $existing['id'];
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO proposal_coauthor_invitations (proposal_id, invitee_user_id, invited_by_user_id, status)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$proposalId, $inviteeUserId, $invitedByUserId, self::STATUS_PENDING]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function markAccepted(int $id): bool
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE proposal_coauthor_invitations
             SET status = ?, responded_at = NOW()
             WHERE id = ? AND status = ?'
        );
        $stmt->execute([self::STATUS_ACCEPTED, $id, self::STATUS_PENDING]);

        return $stmt->rowCount() > 0;
    }

    public static function markRejected(int $id): bool
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE proposal_coauthor_invitations
             SET status = ?, responded_at = NOW()
             WHERE id = ? AND status = ?'
        );
        $stmt->execute([self::STATUS_REJECTED, $id, self::STATUS_PENDING]);

        return $stmt->rowCount() > 0;
    }

    public static function removeForProposalExcept(int $proposalId, array $inviteeUserIds): void
    {
        $inviteeUserIds = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $inviteeUserIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($inviteeUserIds === []) {
            Database::pdo()->prepare(
                'DELETE FROM proposal_coauthor_invitations WHERE proposal_id = ?'
            )->execute([$proposalId]);

            return;
        }

        $placeholders = implode(',', array_fill(0, count($inviteeUserIds), '?'));
        $stmt = Database::pdo()->prepare(
            "DELETE FROM proposal_coauthor_invitations
             WHERE proposal_id = ? AND invitee_user_id NOT IN ($placeholders)"
        );
        $stmt->execute([$proposalId, ...$inviteeUserIds]);
    }

    /**
     * SQL fragment: user has co-author access (accepted invitation or legacy summary tag).
     */
    public static function coauthorAccessWhereSql(string $proposalAlias = 'p'): string
    {
        return '(' . $proposalAlias . '.user_id = ?
            OR EXISTS (
                SELECT 1 FROM proposal_coauthor_invitations ci
                WHERE ci.proposal_id = ' . $proposalAlias . '.id
                  AND ci.invitee_user_id = ?
                  AND ci.status = \'accepted\'
            )
            OR (
                ' . $proposalAlias . '.summary IS NOT NULL
                AND ' . \App\Core\Sql::regexp($proposalAlias . '.summary', '?') . '
                AND NOT EXISTS (
                    SELECT 1 FROM proposal_coauthor_invitations ci2
                    WHERE ci2.proposal_id = ' . $proposalAlias . '.id
                      AND ci2.invitee_user_id = ?
                )
            ))';
    }

    /**
     * @return list<int|string>
     */
    public static function coauthorAccessParams(int $userId): array
    {
        return [
            $userId,
            $userId,
            \App\Support\ProposalCoAuthors::userIdRegexpPattern($userId),
            $userId,
        ];
    }
}
