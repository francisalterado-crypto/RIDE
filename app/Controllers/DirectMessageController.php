<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\DirectMessage;
use App\Models\Notification;
use App\Models\User;
use App\Support\DirectMessaging;

final class DirectMessageController
{
    public function conversation(int $partnerId): void
    {
        Notification::checkOverdueAlerts();
        if (!DirectMessaging::isEnabledForCurrentUser()) {
            http_response_code(403);
            view('errors.403');
            exit;
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        if ($partnerId <= 0 || !DirectMessaging::canExchange($userId, $partnerId)) {
            set_flash('error', 'You cannot view this conversation.');
            redirect('messages');
        }

        $partner = User::findById($partnerId);
        if ($partner === null) {
            set_flash('error', 'Recipient not found.');
            redirect('messages');
        }

        DirectMessage::markThreadRead($userId, $partnerId);
        $messages = DirectMessage::thread($userId, $partnerId);
        $partnerName = trim((string) ($partner['first_name'] ?? '') . ' ' . (string) ($partner['last_name'] ?? ''));
        $partnerRoleLabel = DirectMessaging::isFacultyUser($partnerId)
            ? 'Faculty'
            : DirectMessaging::staffRoleLabelForUser($partnerId);

        view('messages.conversation', [
            'user' => $user,
            'partner' => $partner,
            'partnerId' => $partnerId,
            'partnerName' => $partnerName,
            'partnerRoleLabel' => $partnerRoleLabel,
            'messages' => $messages,
            'pageTitle' => 'Conversation | RIDE',
            'pageHeading' => 'Message',
            'pageSubtitle' => $partnerName !== '' ? $partnerName . ' · ' . $partnerRoleLabel : $partnerRoleLabel,
        ]);
    }

    public function send(): void
    {
        if (!DirectMessaging::isEnabledForCurrentUser()) {
            http_response_code(403);
            view('errors.403');
            exit;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('messages');
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $recipientId = (int) ($_POST['recipient_id'] ?? 0);
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($recipientId <= 0 || !DirectMessaging::canExchange($userId, $recipientId)) {
            set_flash('error', 'You cannot send a message to this recipient.');
            redirect('messages');
        }

        if ($body === '') {
            set_flash('error', 'Message cannot be empty.');
            redirect($recipientId > 0 ? 'messages/conversation/' . $recipientId : 'messages');
        }

        if (strlen($body) > 2000) {
            set_flash('error', 'Message must be 2000 characters or less.');
            redirect('messages/conversation/' . $recipientId);
        }

        DirectMessage::send($userId, $recipientId, $body);

        $senderName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
        \App\Models\Notification::notifyProjectLeader(
            $recipientId,
            'New message',
            ($senderName !== '' ? $senderName : 'Someone') . ' sent you a message.',
            'messages/conversation/' . $userId
        );

        set_flash('success', 'Message sent.');
        redirect('messages/conversation/' . $recipientId);
    }
}
