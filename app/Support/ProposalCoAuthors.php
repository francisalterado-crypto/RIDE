<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ProposalCoAuthorInvitation;

final class ProposalCoAuthors
{
    /** MariaDB/MySQL REGEXP: summary JSON contains this faculty user_id as a co-author. */
    public static function userIdRegexpPattern(int $userId): string
    {
        return '"user_id":' . $userId . '([^0-9]|$)';
    }

    public static function summaryContainsUserId(?string $summaryJson, int $userId): bool
    {
        return self::summaryContainsAcceptedUserId($summaryJson, $userId);
    }

    public static function summaryContainsAcceptedUserId(?string $summaryJson, int $userId): bool
    {
        if ($userId <= 0 || $summaryJson === null || trim($summaryJson) === '') {
            return false;
        }

        $summary = json_decode($summaryJson, true);
        if (!is_array($summary)) {
            return false;
        }

        $coauthors = $summary['coauthors'] ?? [];
        if (!is_array($coauthors)) {
            return false;
        }

        foreach ($coauthors as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ((int) ($row['user_id'] ?? 0) !== $userId) {
                continue;
            }

            $status = trim((string) ($row['invitation_status'] ?? ''));
            if ($status === '' || $status === 'accepted') {
                return true;
            }
        }

        return false;
    }

    public static function userHasCoauthorAccess(int $proposalId, ?string $summaryJson, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if (\App\Models\ProposalCoAuthorInvitation::hasAcceptedAccess($proposalId, $userId)) {
            return true;
        }

        if (\App\Models\ProposalCoAuthorInvitation::forProposalAndInvitee($proposalId, $userId) !== null) {
            return false;
        }

        return self::summaryContainsAcceptedUserId($summaryJson, $userId);
    }

    public static function invitationStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Pending acceptance',
            'accepted' => 'Accepted',
            'rejected' => 'Declined',
            default => '',
        };
    }

    public static function shouldShowCoAuthorAccountDetails(): bool
    {
        return !AccessControl::isCoordinatorResearch();
    }

    public static function isDeclinedCoauthor(array $row): bool
    {
        return trim((string) ($row['invitation_status'] ?? '')) === ProposalCoAuthorInvitation::STATUS_REJECTED;
    }

    /**
     * True when a registered faculty co-author is listed but has not accepted yet.
     * Manual name-only co-authors (no user_id) do not block submission.
     */
    public static function hasPendingLinkedCoAuthors(int $proposalId, ?string $summaryJson): bool
    {
        if ($proposalId <= 0 || $summaryJson === null || trim($summaryJson) === '') {
            return false;
        }

        $summary = json_decode($summaryJson, true);
        if (!is_array($summary)) {
            return false;
        }

        $coauthors = $summary['coauthors'] ?? [];
        if (!is_array($coauthors)) {
            return false;
        }

        foreach ($coauthors as $row) {
            if (!is_array($row)) {
                continue;
            }

            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $summaryStatus = trim((string) ($row['invitation_status'] ?? ''));
            if ($summaryStatus === ProposalCoAuthorInvitation::STATUS_ACCEPTED) {
                continue;
            }

            $invitation = ProposalCoAuthorInvitation::forProposalAndInvitee($proposalId, $userId);
            if ($invitation === null) {
                if ($summaryStatus === ProposalCoAuthorInvitation::STATUS_PENDING) {
                    return true;
                }

                continue;
            }

            if ((string) ($invitation['status'] ?? '') === ProposalCoAuthorInvitation::STATUS_PENDING) {
                return true;
            }
        }

        return false;
    }

    public static function isAtOrPastCoordinatorEndorsement(?string $currentStep): bool
    {
        $step = trim((string) $currentStep);
        if ($step === '') {
            return false;
        }

        return in_array($step, [
            AccessControl::COORDINATOR_RESEARCH,
            AccessControl::COORDINATOR_EXTENSION,
            AccessControl::DEAN,
            AccessControl::DIRECTOR_RESEARCH,
            AccessControl::DIRECTOR_EXTENSION,
            AccessControl::VPRIDE,
        ], true);
    }

    /**
     * Hides declined faculty co-authors once the proposal is at coordinator endorsement or later.
     *
     * @param list<array<string, mixed>> $coauthors
     * @return list<array<string, mixed>>
     */
    public static function coauthorsForApplicantDisplay(array $coauthors, ?string $currentStep): array
    {
        if (!self::isAtOrPastCoordinatorEndorsement($currentStep)) {
            return $coauthors;
        }

        $visible = [];
        foreach ($coauthors as $row) {
            if (!is_array($row) || self::isDeclinedCoauthor($row)) {
                continue;
            }
            $visible[] = $row;
        }

        return $visible;
    }

    /**
     * @param list<array<string, mixed>> $facultyRows
     * @return list<array{id: int, label: string, last_name: string, first_name: string, middle_name: string, college_name: string}>
     */
    public static function optionsForPicker(array $facultyRows, int $excludeUserId = 0): array
    {
        $options = [];
        foreach ($facultyRows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || $id === $excludeUserId) {
                continue;
            }

            $lastName = trim((string) ($row['last_name'] ?? ''));
            $firstName = trim((string) ($row['first_name'] ?? ''));
            $collegeName = trim((string) ($row['college_name'] ?? ''));
            $label = trim($lastName . ', ' . $firstName);
            if ($collegeName !== '') {
                $label .= ' | ' . $collegeName;
            }

            $options[] = [
                'id' => $id,
                'label' => $label,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'middle_name' => '',
                'college_name' => $collegeName,
            ];
        }

        return $options;
    }
}
