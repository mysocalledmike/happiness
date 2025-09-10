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
            case 'waitlist':
                $startTime = new \DateTime($user['created_at']);
                break;
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
        
        // Update user status
        $db->update('senders', [
            'status' => 'inactive',
            'creation_url' => $creationUrl,
            'activated_at' => date('Y-m-d H:i:s'),
            'last_activity' => date('Y-m-d H:i:s')
        ], 'email = ?', [$email]);
        
        // Remove from waitlist
        $db->delete('waitlist', 'email = ?', [$email]);
        
        // Send email notification
        self::sendCreationEmail($email, $creationUrl);
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
        
        // Delete from all tables (cascading will handle messages)
        $db->delete('senders', 'email = ?', [$email]);
        $db->delete('waitlist', 'email = ?', [$email]);
    }

    private static function sendCreationEmail(string $email, string $creationUrl): void
    {
        $subject = 'Create Your Goodbye Page - You\'re off the waitlist!';
        
        // Use the current host for the URL (works in both dev and production)
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $createUrl = "{$protocol}://{$host}/create/{$creationUrl}";
        
        $message = "
Great news! You've been removed from the waitlist and can now create your goodbye page.

Click here to get started:
{$createUrl}

Remember, this is your chance to bring smiles and happiness to people you'll miss!

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
        $subject = 'Don\'t forget to create your goodbye page!';
        
        // Use the current host for the URL (works in both dev and production)
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $createUrl = "{$protocol}://{$host}/create/{$creationUrl}";
        
        $message = "
Hi there!

Just a friendly reminder that you can create your goodbye page to bring smiles and happiness to people you'll miss.

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

You can use this link to continue editing your goodbye page.

This link is private and unique to you - don't share it with others.

Happy creating!
        ";
        
        $result = \App\Services\EmailService::sendEmail($email, $subject, $message);
        
        if (!$result) {
            throw new \Exception('Failed to send reset creation email. Please check mail configuration.');
        }
    }
}