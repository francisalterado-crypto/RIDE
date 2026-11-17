<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\Proposal;
use App\Models\ProposalCoAuthorInvitation;
use App\Models\User;
use App\Support\ProposalCoAuthors;

final class CoAuthorInvitationService
{
    /**
     * @param list<array<string, mixed>> $coauthors
     */
    public static function syncAfterProposalSave(int $proposalId, int $leadUserId, array $coauthors, ?string $previousSummaryJson = null): void
    {
        if ($proposalId <= 0 || $leadUserId <= 0) {
            return;
        }

        $proposal = Proposal::find($proposalId);
        if ($proposal === null) {
            return;
        }

        $summaryJson = (string) ($proposal['summary'] ?? '');
        $linkedIds = self::linkedUserIdsFromCoauthors($coauthors);
        ProposalCoAuthorInvitation::removeForProposalExcept($proposalId, $linkedIds);

        $previousStatuses = self::invitationStatusesFromSummary($previousSummaryJson);
        $updatedCoauthors = self::applyInvitationStatusesToCoauthors($coauthors, $proposalId, $previousStatuses);
        $encodedSummary = self::replaceCoauthorsInSummary($summaryJson, $updatedCoauthors);
        if ($encodedSummary !== null && $encodedSummary !== $summaryJson) {
            Proposal::updateSummary($proposalId, $encodedSummary);
            $summaryJson = $encodedSummary;
        }

        $lead = User::findById($leadUserId);
        $leadName = trim((string) ($lead['first_name'] ?? '') . ' ' . (string) ($lead['last_name'] ?? ''));
        if ($leadName === '') {
            $leadName = 'A researcher';
        }

        $title = (string) ($proposal['title'] ?? 'Untitled proposal');
        $previousLinkedIds = self::linkedUserIdsFromSummary($previousSummaryJson);

        foreach ($linkedIds as $inviteeUserId) {
            if ($inviteeUserId === $leadUserId) {
                continue;
            }

            $existing = ProposalCoAuthorInvitation::forProposalAndInvitee($proposalId, $inviteeUserId);
            $wasAlreadyListed = in_array($inviteeUserId, $previousLinkedIds, true);

            if ($existing !== null && (string) ($existing['status'] ?? '') === ProposalCoAuthorInvitation::STATUS_ACCEPTED) {
                continue;
            }

            if ($existing !== null && (string) ($existing['status'] ?? '') === ProposalCoAuthorInvitation::STATUS_PENDING && $wasAlreadyListed) {
                continue;
            }

            $invitationId = ProposalCoAuthorInvitation::upsertPending($proposalId, $inviteeUserId, $leadUserId);

            Notification::notifyProjectLeader(
                $inviteeUserId,
                'Co-author invitation',
                $leadName . ' invited you to be a co-author on "' . $title . '". Please accept or decline.',
                'coauthor-invitations/' . $invitationId
            );
        }
    }

    public static function accept(int $invitationId, int $userId): bool
    {
        $invitation = ProposalCoAuthorInvitation::find($invitationId);
        if ($invitation === null || (int) ($invitation['invitee_user_id'] ?? 0) !== $userId) {
            return false;
        }
        if ((string) ($invitation['status'] ?? '') !== ProposalCoAuthorInvitation::STATUS_PENDING) {
            return false;
        }

        if (!ProposalCoAuthorInvitation::markAccepted($invitationId)) {
            return false;
        }

        self::setSummaryInvitationStatus(
            (int) $invitation['proposal_id'],
            $userId,
            ProposalCoAuthorInvitation::STATUS_ACCEPTED
        );

        $inviteeName = trim(
            (string) ($invitation['invitee_first_name'] ?? '') . ' ' . (string) ($invitation['invitee_last_name'] ?? '')
        );
        if ($inviteeName === '') {
            $inviteeName = 'A faculty member';
        }

        $leadUserId = (int) ($invitation['lead_user_id'] ?? $invitation['invited_by_user_id'] ?? 0);
        $title = (string) ($invitation['proposal_title'] ?? 'your proposal');
        Notification::notifyProjectLeader(
            $leadUserId,
            'Co-author accepted',
            $inviteeName . ' accepted your co-author invitation for "' . $title . '".',
            'proposals/' . (int) $invitation['proposal_id']
        );

        return true;
    }

    public static function reject(int $invitationId, int $userId): bool
    {
        $invitation = ProposalCoAuthorInvitation::find($invitationId);
        if ($invitation === null || (int) ($invitation['invitee_user_id'] ?? 0) !== $userId) {
            return false;
        }
        if ((string) ($invitation['status'] ?? '') !== ProposalCoAuthorInvitation::STATUS_PENDING) {
            return false;
        }

        if (!ProposalCoAuthorInvitation::markRejected($invitationId)) {
            return false;
        }

        self::removeCoauthorFromSummary((int) $invitation['proposal_id'], $userId);

        $inviteeName = trim(
            (string) ($invitation['invitee_first_name'] ?? '') . ' ' . (string) ($invitation['invitee_last_name'] ?? '')
        );
        if ($inviteeName === '') {
            $inviteeName = 'A faculty member';
        }

        $leadUserId = (int) ($invitation['lead_user_id'] ?? $invitation['invited_by_user_id'] ?? 0);
        $title = (string) ($invitation['proposal_title'] ?? 'your proposal');
        Notification::notifyProjectLeader(
            $leadUserId,
            'Co-author declined',
            $inviteeName . ' did not accept your co-author invitation for "' . $title . '".',
            'proposals/' . (int) $invitation['proposal_id']
        );

        return true;
    }

