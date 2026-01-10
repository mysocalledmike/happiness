<?php

namespace App;

class Config
{
    // Domain configuration - change this once to update everywhere
    public static function getDomain(): string
    {
        return 'onetrillionsmiles.com';
    }

    public static function getEmailDomain(): string
    {
        return 'mail.onetrillionsmiles.com';
    }
    
    public static function getEmailSender(): string
    {
        return 'noreply@' . self::getEmailDomain();
    }
    
    public static function getBaseUrl(): string
    {
        // Automatically detect protocol
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        return $protocol . '://' . self::getDomain();
    }

    public static function getFullUrl(string $path = ''): string
    {
        $baseUrl = self::getBaseUrl();
        $path = ltrim($path, '/');
        return $path ? $baseUrl . '/' . $path : $baseUrl;
    }
    
    // Application settings
    public static function getAppName(): string
    {
        return 'One Trillion Smiles';
    }

    public static function getAppDescription(): string
    {
        return 'Bring a smile to 1 billion faces';
    }
}