<?php

namespace App\Services;

class EmailService
{
    private static $isDevelopment = null;
    
    private static function isDevelopment(): bool
    {
        if (self::$isDevelopment === null) {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            self::$isDevelopment = strpos($host, 'localhost') !== false || 
                                 strpos($host, '127.0.0.1') !== false ||
                                 strpos($host, 'dreamhost') !== false ||
                                 strpos($host, 'happiness.mikesorvillo.com') !== false;
        }
        return self::$isDevelopment;
    }
    
    public static function sendEmail(string $to, string $subject, string $message, string $from = null): bool
    {
        // Use configured email sender if not specified
        if ($from === null) {
            $from = \App\Config::getEmailSender();
        }
        
        $headers = 'From: ' . $from . "\r\n" .
                   'Reply-To: ' . $from . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();
        
        if (self::isDevelopment()) {
            // In development, log the email instead of sending
            self::logEmailForDevelopment($to, $subject, $message, $from);
            return true;
        } else {
            // In production, send the actual email
            $result = mail($to, $subject, $message, $headers);
            
            // Log the result
            error_log("Email to {$to}: " . ($result ? 'SUCCESS' : 'FAILED'));
            
            return $result;
        }
    }
    
    private static function logEmailForDevelopment(string $to, string $subject, string $message, string $from): void
    {
        $logFile = __DIR__ . '/../../development_emails.log';
        
        $timestamp = date('Y-m-d H:i:s');
        $emailLog = "\n" . str_repeat('=', 60) . "\n";
        $emailLog .= "📧 EMAIL SENT AT: {$timestamp}\n";
        $emailLog .= "To: {$to}\n";
        $emailLog .= "From: {$from}\n";
        $emailLog .= "Subject: {$subject}\n";
        $emailLog .= "Message:\n{$message}\n";
        $emailLog .= str_repeat('=', 60) . "\n";
        
        file_put_contents($logFile, $emailLog, FILE_APPEND | LOCK_EX);
        
        // Only echo if we're running from command line, not via web request
        if (php_sapi_name() === 'cli') {
            echo "📧 Development Email Logged: {$subject} to {$to}\n";
            echo "📝 Check development_emails.log to see the full email content\n";
        }
    }
    
    public static function getRecentDevelopmentEmails(int $limit = 10): array
    {
        $logFile = __DIR__ . '/../../development_emails.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $content = file_get_contents($logFile);
        $emails = explode(str_repeat('=', 60), $content);
        
        // Remove empty entries and get the most recent ones
        $emails = array_filter($emails, 'trim');
        $emails = array_slice($emails, -$limit);
        
        return array_reverse($emails);
    }
    
    public static function clearDevelopmentEmails(): bool
    {
        $logFile = __DIR__ . '/../../development_emails.log';
        
        if (!file_exists($logFile)) {
            return true;
        }
        
        return file_put_contents($logFile, '') !== false;
    }
}