<?php

namespace App\Services;

use App\Database;

class SignupService
{
    public static function createPage(string $email): string
    {
        $db = Database::getInstance();

        // Check if already exists
        $existing = $db->fetchOne('SELECT 1 FROM senders WHERE email = ?', [$email]);
        if ($existing) {
            throw new \Exception('Email already has a page. Check your email for your creation link!');
        }

        // Generate unique creation URL
        $creationUrl = $db->generateUniqueId('senders', 'creation_url', 32);

        // Generate smart defaults
        $defaults = self::generateSmartDefaults($email);

        // Create user with inactive status (they haven't created messages yet)
        $db->insert('senders', [
            'email' => $email,
            'status' => 'inactive',
            'creation_url' => $creationUrl,
            'slug' => $defaults['slug'],
            'overall_message' => $defaults['overall_message'],
            'theme' => $defaults['theme'],
            'not_found_message' => $defaults['not_found_message'],
            'activated_at' => date('Y-m-d H:i:s'),
            'last_activity' => date('Y-m-d H:i:s')
        ]);

        // Send creation email
        self::sendCreationEmail($email, $creationUrl);

        return $creationUrl;
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
}