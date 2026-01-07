<?php

namespace App\Services;

use App\Database;

class AdminService
{
    public static function getAllUsers(): array
    {
        $db = Database::getInstance();

        $users = $db->fetchAll('
            SELECT
                s.*,
                COUNT(m.id) as message_count,
                COUNT(CASE WHEN m.read_at IS NOT NULL THEN 1 END) as smiles_created
            FROM senders s
            LEFT JOIN messages m ON s.id = m.sender_id
            GROUP BY s.id
            ORDER BY s.created_at DESC
        ');

        return $users;
    }

    public static function sendReminder(string $email): void
    {
        $db = Database::getInstance();

        $sender = $db->fetchOne('SELECT * FROM senders WHERE email = ?', [$email]);
        if (!$sender) {
            throw new \Exception('User not found');
        }

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $dashboardLink = "{$protocol}://{$host}/dashboard/{$sender['dashboard_url']}";

        $subject = 'Reminder: Spread some smiles today!';
        $message = "
Hey {$sender['name']}!

Just a friendly reminder that your Smile dashboard is waiting for you:
{$dashboardLink}

Have you sent any smiles lately? It only takes 2 minutes to make someone's day better.

Keep spreading happiness!
        ";

        \App\Services\EmailService::sendEmail($email, $subject, $message);
    }

    public static function resetCreationPage(string $email): void
    {
        $db = Database::getInstance();

        $sender = $db->fetchOne('SELECT id, name FROM senders WHERE email = ?', [$email]);
        if (!$sender) {
            throw new \Exception('User not found');
        }

        // Generate new dashboard URL
        $newDashboardUrl = $db->generateUniqueId('senders', 'dashboard_url', 32);

        $db->update('senders', [
            'dashboard_url' => $newDashboardUrl
        ], 'email = ?', [$email]);

        // Send email with new link
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $dashboardLink = "{$protocol}://{$host}/dashboard/{$newDashboardUrl}";

        $subject = 'Your New Smile Dashboard Link';
        $message = "
Hey {$sender['name']}!

We've generated a new dashboard link for you:
{$dashboardLink}

Your old link will no longer work. Please bookmark this new one!

Keep spreading smiles!
        ";

        \App\Services\EmailService::sendEmail($email, $subject, $message);
    }

    public static function deleteUser(string $email): void
    {
        $db = Database::getInstance();

        // Delete from senders table (cascading will handle messages and notifications)
        $db->delete('senders', 'email = ?', [$email]);
    }
}
