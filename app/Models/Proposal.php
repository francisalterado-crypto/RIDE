<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Sql;
use App\Support\AccessControl;
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

    /**
     * @param array{0: string, 1: list<mixed>}|null $where extra condition on alias p, e.g. from ProposalRegistry::kindWhere()
     * @return list<array>
     */
    public static function forUser(int $userId, ?array $where = null): array
    {
        $accessSql = ProposalCoAuthorInvitation::coauthorAccessWhereSql('p');
        $stmt = Database::pdo()->prepare(
            'SELECT p.*, u.first_name, u.last_name, c.name AS college_name,
                    CASE WHEN p.user_id = ? THEN \'lead\' ELSE \'coauthor\' END AS membership
             FROM proposals p
             LEFT JOIN users u ON u.id = p.user_id
             LEFT JOIN colleges c ON c.id = p.college_id
             WHERE ' . $accessSql . ($where !== null ? ' AND (' . $where[0] . ')' : '') . '
             ORDER BY p.updated_at DESC'
        );
        $stmt->execute([$userId, ...ProposalCoAuthorInvitation::coauthorAccessParams($userId), ...($where[1] ?? [])]);

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
        $sql = 'SELECT p.*, u.first_name, u.last_name, c.name AS college_name,
                       CASE WHEN p.user_id = ? THEN \'lead\' ELSE \'coauthor\' END AS membership
                FROM proposals p
                LEFT JOIN users u ON u.id = p.user_id
                LEFT JOIN colleges c ON c.id = p.college_id
                WHERE ' . $accessSql . '
                  AND p.project_type IN (' . $placeholders . ')
                ORDER BY p.updated_at DESC';
        $params = [$userId, ...ProposalCoAuthorInvitation::coauthorAccessParams($userId), ...$projectTypes];
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array> */
    public static function forCollege(int $collegeId, ?string $status = null, ?string $formType = null): array
    {
        $sql = 'SELECT p.*, u.first_name, u.last_name FROM proposals p
                INNER JOIN users u ON u.id = p.user_id
                WHERE p.college_id = ?';
        $params = [$collegeId];
        if ($status) {
            $sql .= ' AND p.status = ?';
            $params[] = $status;
        }
        if ($formType !== null) {
            $sql .= ' AND p.form_type = ?';
            $params[] = $formType;
        }
        $sql .= ' ORDER BY p.updated_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array{0: string, 1: list<mixed>}|null $where extra condition on alias p, e.g. from ProposalRegistry::kindWhere()
     * @return list<array>
     */
    public static function all(?string $status = null, ?string $projectType = null, ?array $where = null): array
    {
        $sql = 'SELECT p.*, u.first_name, u.last_name, c.name AS college_name
                FROM proposals p
                INNER JOIN users u ON u.id = p.user_id
                INNER JOIN colleges c ON c.id = p.college_id';
        $params = [];
        $conditions = [];
        if ($status) {
            $conditions[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($projectType !== null) {
            $conditions[] = 'p.project_type = ?';
            $params[] = $projectType;
        }
        if ($where !== null) {
            $conditions[] = '(' . $where[0] . ')';
            array_push($params, ...$where[1]);
        }
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
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
            'INSERT INTO proposals (user_id, college_id, campus_id, project_type, title, summary, form_type, funding_source, risk_level, ethics_required, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['college_id'],
            $data['campus_id'] ?: null,
            $data['project_type'],
            $data['title'],
            $data['summary'] ?? null,
            self::formTypeFromSummary($data['summary'] ?? null),
            $data['funding_source'] ?? null,
            $data['risk_level'] ?? 'low',
            !empty($data['ethics_required']) ? 1 : 0,
            'draft',
        ]);
        $id = (int) Database::pdo()->lastInsertId();
        // The proposal carries the leader's academic program unless the form chose one.
        Database::pdo()->prepare(
            'UPDATE proposals SET academic_program_id = (SELECT u.academic_program_id FROM users u WHERE u.id = proposals.user_id) WHERE id = ?'
        )->execute([$id]);
        self::saveCatalogLinks($id, $data);

        return $id;
    }

    /**
     * Store the catalog references a form sent (extension program, extension project, academic program).
     * Keys missing from $data are left unchanged, so forms without these fields do not clear them.
     *
     * @param array<string, mixed> $data
     */
    public static function saveCatalogLinks(int $id, array $data): void
    {
        foreach (['extension_program_id', 'extension_project_id', 'academic_program_id'] as $column) {
            if (array_key_exists($column, $data)) {
                Database::pdo()->prepare("UPDATE proposals SET {$column} = ? WHERE id = ?")
                    ->execute([$data[$column] !== null ? (int) $data[$column] : null, $id]);
            }
        }
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE proposals SET title = ?, summary = ?, form_type = ?, funding_source = ?, risk_level = ?, ethics_required = ?, project_type = ?
             WHERE id = ? AND status IN (\'draft\', \'returned\')'
        );
        $stmt->execute([
            $data['title'],
            $data['summary'] ?? null,
            self::formTypeFromSummary($data['summary'] ?? null),
            $data['funding_source'] ?? null,
            $data['risk_level'] ?? 'low',
            !empty($data['ethics_required']) ? 1 : 0,
            $data['project_type'],
            $id,
        ]);
        $current = self::find($id);
        if ($current !== null && in_array((string) $current['status'], ['draft', 'returned'], true)) {
            self::saveCatalogLinks($id, $data);
        }
    }

    public static function updateConsolidatedDraft(int $id, string $title, string $summaryJson): void
    {
        Database::pdo()->prepare(
            'UPDATE proposals SET title = ?, summary = ?, form_type = ? WHERE id = ? AND status IN (\'draft\', \'returned\')'
        )->execute([$title, $summaryJson, self::formTypeFromSummary($summaryJson), $id]);
    }

    public static function updateSummaryCoauthorsOnly(int $id, string $summaryJson): void
    {
        Database::pdo()->prepare(
            'UPDATE proposals SET summary = ?, form_type = ? WHERE id = ?'
        )->execute([$summaryJson, self::formTypeFromSummary($summaryJson), $id]);
    }

    /**
     * The form_type key of a summary JSON. It is copied to proposals.form_type on every write so queries can
     * filter by form without decoding each row; the JSON stays the source of truth.
     */
    public static function formTypeFromSummary(?string $summaryJson): ?string
    {
        if ($summaryJson === null || $summaryJson === '') {
            return null;
        }
        $decoded = json_decode($summaryJson, true);
        $formType = is_array($decoded) ? ($decoded['form_type'] ?? null) : null;

        return is_string($formType) && $formType !== '' ? substr($formType, 0, 60) : null;
    }

    /**
     * Draft or returned records of one form type that a user leads or co-authors, newest first.
     *
     * @return list<array>
     */
    public static function editableForUserByFormType(int $userId, string $formType): array
    {
        $accessSql = ProposalCoAuthorInvitation::coauthorAccessWhereSql('p');
        $stmt = Database::pdo()->prepare(
            'SELECT p.*, u.first_name, u.last_name, c.name AS college_name,
                    CASE WHEN p.user_id = ? THEN \'lead\' ELSE \'coauthor\' END AS membership
             FROM proposals p
             LEFT JOIN users u ON u.id = p.user_id
             LEFT JOIN colleges c ON c.id = p.college_id
             WHERE ' . $accessSql . '
               AND p.form_type = ?
               AND p.status IN (\'draft\', \'returned\')
             ORDER BY p.updated_at DESC'
        );
        $stmt->execute([$userId, ...ProposalCoAuthorInvitation::coauthorAccessParams($userId), $formType]);

        return $stmt->fetchAll() ?: [];
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
        // The route is fixed at submission (see AccessControl::reviewChain()): the full chain, a College
        // Coordinator's own record (skips their endorsement), or a quarterly report prepared by the RIDE coordinator.
        $route = AccessControl::submitRoute($proposal);
        $proposal['coordinator_filed'] = $route;
        $proposal['status'] = 'submitted';
        $step = AccessControl::firstStep($proposal);
        $stmt = Database::pdo()->prepare(
            'UPDATE proposals SET status = \'submitted\', current_step = ?, coordinator_filed = ?, submitted_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$step, $route, $id]);
        Notification::notifyStepHolders($proposal, $step);
    }

    public static function addComment(int $proposalId, int $userId, string $comment, string $action = 'comment', ?string $step = null): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO proposal_comments (proposal_id, user_id, step, comment, action) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$proposalId, $userId, $step, $comment, $action]);
    }

    /**
     * Applies an approval action as one transaction. The record row is locked first, so two approvers acting at
     * the same time are serialized. When $expectedStep is given and the record has since moved to another step,
     * nothing is changed and false is returned. $beforeAction runs inside the transaction, after the step check.
     */
    public static function advanceWorkflow(
        int $id,
        string $action,
        int $userId,
        string $comment = '',
        ?string $expectedStep = null,
        ?callable $beforeAction = null
    ): bool {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT status, current_step FROM proposals WHERE id = ? FOR UPDATE');
            $lock->execute([$id]);
            $locked = $lock->fetch();
            if (!$locked
                || !in_array((string) $locked['status'], ['submitted', 'under_review'], true)
                || ($expectedStep !== null && (string) ($locked['current_step'] ?? '') !== $expectedStep)) {
                $pdo->rollBack();

                return false;
            }

            // Writes that belong to this action (e.g. the reviewer's evaluation) commit or roll back with it.
            if ($beforeAction !== null) {
                $beforeAction();
            }
            $applied = self::applyWorkflowAction($id, $action, $userId, $comment);
            if ($applied) {
                $pdo->commit();
            } else {
                $pdo->rollBack();
            }

            return $applied;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Body of advanceWorkflow(); runs inside its transaction with the record row locked. */
    private static function applyWorkflowAction(int $id, string $action, int $userId, string $comment): bool
    {
        $proposal = self::find($id);
        if (!$proposal) {
            return false;
        }

        $step = $proposal['current_step'] ?? AccessControl::firstStep($proposal);

        if ($action === 'return') {
            Database::pdo()->prepare(
                'UPDATE proposals SET status = \'returned\', current_step = NULL WHERE id = ?'
            )->execute([$id]);
            self::addComment($id, $userId, $comment, 'return', $step);
            Notification::notifyProjectLeader(
                (int) $proposal['user_id'],
                'Returned for revision',
                '"' . $proposal['title'] . '" was returned for revision at '
                    . AccessControl::stepLabel((string) $step) . '. Comments: ' . $comment,
                'proposals/' . $id
            );
            return true;
        }

        if ($action === 'reject') {
            Database::pdo()->prepare(
                'UPDATE proposals SET status = \'suspended\', current_step = NULL WHERE id = ?'
            )->execute([$id]);
            self::addComment($id, $userId, $comment, 'reject', $step);
            return true;
        }

        $next = AccessControl::nextStep((string) $step, $proposal);
        if ($next === 'approved' && \App\Support\ProposalRegistry::kindOf($proposal) === 'report') {
            // Reports are approved records, not projects: no project code, no monitoring.
            Database::pdo()->prepare(
                'UPDATE proposals SET status = \'approved\', current_step = NULL, approved_at = NOW() WHERE id = ?'
            )->execute([$id]);
            Notification::notifyProjectLeader(
                (int) $proposal['user_id'],
                'Report approved',
                'Your report "' . $proposal['title'] . '" received final approval.',
                'proposals/' . $id
            );
        } elseif ($next === 'approved' && \App\Support\ResearchRequestForms::typeOf($proposal) !== null) {
            // Request forms are approvals of a request, not new projects: own REQ- code, no monitoring.
            $code = self::generateRequestCode($proposal);
            Database::pdo()->prepare(
                'UPDATE proposals SET status = \'approved\', current_step = NULL, project_code = ?, approved_at = NOW() WHERE id = ?'
            )->execute([$code, $id]);
            Notification::notifyProjectLeader(
                (int) $proposal['user_id'],
                'Request approved',
                'Your request "' . $proposal['title'] . '" was approved. Code: ' . $code,
                'proposals/' . $id
            );
        } elseif ($next === 'approved') {
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
            Notification::notifyStepHolders($proposal, $next);
        }

        $hash = hash('sha256', $id . '|' . $userId . '|' . time());
        $stmt = Database::pdo()->prepare(
            'INSERT INTO approval_actions (proposal_id, user_id, step, action, signature_hash) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $userId, $step, 'approve', $hash]);
        self::addComment($id, $userId, $comment ?: 'Approved.', 'approve', $step);

        if ($step === AccessControl::DIRECTOR_RESEARCH) {
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

    private static function generateProjectCode(array $proposal): string
    {
        $year = date('Y');
        $seq = self::nextCodeSequence('RIDE-' . $year . '-');

        return sprintf('RIDE-%s-%s-%03d', $year, self::collegeCode($proposal), $seq);
    }

    /** Code of an approved RDO request form: REQ-<year>-<college>-<seq>, numbered per year. */
    private static function generateRequestCode(array $proposal): string
    {
        $year = date('Y');
        $seq = self::nextCodeSequence('REQ-' . $year . '-');

        return sprintf('REQ-%s-%s-%03d', $year, self::collegeCode($proposal), $seq);
    }

    private static function collegeCode(array $proposal): string
    {
        $college = Database::pdo()->prepare('SELECT code FROM colleges WHERE id = ?');
        $college->execute([$proposal['college_id']]);

        return (string) ($college->fetchColumn() ?: 'GEN');
    }

    /**
     * Next number of a per-year code series ("RIDE-2026-", "REQ-2026-") from its code_sequences counter row.
     * Must run inside a transaction: the row stays locked until commit, so concurrent approvals get distinct
     * numbers. A missing counter starts from the highest number already issued with that prefix.
     */
    private static function nextCodeSequence(string $prefix): int
    {
        $pdo = Database::pdo();
        $select = $pdo->prepare('SELECT last_value FROM code_sequences WHERE scope = ? FOR UPDATE');
        $select->execute([$prefix]);
        $last = $select->fetchColumn();

        if ($last === false) {
            $issued = $pdo->prepare('SELECT project_code FROM proposals WHERE project_code LIKE ?');
            $issued->execute([$prefix . '%']);
            $highest = 0;
            foreach ($issued->fetchAll(PDO::FETCH_COLUMN) as $code) {
                if (preg_match('/-(\d+)$/', (string) $code, $m) === 1) {
                    $highest = max($highest, (int) $m[1]);
                }
            }
            $pdo->prepare(Sql::insertIgnore('INSERT INTO code_sequences (scope, last_value) VALUES (?, ?)', 'scope'))
                ->execute([$prefix, $highest]);
            $select->execute([$prefix]);
            $last = $select->fetchColumn();
        }

        $next = (int) $last + 1;
        $pdo->prepare('UPDATE code_sequences SET last_value = ? WHERE scope = ?')->execute([$next, $prefix]);

        return $next;
    }

    /**
     * Latest "return for revision" comment per proposal.
     *
     * @param list<int> $proposalIds
     * @return array<int, array{comment: string, name: string, created_at: string}>
     */
    public static function latestReturnComments(array $proposalIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $proposalIds))));
        if ($ids === []) {
            return [];
        }
        $stmt = Database::pdo()->prepare(
            'SELECT pc.proposal_id, pc.comment, pc.created_at, u.first_name, u.last_name
             FROM proposal_comments pc
             INNER JOIN users u ON u.id = pc.user_id
             WHERE pc.action = \'return\' AND pc.proposal_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')
             ORDER BY pc.created_at ASC, pc.id ASC'
        );
        $stmt->execute($ids);
        $out = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $out[(int) $row['proposal_id']] = [
                'comment' => (string) $row['comment'],
                'name' => trim((string) $row['first_name'] . ' ' . (string) $row['last_name']),
                'created_at' => (string) $row['created_at'],
            ];
        }

        return $out;
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
     * Submissions and approvals per month over the last six months. Proposals (RDO-20 research proposals and
     * ESO-03 extension proposals) are counted apart from reports and request forms, which share the table.
     *
     * @param list<string> $projectTypes
     * @return array{labels: list<string>, submitted: list<int>, approved: list<int>, reports_submitted: list<int>, reports_approved: list<int>}
     */
    public static function monthlyWorkflowActivity(?int $userId = null, ?int $collegeId = null, array $projectTypes = []): array
    {
        $labels = [];
        $monthLookup = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTimeImmutable('first day of -' . $i . ' month');
            $monthLookup[$date->format('Y-m')] = count($labels);
            $labels[] = $date->format('M');
        }
        $zeros = array_fill(0, count($labels), 0);
        $series = ['submitted' => $zeros, 'approved' => $zeros, 'reports_submitted' => $zeros, 'reports_approved' => $zeros];

        $sql = 'SELECT p.submitted_at, p.approved_at, p.summary, p.project_type FROM proposals p
                WHERE (p.submitted_at >= ' . Sql::monthStartAgo(5) . ' OR p.approved_at >= ' . Sql::monthStartAgo(5) . ')';
        $params = [];
        if ($userId !== null) {
            $sql .= ' AND ' . ProposalCoAuthorInvitation::coauthorAccessWhereSql('p');
            $params = [...$params, ...ProposalCoAuthorInvitation::coauthorAccessParams($userId)];
        } elseif ($collegeId !== null) {
            $sql .= ' AND p.college_id = ?';
            $params[] = $collegeId;
        }
        if ($projectTypes !== []) {
            $sql .= ' AND p.project_type IN (' . implode(',', array_fill(0, count($projectTypes), '?')) . ')';
            $params = [...$params, ...$projectTypes];
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        foreach ($stmt->fetchAll() ?: [] as $row) {
            $prefix = proposal_is_proposal_form($row) ? '' : 'reports_';
            foreach (['submitted' => 'submitted_at', 'approved' => 'approved_at'] as $key => $column) {
                $month = substr((string) ($row[$column] ?? ''), 0, 7);
                if (isset($monthLookup[$month])) {
                    $series[$prefix . $key][$monthLookup[$month]]++;
                }
            }
        }

        return ['labels' => $labels] + $series;
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

        // Only proposals become projects: approved requests and reports are not.
        return array_values(array_filter(
            $stmt->fetchAll() ?: [],
            static fn (array $p): bool => \App\Support\ProposalRegistry::kindOf($p) === 'proposal'
        ));
    }

    /** @return list<string> */
    private static function monitoringTypes(?string $projectType = null): array
    {
        if ($projectType !== null && in_array($projectType, AccessControl::MONITORING_TYPES, true)) {
            return [$projectType];
        }

        return AccessControl::MONITORING_TYPES;
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
            'pending_coordinator' => (int) (($pendingByStep[AccessControl::COORDINATOR_RESEARCH] ?? 0) + ($pendingByStep[AccessControl::COORDINATOR_EXTENSION] ?? 0)),
            'pending_coordinator_research' => (int) ($pendingByStep[AccessControl::COORDINATOR_RESEARCH] ?? 0),
            'pending_coordinator_extension' => (int) ($pendingByStep[AccessControl::COORDINATOR_EXTENSION] ?? 0),
            'pending_dean' => (int) ($pendingByStep[AccessControl::DEAN] ?? 0),
            'pending_director_research' => (int) ($pendingByStep[AccessControl::DIRECTOR_RESEARCH] ?? 0),
            'pending_director_extension' => (int) ($pendingByStep[AccessControl::DIRECTOR_EXTENSION] ?? 0),
            'pending_vpride' => (int) ($pendingByStep[AccessControl::VPRIDE] ?? 0),
        ];
    }
}
