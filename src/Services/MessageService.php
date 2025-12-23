<?php

namespace App\Services;

use App\Database;

class MessageService
{
    public static function lookupMessage(string $slug, string $email): ?array
    {
        $db = Database::getInstance();
        
        // First get the sender by slug
        $sender = $db->fetchOne('SELECT id FROM senders WHERE slug = ? AND status = "active"', [$slug]);
        
        if (!$sender) {
            throw new \Exception('Happiness page not found');
        }
        
        // Look up the message
        $message = $db->fetchOne('
            SELECT recipient_name, message, emotion 
            FROM messages 
            WHERE sender_id = ? AND recipient_email = ?
        ', [$sender['id'], $email]);
        
        return $message;
    }
}