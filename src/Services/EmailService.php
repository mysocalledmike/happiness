<?php

namespace App\Services;

use Resend;

class EmailService
{
    private static $isDevelopment = null;

    private static function isDevelopment(): bool
    {
        if (self::$isDevelopment === null) {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            self::$isDevelopment = strpos($host, 'localhost') !== false ||
                                 strpos($host, '127.0.0.1') !== false;
        }
        return self::$isDevelopment;
    }

    private static function getResendApiKey(): ?string
    {
        // Try to get from environment variable first
        $apiKey = getenv('RESEND_API_KEY');

        // Fall back to config file if environment variable not set
        if (!$apiKey && file_exists(__DIR__ . '/../../config/resend.php')) {
            $config = require __DIR__ . '/../../config/resend.php';
            $apiKey = $config['api_key'] ?? null;
        }

        return $apiKey;
    }

    public static function sendEmail(string $to, string $subject, string $message, ?string $from = null): bool
    {
        // Use configured email sender if not specified
        if ($from === null) {
            $from = \App\Config::getEmailSender();
        }

        if (self::isDevelopment()) {
            // In development, log the email instead of sending
            self::logEmailForDevelopment($to, $subject, $message, $from);
            return true;
        }

        // In production, use Resend
        try {
            $apiKey = self::getResendApiKey();

            if (!$apiKey) {
                error_log("Resend API key not configured. Falling back to PHP mail()");
                return self::sendWithPhpMail($to, $subject, $message, $from);
            }

            $resend = Resend::client($apiKey);

            $result = $resend->emails->send([
                'from' => $from,
                'to' => [$to],
                'subject' => $subject,
                'text' => $message,
            ]);

            // Log success
            error_log("Email sent via Resend to {$to}: " . ($result->id ?? 'SUCCESS'));

            return true;

        } catch (\Exception $e) {
            // Log the error
            error_log("Resend error: " . $e->getMessage());

            // Fall back to PHP mail as last resort
            error_log("Falling back to PHP mail() due to Resend error");
            return self::sendWithPhpMail($to, $subject, $message, $from);
        }
    }

    /**
     * Fallback method using PHP's mail() function
     */
    private static function sendWithPhpMail(string $to, string $subject, string $message, string $from): bool
    {
        $headers = 'From: ' . $from . "\r\n" .
                   'Reply-To: ' . $from . "\r\n" .
                   'Return-Path: ' . $from . "\r\n" .
                   'MIME-Version: 1.0' . "\r\n" .
                   'Content-Type: text/plain; charset=UTF-8' . "\r\n" .
                   'Content-Transfer-Encoding: 8bit' . "\r\n" .
                   'X-Mailer: Happiness Platform' . "\r\n" .
                   'X-Priority: 3' . "\r\n";

        $result = mail($to, $subject, $message, $headers);

        // Log the result
        error_log("Email to {$to} (PHP mail): " . ($result ? 'SUCCESS' : 'FAILED'));

        return $result;
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
