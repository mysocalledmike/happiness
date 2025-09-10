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
        
        // Get existing messages
        $messages = $db->fetchAll('
            SELECT recipient_email, recipient_name, message, emotion 
            FROM messages 
            WHERE sender_id = ? 
            ORDER BY id ASC
        ', [$sender['id']]);
        
        $sender['messages'] = $messages;
        return $sender;
    }

    public static function getSenderBySlug(string $slug): array
    {
        $db = Database::getInstance();
        $sender = $db->fetchOne('SELECT * FROM senders WHERE slug = ?', [$slug]);
        
        if (!$sender || $sender['status'] !== 'active') {
            throw new \Exception('Goodbye page not found');
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
}