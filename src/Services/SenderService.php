<?php

namespace App\Services;

use App\Database;

class SenderService
{
    public static function getSenderByCreationUrl(string $creationUrl): array
    {
        $db = Database::getInstance();
        $sender = $db->fetchOne('SELECT * FROM senders WHERE creation_url = ?', [$creationUrl]);
        
        if (!$sender) {
            throw new \Exception('Invalid creation URL');
        }
        
        // Get existing messages with sent status
        $messages = $db->fetchAll('
            SELECT m.recipient_email, m.recipient_name, m.message, m.emotion,
                   CASE WHEN en.id IS NOT NULL THEN 1 ELSE 0 END as is_sent
            FROM messages m
            LEFT JOIN email_notifications en ON en.sender_id = m.sender_id AND en.recipient_email = m.recipient_email
            WHERE m.sender_id = ?
            ORDER BY m.id ASC
        ', [$sender['id']]);

        $sender['messages'] = $messages;
        return $sender;
    }

    public static function getSenderBySlug(string $slug): array
    {
        $db = Database::getInstance();
        $sender = $db->fetchOne('SELECT * FROM senders WHERE slug = ?', [$slug]);
        
        if (!$sender || $sender['status'] !== 'active') {
            throw new \Exception('Happiness page not found');
        }
        
        // Add theme color if theme is set
        if ($sender['theme']) {
            $theme = \App\Services\ThemeService::getThemeById($sender['theme']);
            if ($theme) {
                $sender['theme_color'] = $theme['background_color'];
            }
        }
        
        return $sender;
    }

    public static function saveCreationData(string $creationUrl, array $data): void
    {
        $db = Database::getInstance();
        $sender = $db->fetchOne('SELECT id FROM senders WHERE creation_url = ?', [$creationUrl]);
        
        if (!$sender) {
            throw new \Exception('Invalid creation URL');
        }
        
        $senderId = $sender['id'];
        
        // Update sender settings if provided
        if (isset($data['settings'])) {
            $settings = $data['settings'];
            $updateData = ['last_activity' => date('Y-m-d H:i:s')];
            
            if (isset($settings['slug'])) {
                // Validate slug
                $slug = trim($settings['slug']);
                if (!preg_match('/^[a-zA-Z0-9-]+$/', $slug)) {
                    throw new \Exception('Slug can only contain letters, numbers, and hyphens');
                }
                
                // Check if slug is available
                $existing = $db->fetchOne('SELECT 1 FROM senders WHERE slug = ? AND id != ?', [$slug, $senderId]);
                if ($existing) {
                    throw new \Exception('This slug is already taken');
                }
                
                $updateData['slug'] = $slug;
            }
            
            if (isset($settings['overall_message'])) {
                $updateData['overall_message'] = trim($settings['overall_message']);
            }
            
            if (isset($settings['theme'])) {
                $updateData['theme'] = $settings['theme'];
            }
            
            if (isset($settings['not_found_message'])) {
                $updateData['not_found_message'] = trim($settings['not_found_message']);
            }
            
            // Update status to active if settings are being saved
            $updateData['status'] = 'active';
            
            $db->update('senders', $updateData, 'id = ?', [$senderId]);
        }
        
        // Save messages if provided
        if (isset($data['messages']) && is_array($data['messages'])) {
            foreach ($data['messages'] as $messageData) {
                if (empty($messageData['recipient_email'])) {
                    continue;
                }
                
                $email = trim($messageData['recipient_email']);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                
                // Insert or update message
                $existing = $db->fetchOne('
                    SELECT id FROM messages 
                    WHERE sender_id = ? AND recipient_email = ?
                ', [$senderId, $email]);
                
                $messageRecord = [
                    'recipient_name' => trim($messageData['recipient_name'] ?? ''),
                    'message' => trim($messageData['message'] ?? ''),
                    'emotion' => $messageData['emotion'] ?? 'happy'
                ];
                
                if ($existing) {
                    $db->update('messages', $messageRecord, 'id = ?', [$existing['id']]);
                } else {
                    $messageRecord['sender_id'] = $senderId;
                    $messageRecord['recipient_email'] = $email;
                    $db->insert('messages', $messageRecord);
                }
            }
        }
    }

    public static function publishPage(string $creationUrl): array
    {
        $db = Database::getInstance();
        $sender = $db->fetchOne('SELECT * FROM senders WHERE creation_url = ?', [$creationUrl]);

        if (!$sender || $sender['status'] !== 'active') {
            throw new \Exception('Page not ready for publishing');
        }

        // Get all messages that haven't been sent yet
        $messages = $db->fetchAll('
            SELECT m.recipient_email, m.recipient_name
            FROM messages m
            LEFT JOIN email_notifications en ON en.sender_id = m.sender_id AND en.recipient_email = m.recipient_email
            WHERE m.sender_id = ? AND en.id IS NULL AND m.message IS NOT NULL AND m.message != ""
        ', [$sender['id']]);

        $emailsSent = 0;
        $maxEmails = 20; // Recipient limit

        if (count($messages) > $maxEmails) {
            throw new \Exception("Too many recipients. Maximum {$maxEmails} emails allowed.");
        }

        foreach ($messages as $message) {
            try {
                self::sendHappinessEmail($sender, $message['recipient_email'], $message['recipient_name']);

                // Mark as sent
                $db->insert('email_notifications', [
                    'sender_id' => $sender['id'],
                    'recipient_email' => $message['recipient_email']
                ]);

                $emailsSent++;
            } catch (\Exception $e) {
                // Continue sending other emails even if one fails
                error_log("Failed to send email to {$message['recipient_email']}: " . $e->getMessage());
            }
        }

        return ['emails_sent' => $emailsSent];
    }

    private static function sendHappinessEmail(array $sender, string $recipientEmail, string $recipientName): void
    {
        $subject = $sender['overall_message'] ? "You have a happiness message! - {$sender['overall_message']}" : "You have a happiness message!";

        // Use the current host for the URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $pageUrl = "{$protocol}://{$host}/{$sender['slug']}?email=" . urlencode($recipientEmail) . "&first=1";

        $message = "
Hi " . ($recipientName ?: 'there') . "!

You just received a happiness message! Someone wants to spread a little joy and positivity your way.

Click here to see your personalized message:
{$pageUrl}

This message was created just for you. Take a moment to smile! 😊

Want to create your own happiness page? Visit our homepage to get started.
        ";

        $result = \App\Services\EmailService::sendEmail($recipientEmail, $subject, $message);

        if (!$result) {
            throw new \Exception('Failed to send email to ' . $recipientEmail);
        }
    }

    public static function incrementPageSmileCount(int $senderId): void
    {
        $db = Database::getInstance();
        $db->query('UPDATE senders SET smile_count = smile_count + 1 WHERE id = ?', [$senderId]);
    }
}