<?php

namespace App\Services;

use App\Database;

class MessageService
{
    const UNCONFIRMED_MESSAGE_LIMIT = 3;

    public static function canSendMessage(int $senderId): array
    {
        $db = Database::getInstance();

        $sender = $db->fetchOne('SELECT email_confirmed FROM senders WHERE id = ?', [$senderId]);
        if (!$sender) {
            return ['can_send' => false, 'reason' => 'Sender not found'];
        }

        // Confirmed users can send unlimited messages
        if ($sender['email_confirmed']) {
            return ['can_send' => true];
        }

        // Unconfirmed users can send up to 3 messages
        $messageCount = $db->fetchOne(
            'SELECT COUNT(*) as count FROM messages WHERE sender_id = ?',
            [$senderId]
        );

        if ($messageCount['count'] >= self::UNCONFIRMED_MESSAGE_LIMIT) {
            return [
                'can_send' => false,
                'reason' => 'You\'ve sent 3 Smiles! Please confirm your email to keep spreading smiles.'
            ];
        }

        return ['can_send' => true];
    }

    public static function createMessage(
        int $senderId,
        string $recipientName,
        string $recipientEmail,
        string $message
    ): array {
        $db = Database::getInstance();

        // Check if user can send
        $canSend = self::canSendMessage($senderId);
        if (!$canSend['can_send']) {
            // Get sender info to auto-send confirmation email
            $sender = $db->fetchOne('SELECT name, email, email_confirmed, email_confirmation_token, dashboard_url FROM senders WHERE id = ?', [$senderId]);

            // Auto-send confirmation email if they hit the limit
            if ($sender && !$sender['email_confirmed']) {
                \App\Services\SignupService::sendConfirmationOnlyEmail(
                    $sender['name'],
                    $sender['email'],
                    $sender['email_confirmation_token'],
                    $sender['dashboard_url']
                );
            }

            throw new \Exception($canSend['reason']);
        }

        // Generate unique message URL
        $messageUrl = $db->generateBase62Id('messages', 'message_url', 8);

        // Create message
        $messageId = $db->insert('messages', [
            'sender_id' => $senderId,
            'recipient_name' => $recipientName,
            'recipient_email' => $recipientEmail,
            'message' => $message,
            'message_url' => $messageUrl,
            'sent_at' => date('Y-m-d H:i:s')
        ]);

        // Send email to recipient
        self::sendMessageEmail($senderId, $recipientName, $recipientEmail, $messageUrl);

        // Update sender's last activity
        $db->update('senders', [
            'last_activity' => date('Y-m-d H:i:s')
        ], 'id = ?', [$senderId]);

        return [
            'id' => $messageId,
            'message_url' => $messageUrl
        ];
    }

    public static function getMessageByUrl(string $messageUrl): ?array
    {
        $db = Database::getInstance();

        $message = $db->fetchOne('
            SELECT
                m.*,
                s.name as sender_name,
                s.avatar as sender_avatar
            FROM messages m
            JOIN senders s ON m.sender_id = s.id
            WHERE m.message_url = ?
        ', [$messageUrl]);

        return $message ?: null;
    }

    public static function getMessagesBySender(int $senderId): array
    {
        $db = Database::getInstance();

        return $db->fetchAll('
            SELECT *
            FROM messages
            WHERE sender_id = ?
            ORDER BY created_at DESC
        ', [$senderId]);
    }

    public static function getSenderMessageCount(int $senderId): int
    {
        $db = Database::getInstance();

        $result = $db->fetchOne(
            'SELECT COUNT(*) as count FROM messages WHERE sender_id = ?',
            [$senderId]
        );

        return (int) $result['count'];
    }

    public static function getOtherMessagesBySender(int $senderId, string $recipientEmail): array
    {
        $db = Database::getInstance();

        return $db->fetchAll('
            SELECT *
            FROM messages
            WHERE sender_id = ? AND recipient_email = ?
            ORDER BY sent_at DESC
        ', [$senderId, $recipientEmail]);
    }

    public static function recordView(string $messageUrl): bool
    {
        $db = Database::getInstance();

        $message = $db->fetchOne('SELECT id, viewed_at FROM messages WHERE message_url = ?', [$messageUrl]);

        if (!$message) {
            return false;
        }

        // If already viewed, don't update
        if ($message['viewed_at']) {
            return true;
        }

        // Record first view
        $db->update('messages', [
            'viewed_at' => date('Y-m-d H:i:s')
        ], 'message_url = ?', [$messageUrl]);

        return true;
    }

    public static function markAsSmiled(string $messageUrl): bool
    {
        $db = Database::getInstance();

        $message = $db->fetchOne('SELECT id, smiled_at FROM messages WHERE message_url = ?', [$messageUrl]);

        if (!$message) {
            return false;
        }

        // If already smiled, don't update
        if ($message['smiled_at']) {
            return true;
        }

        // Mark as smiled
        $db->update('messages', [
            'smiled_at' => date('Y-m-d H:i:s')
        ], 'message_url = ?', [$messageUrl]);

        // Increment global smile count
        StatsService::incrementSmileCount();

        return true;
    }

    public static function deleteMessage(int $messageId, int $senderId): bool
    {
        $db = Database::getInstance();

        // Verify ownership
        $message = $db->fetchOne('SELECT id FROM messages WHERE id = ? AND sender_id = ?', [$messageId, $senderId]);
        if (!$message) {
            return false;
        }

        $db->delete('messages', 'id = ? AND sender_id = ?', [$messageId, $senderId]);
        return true;
    }

    private static function sendMessageEmail(int $senderId, string $recipientName, string $recipientEmail, string $messageUrl): void
    {
        $db = Database::getInstance();

        $sender = $db->fetchOne('SELECT name FROM senders WHERE id = ?', [$senderId]);
        if (!$sender) {
            return;
        }

        $subject = "{$sender['name']} wants to make you smile";

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $messageLink = "{$protocol}://{$host}/s/{$messageUrl}";

        $htmlMessage = \App\Services\EmailService::generateSmileNotificationEmailHtml(
            $recipientName,
            $sender['name'],
            $messageLink
        );

        \App\Services\EmailService::sendHtmlEmail($recipientEmail, $subject, $htmlMessage);

        // Track that we sent this email
        $db->insert('email_notifications', [
            'sender_id' => $senderId,
            'recipient_email' => $recipientEmail,
            'notification_type' => 'message'
        ]);
    }
}
