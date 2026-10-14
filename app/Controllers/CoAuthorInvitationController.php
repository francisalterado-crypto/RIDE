<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Notification;
use App\Models\ProposalCoAuthorInvitation;
use App\Services\CoAuthorInvitationService;

final class CoAuthorInvitationController
{
    public function show(int $id): void
    {
        Notification::checkOverdueAlerts();
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $invitation = ProposalCoAuthorInvitation::find($id);

        if ($invitation === null || (int) ($invitation['invitee_user_id'] ?? 0) !== $userId) {
            set_flash('error', 'Invitation not found.');
            redirect('dashboard');
        }

        view('coauthor-invitations.show', [
            'user' => $user,
            'invitation' => $invitation,
            'pageTitle' => 'Co-author invitation | RIDE',
            'pageHeading' => 'Co-author invitation',
            'pageSubtitle' => 'Review and respond to this request',
        ]);
    }

    public function accept(int $id): void
    {
        $this->respond($id, true);
    }

    public function reject(int $id): void
    {
        $this->respond($id, false);
    }

    private function respond(int $id, bool $accept): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('dashboard');
        }

        if (!Auth::hasRole('faculty')) {
            http_response_code(403);
            view('errors.403');
            exit;
        }

        $userId = (int) (Auth::user()['id'] ?? 0);
        $ok = $accept
            ? CoAuthorInvitationService::accept($id, $userId)
            : CoAuthorInvitationService::reject($id, $userId);

        if (!$ok) {
            set_flash('error', 'This invitation is no longer available or has already been answered.');
            redirect('dashboard');
        }

        set_flash(
            'success',
            $accept
                ? 'You accepted the co-author invitation. The proposal is now on your dashboard.'
                : 'You declined the co-author invitation. The lead author has been notified.'
        );
        redirect($accept ? 'proposals' : 'dashboard');
    }
}
