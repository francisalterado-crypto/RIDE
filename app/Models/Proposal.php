<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Sql;
use App\Support\MonitoringRoles;
use App\Support\ProposalCoAuthors;
use PDO;

final class Proposal
{
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT p.*, u.first_name, u.last_name, u.email AS leader_email,
                    c.name AS college_name, cp.name AS campus_name
             FROM proposals p
             INNER JOIN users u ON u.id = p.user_id
             INNER JOIN colleges c ON c.id = p.college_id
             LEFT JOIN campuses cp ON cp.id = p.campus_id
             WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return list<array> */
    public static function forUser(int $userId): array
    {
        $accessSql = ProposalCoAuthorInvitation::coauthorAccessWhereSql('p');
        $stmt = Database::pdo()->prepare(
            'SELECT p.*,
                    CASE WHEN p.user_id = ? THEN \'lead\' ELSE \'coauthor\' END AS membership
             FROM proposals p
             WHERE ' . $accessSql . '
             ORDER BY p.updated_at DESC'
        );
        $stmt->execute([$userId, ...ProposalCoAuthorInvitation::coauthorAccessParams($userId)]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param list<string> $projectTypes
     * @return list<array>
     */
    public static function forUserByTypes(int $userId, array $projectTypes): array
    {
        if ($projectTypes === []) {
            return self::forUser($userId);
        }

        $placeholders = implode(',', array_fill(0, count($projectTypes), '?'));
        $accessSql = ProposalCoAuthorInvitation::coauthorAccessWhereSql('p');
        $sql = 'SELECT p.*,
                       CASE WHEN p.user_id = ? THEN \'lead\' ELSE \'coauthor\' END AS membership
                FROM proposals p
                WHERE ' . $accessSql . '
                  AND p.project_type IN (' . $placeholders . ')
                ORDER BY p.updated_at DESC';
        $params = [$userId, ...ProposalCoAuthorInvitation::coauthorAccessParams($userId), ...$projectTypes];
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array> */
    public static function forCollege(int $collegeId, ?string $status = null): array
    {
        $sql = 'SELECT p.*, u.first_name, u.last_name FROM proposals p
                INNER JOIN users u ON u.id = p.user_id
                WHERE p.college_id = ?';
        $params = [$collegeId];
        if ($status) {
            $sql .= ' AND p.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY p.updated_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array> */
    public static function all(?string $status = null, ?string $projectType = null): array
    {
        $sql = 'SELECT p.*, u.first_name, u.last_name, c.name AS college_name
                FROM proposals p
                INNER JOIN users u ON u.id = p.user_id
                INNER JOIN colleges c ON c.id = p.college_id';
        $params = [];
        $where = [];
        if ($status) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($projectType !== null) {
            $where[] = 'p.project_type = ?';
            $params[] = $projectType;
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY p.updated_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public static function hasDuplicateTitleForUser(int $userId, string $title, ?int $excludeProposalId = null): bool
    {
        $normalized = mb_strtolower(trim($title));
        if ($normalized === '') {
            return false;
        }

        $sql = 'SELECT id FROM proposals WHERE user_id = ? AND LOWER(TRIM(title)) = ?';
        $params = [$userId, $normalized];
        if ($excludeProposalId !== null && $excludeProposalId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeProposalId;
        }
        $sql .= ' LIMIT 1';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO proposals (user_id, college_id, campus_id, project_type, title, summary, funding_source, risk_level, ethics_required, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['college_id'],
            $data['campus_id'] ?: null,
            $data['project_type'],
            $data['title'],
            $data['summary'] ?? null,
            $data['funding_source'] ?? null,
            $data['risk_level'] ?? 'low',
            !empty($data['ethics_required']) ? 1 : 0,
            'draft',
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE proposals SET title = ?, summary = ?, funding_source = ?, risk_level = ?, ethics_required = ?, project_type = ?
             WHERE id = ? AND status IN (\'draft\', \'returned\')'
        );
        $stmt->execute([
            $data['title'],
            $data['summary'] ?? null,
            $data['funding_source'] ?? null,
            $data['risk_level'] ?? 'low',
            !empty($data['ethics_required']) ? 1 : 0,
            $data['project_type'],
            $id,
        ]);
    }

    public static function updateConsolidatedDraft(int $id, string $title, string $summaryJson): void
    {
        Database::pdo()->prepare(
            'UPDATE proposals SET title = ?, summary = ? WHERE id = ? AND status IN (\'draft\', \'returned\')'
        )->execute([$title, $summaryJson, $id]);
    }

    public static function updateSummaryCoauthorsOnly(int $id, string $summaryJson): void
    {
        Database::pdo()->prepare(
            'UPDATE proposals SET summary = ? WHERE id = ?'
        )->execute([$summaryJson, $id]);
    }

    public static function deleteForUser(int $id, int $userId): bool
    {
        $stmt = Database::pdo()->prepare(
            "DELETE FROM proposals WHERE id = ? AND user_id = ? AND status IN ('draft', 'returned')"
        );
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }

    public static function forceReopenForEdit(int $id): bool
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE proposals SET status = 'returned', current_step = NULL WHERE id = ? AND status IN ('submitted', 'under_review')"
        );
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    public static function forceDelete(int $id): bool
    {
        $stmt = Database::pdo()->prepare(
            "DELETE FROM proposals WHERE id = ? AND status IN ('draft', 'returned', 'submitted', 'under_review', 'suspended')"
        );
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    public static function submit(int $id): void
    {
        $proposal = self::find($id);
        if (!$proposal) {
            return;
        }
        $step = MonitoringRoles::coordinatorStepForType((string) ($proposal['project_type'] ?? 'research'));
        $stmt = Database::pdo()->prepare(
            'UPDATE proposals SET status = \'submitted\', current_step = ?, submitted_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$step, $id]);
    }

    public static function addComment(int $proposalId, int $userId, string $comment, string $action = 'comment', ?string $step = null): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO proposal_comments (proposal_id, user_id, step, comment, action) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$proposalId, $userId, $step, $comment, $action]);
    }

    public static function advanceWorkflow(int $id, string $action, int $userId, string $comment = ''): bool
    {
        $proposal = self::find($id);
        if (!$proposal || !in_array($proposal['status'], ['submitted', 'under_review'], true)) {
            return false;
        }

        $step = $proposal['current_step'] ?? MonitoringRoles::coordinatorStepForType((string) ($proposal['project_type'] ?? 'research'));

        if ($action === 'return') {
            Database::pdo()->prepare(
                'UPDATE proposals SET status = \'returned\', current_step = NULL WHERE id = ?'
            )->execute([$id]);
            self::addComment($id, $userId, $comment, 'return', $step);
            return true;
        }

        if ($action === 'reject') {
            Database::pdo()->prepare(
                'UPDATE proposals SET status = \'suspended\', current_step = NULL WHERE id = ?'
            )->execute([$id]);
            self::addComment($id, $userId, $comment, 'reject', $step);
            return true;
        }

        $next = self::nextStep($step, $proposal);
        if ($next === 'approved') {
            $code = self::generateProjectCode($proposal);
            $stmt = Database::pdo()->prepare(
                'UPDATE proposals SET status = \'ongoing\', current_step = NULL, project_code = ?, approved_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$code, $id]);
            Milestone::seedDefaults($id);
            Notification::notifyProjectLeader(
                (int) $proposal['user_id'],
                'Project approved',
                'Your project "' . $proposal['title'] . '" was approved. Code: ' . $code,
                'projects/' . $id
            );
        } else {
            $stmt = Database::pdo()->prepare(
                'UPDATE proposals SET status = \'under_review\', current_step = ? WHERE id = ?'
            );
            $stmt->execute([$next, $id]);
        }

        $hash = hash('sha256', $id . '|' . $userId . '|' . time());
        $stmt = Database::pdo()->prepare(
            'INSERT INTO approval_actions (proposal_id, user_id, step, action, signature_hash) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $userId, $step, 'approve', $hash]);
        self::addComment($id, $userId, $comment ?: 'Approved.', 'approve', $step);

        if ($step === MonitoringRoles::DIRECTOR_RESEARCH) {
            \App\Services\OngoingResearchesConsolidation::depositOnDirectorApproval($id);
            \App\Services\CompletedResearchesConsolidation::depositOnDirectorApproval($id);
            \App\Services\ResearchOutputPublishedConsolidation::depositOnDirectorApproval($id);
            \App\Services\ResearchOutputPresentedConsolidation::depositOnDirectorApproval($id);
            foreach (\App\Services\ResearchFormConsolidation::FORM_KEYS as $formKey) {
                \App\Services\ResearchFormConsolidation::depositOnDirectorApproval($formKey, $id);
            }
        }

        return true;
    }

    private static function nextStep(string $current, array $proposal): string
    {
        $projectType = (string) ($proposal['project_type'] ?? 'research');

        return match ($current) {
            MonitoringRoles::COORDINATOR_RESEARCH, MonitoringRoles::COORDINATOR_EXTENSION => MonitoringRoles::DEAN,
            MonitoringRoles::DEAN => MonitoringRoles::directorStepForType($projectType),
            MonitoringRoles::DIRECTOR_RESEARCH, MonitoringRoles::DIRECTOR_EXTENSION => MonitoringRoles::VPRIDE,
            MonitoringRoles::VPRIDE => 'approved',
            default => MonitoringRoles::VPRIDE,
        };
    }

    private static function generateProjectCode(array $proposal): string
    {
        $college = Database::pdo()->prepare('SELECT code FROM colleges WHERE id = ?');
        $college->execute([$proposal['college_id']]);
        $code = $college->fetchColumn() ?: 'GEN';
        $year = date('Y');
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM proposals WHERE project_code IS NOT NULL AND ' . Sql::year('approved_at') . ' = ?'
        );
        $stmt->execute([$year]);
        $seq = (int) $stmt->fetchColumn() + 1;
        return sprintf('RIDE-%s-%s-%03d', $year, $code, $seq);
    }

    /** @return list<array> */
    public static function comments(int $proposalId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT pc.*, u.first_name, u.last_name FROM proposal_comments pc
             INNER JOIN users u ON u.id = pc.user_id
             WHERE pc.proposal_id = ? ORDER BY pc.created_at ASC'
        );
        $stmt->execute([$proposalId]);
        return $stmt->fetchAll() ?: [];
    }

    /** Coordinator / dean / VPRIDE approve action at a workflow step, if recorded. */
    public static function approvalAtStep(int $proposalId, string $step): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT aa.*, u.first_name, u.last_name, u.email
             FROM approval_actions aa
             INNER JOIN users u ON u.id = aa.user_id
             WHERE aa.proposal_id = ? AND aa.step = ? AND aa.action = \'approve\'
             ORDER BY aa.created_at DESC
             LIMIT 1'
        );
        $stmt->execute([$proposalId, $step]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function stats(): array
    {
        $pdo = Database::pdo();
        $byStatus = $pdo->query(
            'SELECT status, COUNT(*) AS cnt FROM proposals GROUP BY status'
        )->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $bottleneck = $pdo->query(
            "SELECT current_step, COUNT(*) AS cnt FROM proposals
             WHERE status IN ('submitted','under_review') AND current_step IS NOT NULL
             GROUP BY current_step"
        )->fetchAll() ?: [];

        $overdueMilestones = (int) $pdo->query(
            "SELECT COUNT(*) FROM milestones m
             INNER JOIN proposals p ON p.id = m.proposal_id
             WHERE m.status = 'overdue' AND p.status IN ('ongoing','approved')"
        )->fetchColumn();

        $overdueReports = (int) $pdo->query(
            "SELECT COUNT(*) FROM progress_reports
             WHERE status = 'draft' AND due_date IS NOT NULL AND due_date < " . Sql::currentDate()
        )->fetchColumn();

        return [
            'by_status' => $byStatus,
            'bottleneck' => $bottleneck,
            'overdue_milestones' => $overdueMilestones,
            'overdue_reports' => $overdueReports,
        ];
    }

    /** @return array<string, mixed> */
    public static function statsForScope(?string $projectType = null, ?int $collegeId = null, ?int $userId = null): array
    {
        $pdo = Database::pdo();
        $filters = [];
        $params = [];

        if ($projectType !== null) {
            $filters[] = 'p.project_type = ?';
            $params[] = $projectType;
        }
        if ($collegeId !== null) {
            $filters[] = 'p.college_id = ?';
            $params[] = $collegeId;
        }
        if ($userId !== null) {
            $filters[] = 'p.user_id = ?';
            $params[] = $userId;
        }

        $filterSql = $filters !== [] ? ' AND ' . implode(' AND ', $filters) : '';

        $byStatusStmt = $pdo->prepare(
            'SELECT p.status, COUNT(*) AS cnt FROM proposals p WHERE 1=1' . $filterSql . ' GROUP BY p.status'
        );
        $byStatusStmt->execute($params);
        $byStatus = $byStatusStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $bottleneckStmt = $pdo->prepare(
            "SELECT p.current_step, COUNT(*) AS cnt FROM proposals p
             WHERE p.status IN ('submitted','under_review')
               AND p.current_step IS NOT NULL"
            . $filterSql
            . ' GROUP BY p.current_step'
        );
        $bottleneckStmt->execute($params);
        $bottleneck = $bottleneckStmt->fetchAll() ?: [];

        $overdueMilestonesStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM milestones m
             INNER JOIN proposals p ON p.id = m.proposal_id
             WHERE m.status = 'overdue'
               AND p.status IN ('ongoing','approved')"
            . $filterSql
        );
        $overdueMilestonesStmt->execute($params);
        $overdueMilestones = (int) $overdueMilestonesStmt->fetchColumn();

        $overdueReportsStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM progress_reports pr
             INNER JOIN proposals p ON p.id = pr.proposal_id
             WHERE pr.status = 'draft'
               AND pr.due_date IS NOT NULL
               AND pr.due_date < " . Sql::currentDate()
            . $filterSql
        );
        $overdueReportsStmt->execute($params);
        $overdueReports = (int) $overdueReportsStmt->fetchColumn();

        return [
            'by_status' => $byStatus,
            'bottleneck' => $bottleneck,
            'overdue_milestones' => $overdueMilestones,
            'overdue_reports' => $overdueReports,
        ];
    }

    /**
     * @param list<string> $projectTypes
     * @return array<string, mixed>
     */
    public static function statsForUser(int $userId, array $projectTypes = []): array
    {
        $pdo = Database::pdo();
        $typeFilterSql = '';
        $typeParams = [];
        if ($projectTypes !== []) {
            $typeFilterSql = ' AND p.project_type IN (' . implode(',', array_fill(0, count($projectTypes), '?')) . ')';
            $typeParams = $projectTypes;
        }

        $ownershipSql = ProposalCoAuthorInvitation::coauthorAccessWhereSql('p');
        $ownershipParams = ProposalCoAuthorInvitation::coauthorAccessParams($userId);

        $byStatusStmt = $pdo->prepare(
            'SELECT p.status, COUNT(*) AS cnt FROM proposals p WHERE ' . $ownershipSql . $typeFilterSql . ' GROUP BY p.status'
        );
        $byStatusStmt->execute([...$ownershipParams, ...$typeParams]);
        $byStatus = $byStatusStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $bottleneckStmt = $pdo->prepare(
            "SELECT p.current_step, COUNT(*) AS cnt FROM proposals p
             WHERE " . $ownershipSql . $typeFilterSql . "
               AND p.status IN ('submitted','under_review')
               AND p.current_step IS NOT NULL
             GROUP BY p.current_step"
        );
        $bottleneckStmt->execute([...$ownershipParams, ...$typeParams]);
        $bottleneck = $bottleneckStmt->fetchAll() ?: [];

        $overdueMilestonesStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM milestones m
             INNER JOIN proposals p ON p.id = m.proposal_id
             WHERE " . $ownershipSql . $typeFilterSql . "
               AND m.status = 'overdue'
               AND p.status IN ('ongoing','approved')"
        );
        $overdueMilestonesStmt->execute([...$ownershipParams, ...$typeParams]);
        $overdueMilestones = (int) $overdueMilestonesStmt->fetchColumn();

        $overdueReportsStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM progress_reports pr
             INNER JOIN proposals p ON p.id = pr.proposal_id
             WHERE " . $ownershipSql . $typeFilterSql . "
               AND pr.status = 'draft'
               AND pr.due_date IS NOT NULL
               AND pr.due_date < " . Sql::currentDate()
        );
        $overdueReportsStmt->execute([...$ownershipParams, ...$typeParams]);
        $overdueReports = (int) $overdueReportsStmt->fetchColumn();

        return [
            'by_status' => $byStatus,
            'bottleneck' => $bottleneck,
            'overdue_milestones' => $overdueMilestones,
            'overdue_reports' => $overdueReports,
        ];
    }

    /**
     * @param list<string> $projectTypes
     * @return array{labels: list<string>, submitted: list<int>, approved: list<int>}
     */
    public static function monthlyWorkflowActivity(?int $userId = null, ?int $collegeId = null, array $projectTypes = []): array
    {
        $pdo = Database::pdo();
        $labels = [];
        $submitted = [];
        $approved = [];
        $monthKeys = [];
        $monthLookup = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTimeImmutable('first day of -' . $i . ' month');
            $key = $date->format('Y-m');
            $monthKeys[] = $key;
            $monthLookup[$key] = count($monthKeys) - 1;
            $labels[] = $date->format('M');
            $submitted[] = 0;
            $approved[] = 0;
        }

        $typeFilterSql = '';
        $typeParams = [];
        if ($projectTypes !== []) {
            $typeFilterSql = ' AND p.project_type IN (' . implode(',', array_fill(0, count($projectTypes), '?')) . ')';
            $typeParams = $projectTypes;
        }

        $submittedSql = 'SELECT ' . Sql::yearMonth('p.submitted_at') . ' AS month_key, COUNT(*) AS cnt
                         FROM proposals p
                         WHERE p.submitted_at IS NOT NULL';
        $submittedParams = [];
        if ($userId !== null) {
            $submittedSql .= ' AND ' . ProposalCoAuthorInvitation::coauthorAccessWhereSql('p');
            $submittedParams = [...$submittedParams, ...ProposalCoAuthorInvitation::coauthorAccessParams($userId)];
        } elseif ($collegeId !== null) {
            $submittedSql .= ' AND p.college_id = ?';
            $submittedParams[] = $collegeId;
        }
        $submittedSql .= $typeFilterSql;
        $submittedSql .= ' AND p.submitted_at >= ' . Sql::monthStartAgo(5) . '
                           GROUP BY month_key';

        $submittedStmt = $pdo->prepare($submittedSql);
        $submittedStmt->execute([...$submittedParams, ...$typeParams]);
        $submittedRows = $submittedStmt->fetchAll() ?: [];
        foreach ($submittedRows as $row) {
            $key = (string) ($row['month_key'] ?? '');
            if ($key !== '' && array_key_exists($key, $monthLookup)) {
                $submitted[$monthLookup[$key]] = (int) ($row['cnt'] ?? 0);
            }
        }

        $approvedSql = 'SELECT ' . Sql::yearMonth('p.approved_at') . ' AS month_key, COUNT(*) AS cnt
                        FROM proposals p
                        WHERE p.approved_at IS NOT NULL';
        $approvedParams = [];
        if ($userId !== null) {
            $approvedSql .= ' AND ' . ProposalCoAuthorInvitation::coauthorAccessWhereSql('p');
            $approvedParams = [...$approvedParams, ...ProposalCoAuthorInvitation::coauthorAccessParams($userId)];
        } elseif ($collegeId !== null) {
            $approvedSql .= ' AND p.college_id = ?';
            $approvedParams[] = $collegeId;
        }
        $approvedSql .= $typeFilterSql;
        $approvedSql .= ' AND p.approved_at >= ' . Sql::monthStartAgo(5) . '
                          GROUP BY month_key';

        $approvedStmt = $pdo->prepare($approvedSql);
        $approvedStmt->execute([...$approvedParams, ...$typeParams]);
        $approvedRows = $approvedStmt->fetchAll() ?: [];
        foreach ($approvedRows as $row) {
            $key = (string) ($row['month_key'] ?? '');
            if ($key !== '' && array_key_exists($key, $monthLookup)) {
                $approved[$monthLookup[$key]] = (int) ($row['cnt'] ?? 0);
            }
        }

        return [
            'labels' => $labels,
            'submitted' => $submitted,
            'approved' => $approved,
        ];
    }

    public static function markCompleted(int $id): void
    {
        Database::pdo()->prepare(
            "UPDATE proposals SET status = 'completed' WHERE id = ? AND status IN ('ongoing','approved')"
        )->execute([$id]);
    }

    /** @return list<array> */
    public static function ongoing(?int $collegeId = null, ?int $userId = null, ?string $projectType = null): array
    {
        $sql = "SELECT p.*, u.first_name, u.last_name, c.name AS college_name
                FROM proposals p
                INNER JOIN users u ON u.id = p.user_id
                INNER JOIN colleges c ON c.id = p.college_id
                WHERE p.status IN ('ongoing','approved','completed')";
        $params = [];
        if ($collegeId) {
            $sql .= ' AND p.college_id = ?';
            $params[] = $collegeId;
        }
        if ($userId) {
            $sql .= ' AND ' . ProposalCoAuthorInvitation::coauthorAccessWhereSql('p');
            $params = [...$params, ...ProposalCoAuthorInvitation::coauthorAccessParams($userId)];
        }
        if ($projectType !== null) {
            $sql .= ' AND p.project_type = ?';
            $params[] = $projectType;
        }
        $sql .= ' ORDER BY p.updated_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /** @return list<string> */
    private static function monitoringTypes(?string $projectType = null): array
    {
        if ($projectType !== null && in_array($projectType, MonitoringRoles::MONITORING_TYPES, true)) {
            return [$projectType];
        }

        return MonitoringRoles::MONITORING_TYPES;
    }

    /** @return list<array> */
    public static function forMonitoring(?int $collegeId = null, ?string $status = null, ?string $projectType = null): array
    {
        $types = self::monitoringTypes($projectType);
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $sql = 'SELECT p.*, u.first_name, u.last_name, c.name AS college_name
                FROM proposals p
                INNER JOIN users u ON u.id = p.user_id
                INNER JOIN colleges c ON c.id = p.college_id
                WHERE p.project_type IN (' . $placeholders . ')';
        $params = $types;

        if ($collegeId) {
            $sql .= ' AND p.college_id = ?';
            $params[] = $collegeId;
        }
        if ($status) {
            $sql .= ' AND p.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY p.updated_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array> */
    public static function pendingAtStep(string $step, ?int $collegeId = null, ?string $projectType = null): array
    {
        $types = self::monitoringTypes($projectType);
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $sql = 'SELECT p.*, u.first_name, u.last_name, c.name AS college_name
                FROM proposals p
                INNER JOIN users u ON u.id = p.user_id
                INNER JOIN colleges c ON c.id = p.college_id
                WHERE p.project_type IN (' . $placeholders . ')
                  AND p.status IN (\'submitted\', \'under_review\')
                  AND p.current_step = ?';
        $params = [...$types, $step];

        if ($collegeId) {
            $sql .= ' AND p.college_id = ?';
            $params[] = $collegeId;
        }
        $sql .= ' ORDER BY p.submitted_at ASC, p.updated_at ASC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed> */
    public static function monitoringStats(?int $collegeId = null, ?string $projectType = null): array
    {
        $types = self::monitoringTypes($projectType);
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $collegeFilter = $collegeId ? ' AND p.college_id = ?' : '';
        $params = $types;
        if ($collegeId) {
            $params[] = $collegeId;
        }

        $pdo = Database::pdo();

        $byStatusSql = 'SELECT p.status, COUNT(*) AS cnt FROM proposals p
                        WHERE p.project_type IN (' . $placeholders . ')' . $collegeFilter . '
                        GROUP BY p.status';
        $stmt = $pdo->prepare($byStatusSql);
        $stmt->execute($params);
        $byStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $pendingParams = [...$types];
        if ($collegeId) {
            $pendingParams[] = $collegeId;
        }
        $pendingSql = 'SELECT p.current_step, COUNT(*) AS cnt FROM proposals p
                       WHERE p.project_type IN (' . $placeholders . ')
                         AND p.status IN (\'submitted\', \'under_review\')
                         AND p.current_step IS NOT NULL' . $collegeFilter . '
                       GROUP BY p.current_step';
        $stmt = $pdo->prepare($pendingSql);
        $stmt->execute($pendingParams);
        $pendingByStep = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        return [
            'by_status' => $byStatus,
            'pending_by_step' => $pendingByStep,
            'pending_coordinator' => (int) (($pendingByStep[MonitoringRoles::COORDINATOR_RESEARCH] ?? 0) + ($pendingByStep[MonitoringRoles::COORDINATOR_EXTENSION] ?? 0)),
            'pending_coordinator_research' => (int) ($pendingByStep[MonitoringRoles::COORDINATOR_RESEARCH] ?? 0),
            'pending_coordinator_extension' => (int) ($pendingByStep[MonitoringRoles::COORDINATOR_EXTENSION] ?? 0),
            'pending_dean' => (int) ($pendingByStep[MonitoringRoles::DEAN] ?? 0),
            'pending_director_research' => (int) ($pendingByStep[MonitoringRoles::DIRECTOR_RESEARCH] ?? 0),
            'pending_director_extension' => (int) ($pendingByStep[MonitoringRoles::DIRECTOR_EXTENSION] ?? 0),
            'pending_vpride' => (int) ($pendingByStep[MonitoringRoles::VPRIDE] ?? 0),
        ];
    }
}
