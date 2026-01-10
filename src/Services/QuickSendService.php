<?php

namespace App\Services;

use App\Database;
use App\Config;

class QuickSendService
{
    /**
     * Available avatars for random selection
     */
    private static $avatars = ['😊', '😄', '🎉', '✨', '💛', '🌟', '🚀', '🎈', '🌈', '⭐'];

    /**
     * Pick a random avatar
     */
    private static function getRandomAvatar(): string
    {
        return self::$avatars[array_rand(self::$avatars)];
    }

    /**
     * Quick send: Create account (if needed) and send message in one step
     *
     * This is used when someone views a message and wants to send one back
     * We auto-create their account with a random avatar and send their message
     *
     * @param string $senderName The person sending the new message
     * @param string $senderEmail The person's email
     * @param string $recipientName The person receiving the message
     * @param string $recipientEmail The recipient's email
     * @param string $message The message content
     * @return array ['dashboard_url' => string, 'existing_user' => bool]
     */
    public static function quickSend(
        string $senderName,
        string $senderEmail,
        string $recipientName,
        string $recipientEmail,
        string $message
    ): array {
        $db = Database::getInstance();

        // Check if sender already exists
        $existingSender = $db->fetchOne(
            'SELECT id, dashboard_url, email_confirmed FROM senders WHERE email = ?',
            [$senderEmail]
        );

        if ($existingSender) {
            // User exists - just send the message
            $senderId = $existingSender['id'];
            $dashboardUrl = $existingSender['dashboard_url'];
            $existingUser = true;
        } else {
            // Create new sender account with random avatar
            $avatar = self::getRandomAvatar();
            $dashboardUrl = bin2hex(random_bytes(16)); // 32 character URL
            $emailConfirmationToken = bin2hex(random_bytes(16));

            $db->execute(
                'INSERT INTO senders (name, email, avatar, dashboard_url, email_confirmed, email_confirmation_token, created_at, last_activity)
                 VALUES (?, ?, ?, ?, 0, ?, datetime("now"), datetime("now"))',
                [$senderName, $senderEmail, $avatar, $dashboardUrl, $emailConfirmationToken]
            );

            $senderId = $db->lastInsertId();
            $existingUser = false;

            // Send welcome email with dashboard link
            self::sendQuickSendWelcomeEmail($senderName, $senderEmail, $dashboardUrl);
        }

        // Send the message using MessageService
        MessageService::createMessage($senderId, $recipientName, $recipientEmail, $message);

        return [
            'dashboard_url' => $dashboardUrl,
            'existing_user' => $existingUser
        ];
    }

    /**
     * Send welcome email for quick send users
     */
    private static function sendQuickSendWelcomeEmail(string $name, string $email, string $dashboardUrl): void
    {
        $baseUrl = Config::getBaseUrl();
        $dashboardLink = "{$baseUrl}/dashboard/{$dashboardUrl}";

        $subject = "You just sent a smile!";
        $message = "
Hey {$name}!

You just sent a smile to someone - nice work! 🎉

Here's your personal dashboard where you can send more smiles and track your impact:
{$dashboardLink}

You can send up to 3 smiles. After that, just confirm your email to keep spreading happiness!

Keep spreading smiles,
The One Trillion Smiles Team
";

        EmailService::sendEmail($email, $subject, $message);
    }
}
