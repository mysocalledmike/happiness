<?php

namespace App\Services;

use App\Database;

class WaitlistService
{
    public static function addToWaitlist(string $email): void
    {
        $db = Database::getInstance();
        
        // Check if already exists
        $existing = $db->fetchOne('SELECT 1 FROM senders WHERE email = ?', [$email]);
        if ($existing) {
            throw new \Exception('Email already registered');
        }
        
        $existingWaitlist = $db->fetchOne('SELECT 1 FROM waitlist WHERE email = ?', [$email]);
        if ($existingWaitlist) {
            throw new \Exception('Email already on waitlist');
        }
        
        // Add to waitlist
        $db->insert('waitlist', ['email' => $email]);
        
        // Also add to senders table with waitlist status
        $db->insert('senders', [
            'email' => $email,
            'status' => 'waitlist'
        ]);
    }
}