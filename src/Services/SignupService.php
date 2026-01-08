<?php

namespace App\Services;

use App\Database;

class SignupService
{
    public static function createUser(string $name, string $email, string $avatar): array
    {
        $db = Database::getInstance();

        // Check if email already exists
        $existing = $db->fetchOne('SELECT id, name, dashboard_url FROM senders WHERE email = ?', [$email]);
        if ($existing) {
            // Send dashboard access email
            self::sendDashboardAccessEmail($existing['name'], $email, $existing['dashboard_url']);
            return [
                'existing' => true,
                'dashboard_url' => $existing['dashboard_url']
            ];
        }

        // Generate unique tokens
        $dashboardUrl = $db->generateUniqueId('senders', 'dashboard_url', 32);
        $confirmationToken = $db->generateUniqueId('senders', 'email_confirmation_token', 32);

        // Create sender
        $db->insert('senders', [
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
            'email_confirmed' => 0,
            'email_confirmation_token' => $confirmationToken,
            'dashboard_url' => $dashboardUrl,
            'last_activity' => date('Y-m-d H:i:s')
        ]);

        // Send combined confirmation and dashboard email
        self::sendConfirmationEmail($name, $email, $confirmationToken, $dashboardUrl);

        return [
            'existing' => false,
            'dashboard_url' => $dashboardUrl
        ];
    }

    public static function confirmEmail(string $token): bool
    {
        $db = Database::getInstance();

        $sender = $db->fetchOne(
            'SELECT id, email_confirmed FROM senders WHERE email_confirmation_token = ?',
            [$token]
        );

        if (!$sender) {
            return false;
        }

        if ($sender['email_confirmed']) {
            // Already confirmed
            return true;
        }

        // Mark as confirmed
        $db->update('senders', [
            'email_confirmed' => 1
        ], 'email_confirmation_token = ?', [$token]);

        return true;
    }

    private static function sendConfirmationEmail(string $name, string $email, string $token, string $dashboardUrl): void
    {
        $subject = 'Welcome to One Trillion Smiles!';

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $dashboardLink = "{$protocol}://{$host}/dashboard/{$dashboardUrl}";

        $message = "
Hey {$name}!

Welcome to One Trillion Smiles! Your Smile dashboard is ready.

Here's your dashboard link:
{$dashboardLink}

Bookmark this link to get back anytime!

Happy creating!
        ";

        \App\Services\EmailService::sendEmail($email, $subject, $message);
    }

    public static function sendConfirmationOnlyEmail(string $name, string $email, string $token, string $dashboardUrl): void
    {
        $subject = 'Confirm your email for One Trillion Smiles';

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $confirmUrl = "{$protocol}://{$host}/confirm/{$token}";

        $message = "
Hey {$name}!

Please confirm your email address to unlock unlimited Smiles:
{$confirmUrl}

This will verify it's really you and let you keep spreading smiles!

Thanks!
        ";

        \App\Services\EmailService::sendEmail($email, $subject, $message);
    }

    public static function sendDashboardAccessEmail(string $name, string $email, string $dashboardUrl): void
    {
        $subject = 'Your One Trillion Smiles Dashboard';

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $dashboardLink = "{$protocol}://{$host}/dashboard/{$dashboardUrl}";

        $message = "
Hey {$name}!

Someone tried to create an account with your email, but you're already spreading smiles!

Here's your dashboard link:
{$dashboardLink}

Bookmark this link to get back anytime!

Happy creating!
        ";

        \App\Services\EmailService::sendEmail($email, $subject, $message);
    }
}
