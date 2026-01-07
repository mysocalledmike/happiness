<?php

namespace App\Services;

use App\Database;

class SignupService
{
    public static function createUser(string $name, string $email, string $avatar): string
    {
        $db = Database::getInstance();

        // Check if email already exists
        $existing = $db->fetchOne('SELECT id, dashboard_url FROM senders WHERE email = ?', [$email]);
        if ($existing) {
            // Return existing dashboard URL
            return $existing['dashboard_url'];
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

        // Send confirmation email
        self::sendConfirmationEmail($email, $confirmationToken);

        // Send dashboard access email
        self::sendDashboardEmail($name, $email, $dashboardUrl);

        return $dashboardUrl;
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

    private static function sendConfirmationEmail(string $email, string $token): void
    {
        $subject = 'Confirm your email for One Trillion Smiles';

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $confirmUrl = "{$protocol}://{$host}/confirm/{$token}";

        $message = "
Welcome to One Trillion Smiles!

Please confirm your email address by clicking the link below:
{$confirmUrl}

Why confirm? It helps us verify it's really you and lets you send unlimited Smiles.

Thanks for spreading happiness!
        ";

        \App\Services\EmailService::sendEmail($email, $subject, $message);
    }

    private static function sendDashboardEmail(string $name, string $email, string $dashboardUrl): void
    {
        $subject = 'Your Smile Dashboard is Ready!';

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $dashboardLink = "{$protocol}://{$host}/dashboard/{$dashboardUrl}";

        $message = "
Hey {$name}!

Your Smile dashboard is ready. Start spreading smiles:
{$dashboardLink}

This link is private and unique to you - bookmark it to get back anytime!

Happy creating!
        ";

        \App\Services\EmailService::sendEmail($email, $subject, $message);
    }
}