    /**
     * @param list<array<string, mixed>> $coauthors
     * @return list<int>
     */
    private static function linkedUserIdsFromCoauthors(array $coauthors): array
    {
        $ids = [];
        foreach ($coauthors as $row) {
            if (!is_array($row)) {
                continue;
            }
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId > 0) {
                $ids[] = $userId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<int>
     */
    private static function linkedUserIdsFromSummary(?string $summaryJson): array
    {
        if ($summaryJson === null || trim($summaryJson) === '') {
            return [];
        }

        $summary = json_decode($summaryJson, true);
        if (!is_array($summary)) {
            return [];
        }

        $coauthors = $summary['coauthors'] ?? [];
        if (!is_array($coauthors)) {
            return [];
        }

        return self::linkedUserIdsFromCoauthors($coauthors);
    }

    /**
     * @return array<int, string>
     */
    private static function invitationStatusesFromSummary(?string $summaryJson): array
    {
        if ($summaryJson === null || trim($summaryJson) === '') {
            return [];
        }

        $summary = json_decode($summaryJson, true);
        if (!is_array($summary)) {
            return [];
        }

        $statuses = [];
        foreach ($summary['coauthors'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $status = trim((string) ($row['invitation_status'] ?? ''));
            if ($status !== '') {
                $statuses[$userId] = $status;
            }
        }

        return $statuses;
    }

    /**
     * @param list<array<string, mixed>> $coauthors
     * @param array<int, string> $previousStatuses
     * @return list<array<string, mixed>>
     */
    private static function applyInvitationStatusesToCoauthors(
        array $coauthors,
        int $proposalId,
        array $previousStatuses
    ): array {
        $result = [];
        foreach ($coauthors as $row) {
            if (!is_array($row)) {
                continue;
            }
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId <= 0) {
                $result[] = $row;
                continue;
            }

            $invitation = ProposalCoAuthorInvitation::forProposalAndInvitee($proposalId, $userId);
            if ($invitation !== null) {
                $row['invitation_status'] = (string) ($invitation['status'] ?? ProposalCoAuthorInvitation::STATUS_PENDING);
            } elseif (isset($previousStatuses[$userId])) {
                $row['invitation_status'] = $previousStatuses[$userId];
            } else {
                $row['invitation_status'] = ProposalCoAuthorInvitation::STATUS_PENDING;
            }

            $result[] = $row;
        }

        return $result;
    }

    /**
     * @param list<array<string, mixed>> $coauthors
     */
    private static function replaceCoauthorsInSummary(string $summaryJson, array $coauthors): ?string
    {
        if (trim($summaryJson) === '') {
            return null;
        }

        $summary = json_decode($summaryJson, true);
        if (!is_array($summary) || !array_key_exists('coauthors', $summary)) {
            return null;
        }

        $summary['coauthors'] = $coauthors;
        $encoded = json_encode($summary, JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $encoded;
    }

    private static function setSummaryInvitationStatus(int $proposalId, int $userId, string $status): void
    {
        $proposal = Proposal::find($proposalId);
        if ($proposal === null) {
            return;
        }

        $summaryJson = (string) ($proposal['summary'] ?? '');
        $summary = json_decode($summaryJson, true);
        if (!is_array($summary) || !is_array($summary['coauthors'] ?? null)) {
            return;
        }

        $changed = false;
        foreach ($summary['coauthors'] as $index => $row) {
            if (!is_array($row) || (int) ($row['user_id'] ?? 0) !== $userId) {
                continue;
            }
            $summary['coauthors'][$index]['invitation_status'] = $status;
            $changed = true;
        }

        if (!$changed) {
            return;
        }

        $encoded = json_encode($summary, JSON_UNESCAPED_SLASHES);
        if ($encoded !== false) {
            Proposal::updateSummary($proposalId, $encoded);
        }
    }

    private static function removeCoauthorFromSummary(int $proposalId, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $proposal = Proposal::find($proposalId);
        if ($proposal === null) {
            return;
        }

        $summaryJson = (string) ($proposal['summary'] ?? '');
        $summary = json_decode($summaryJson, true);
        if (!is_array($summary) || !is_array($summary['coauthors'] ?? null)) {
            return;
        }

        $filtered = [];
        $removed = false;
        foreach ($summary['coauthors'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ((int) ($row['user_id'] ?? 0) === $userId) {
                $removed = true;
                continue;
            }
            $filtered[] = $row;
        }

        if (!$removed) {
            return;
        }

        $summary['coauthors'] = $filtered;
        $encoded = json_encode($summary, JSON_UNESCAPED_SLASHES);
        if ($encoded !== false) {
            Proposal::updateSummary($proposalId, $encoded);
        }
    }
}
