<?php

namespace App\Services;

use App\Database;

class AdminService
{
    public static function getAllUsers(): array
    {
        $db = Database::getInstance();
        
        $users = $db->fetchAll('
            SELECT email, status, slug, creation_url, created_at, activated_at, last_activity 
            FROM senders 
            ORDER BY email ASC
        ');
        
        // Calculate time in current state
        foreach ($users as &$user) {
            $user['time_in_state'] = self::calculateTimeInState($user);
        }
        
        return $users;
    }

    private static function calculateTimeInState(array $user): string
    {
        $now = new \DateTime();
        
        switch ($user['status']) {
            case 'inactive':
                $startTime = $user['activated_at'] ? new \DateTime($user['activated_at']) : new \DateTime($user['created_at']);
                break;
            case 'active':
                $startTime = $user['activated_at'] ? new \DateTime($user['activated_at']) : new \DateTime($user['created_at']);
                break;
            default:
                $startTime = new \DateTime($user['created_at']);
        }
        
        $diff = $now->diff($startTime);
        
        if ($diff->days > 0) {
            return "For {$diff->days} day" . ($diff->days > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return "For {$diff->h} hour" . ($diff->h > 1 ? 's' : '');
        } else {
            return "For {$diff->i} minute" . ($diff->i > 1 ? 's' : '');
        }
    }

    public static function allowUser(string $email): void
    {
        $db = Database::getInstance();

        // Generate unique creation URL
        $creationUrl = $db->generateUniqueId('senders', 'creation_url', 32);

        // Generate smart defaults
        $defaults = self::generateSmartDefaults($email);

        // Update user status with smart defaults
        $db->update('senders', [
            'status' => 'active', // Immediately active with defaults
            'creation_url' => $creationUrl,
            'slug' => $defaults['slug'],
            'overall_message' => $defaults['overall_message'],
            'theme' => $defaults['theme'],
            'not_found_message' => $defaults['not_found_message'],
            'activated_at' => date('Y-m-d H:i:s'),
            'last_activity' => date('Y-m-d H:i:s')
        ], 'email = ?', [$email]);

        // Send email notification
        self::sendCreationEmail($email, $creationUrl);
    }

    private static function generateSmartDefaults(string $email): array
    {
        $db = Database::getInstance();

        // Generate slug from email prefix
        $emailPrefix = explode('@', $email)[0];
        $baseSlug = preg_replace('/[^a-zA-Z0-9]/', '', $emailPrefix);
        $baseSlug = strtolower($baseSlug);

        // Ensure slug is unique
        $slug = $baseSlug;
        $counter = 1;
        while (true) {
            $existing = $db->fetchOne('SELECT 1 FROM senders WHERE slug = ?', [$slug]);
            if (!$existing) {
                break;
            }
            $slug = $baseSlug . $counter;
            $counter++;
        }

        // Random theme
        $themes = ['rosie', 'hooty', 'bruno', 'whiskers', 'penny', 'lily', 'buzzy', 'panda'];
        $randomTheme = $themes[array_rand($themes)];

        return [
            'slug' => $slug,
            'overall_message' => 'You make me happy',
            'theme' => $randomTheme,
            'not_found_message' => "I don't think we've crossed paths, but thanks for checking out my happiness page! Feel free to create your own."
        ];
    }

    public static function sendReminder(string $email): void
    {
        $db = Database::getInstance();
        $user = $db->fetchOne('SELECT creation_url FROM senders WHERE email = ?', [$email]);
        
        if (!$user || !$user['creation_url']) {
            throw new \Exception('User not found or not eligible for reminder');
        }
        
        self::sendReminderEmail($email, $user['creation_url']);
    }

    public static function resetCreationPage(string $email): void
    {
        $db = Database::getInstance();
        
        // Generate new creation URL
        $creationUrl = $db->generateUniqueId('senders', 'creation_url', 32);
        
        // Update creation URL but keep existing data
        $db->update('senders', [
            'creation_url' => $creationUrl,
            'last_activity' => date('Y-m-d H:i:s')
        ], 'email = ?', [$email]);
        
        // Send new creation link
        self::sendResetCreationEmail($email, $creationUrl);
    }

    public static function deleteUser(string $email): void
    {
        $db = Database::getInstance();

        // Delete from senders table (cascading will handle messages and notifications)
        $db->delete('senders', 'email = ?', [$email]);
    }

    private static function sendCreationEmail(string $email, string $creationUrl): void
    {
        $subject = 'Create Your Happiness Page!';

        // Use the current host for the URL (works in both dev and production)
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $createUrl = "{$protocol}://{$host}/create/{$creationUrl}";

        $message = "
Welcome to Happiness!

You're all set to create your happiness page and spread joy to your colleagues, classmates, and friends.

Click here to get started:
{$createUrl}

This link is private and unique to you - don't share it with others.

Happy creating!
        ";
        
        $result = \App\Services\EmailService::sendEmail($email, $subject, $message);
        
        if (!$result) {
            throw new \Exception('Failed to send email. Please check mail configuration.');
        }
    }

    private static function sendReminderEmail(string $email, string $creationUrl): void
    {
        $subject = 'Don\'t forget to create your happiness page!';
        
        // Use the current host for the URL (works in both dev and production)
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $createUrl = "{$protocol}://{$host}/create/{$creationUrl}";
        
        $message = "
Hi there!

Just a friendly reminder that you can create your happiness page to bring smiles and happiness to your colleagues, classmates, and friends.

Your creation link is still active:
{$createUrl}

Take a few minutes to spread some joy!

Cheers!
        ";
        
        $result = \App\Services\EmailService::sendEmail($email, $subject, $message);
        
        if (!$result) {
            throw new \Exception('Failed to send reminder email. Please check mail configuration.');
        }
    }

    private static function sendResetCreationEmail(string $email, string $creationUrl): void
    {
        $subject = 'Here is your new Creation URL';
        
        // Use the current host for the URL (works in both dev and production)
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $createUrl = "{$protocol}://{$host}/create/{$creationUrl}";
        
        $message = "
Hi there!

Your creation URL has been reset. Here is your new creation link:
{$createUrl}

You can use this link to continue editing your happiness page.

This link is private and unique to you - don't share it with others.

Happy creating!
        ";
        
        $result = \App\Services\EmailService::sendEmail($email, $subject, $message);
        
        if (!$result) {
            throw new \Exception('Failed to send reset creation email. Please check mail configuration.');
        }
    }
}