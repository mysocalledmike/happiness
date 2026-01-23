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
            $senderName = \App\Config::getEmailSenderName();
            $senderEmail = \App\Config::getEmailSender();
            $from = "{$senderName} <{$senderEmail}>";
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

    public static function sendHtmlEmail(string $to, string $subject, string $htmlMessage, ?string $textMessage = null, ?string $from = null): bool
    {
        // Use configured email sender if not specified
        if ($from === null) {
            $senderName = \App\Config::getEmailSenderName();
            $senderEmail = \App\Config::getEmailSender();
            $from = "{$senderName} <{$senderEmail}>";
        }

        // Generate plain text version if not provided
        if ($textMessage === null) {
            $textMessage = strip_tags($htmlMessage);
        }

        if (self::isDevelopment()) {
            // In development, log the email instead of sending
            self::logEmailForDevelopment($to, $subject, $htmlMessage, $from, true);
            return true;
        }

        // In production, use Resend
        try {
            $apiKey = self::getResendApiKey();

            if (!$apiKey) {
                error_log("Resend API key not configured. Falling back to PHP mail()");
                return self::sendWithPhpMail($to, $subject, $htmlMessage, $from, true);
            }

            $resend = Resend::client($apiKey);

            $result = $resend->emails->send([
                'from' => $from,
                'to' => [$to],
                'subject' => $subject,
                'html' => $htmlMessage,
                'text' => $textMessage,
            ]);

            // Log success
            error_log("HTML Email sent via Resend to {$to}: " . ($result->id ?? 'SUCCESS'));

            return true;

        } catch (\Exception $e) {
            // Log the error
            error_log("Resend error: " . $e->getMessage());

            // Fall back to PHP mail as last resort
            error_log("Falling back to PHP mail() due to Resend error");
            return self::sendWithPhpMail($to, $subject, $htmlMessage, $from, true);
        }
    }

    /**
     * Fallback method using PHP's mail() function
     */
    private static function sendWithPhpMail(string $to, string $subject, string $message, string $from, bool $isHtml = false): bool
    {
        $contentType = $isHtml ? 'text/html' : 'text/plain';

        $headers = 'From: ' . $from . "\r\n" .
                   'Reply-To: ' . $from . "\r\n" .
                   'Return-Path: ' . $from . "\r\n" .
                   'MIME-Version: 1.0' . "\r\n" .
                   'Content-Type: ' . $contentType . '; charset=UTF-8' . "\r\n" .
                   'Content-Transfer-Encoding: 8bit' . "\r\n" .
                   'X-Mailer: Happiness Platform' . "\r\n" .
                   'X-Priority: 3' . "\r\n";

        $result = mail($to, $subject, $message, $headers);

        // Log the result
        error_log("Email to {$to} (PHP mail): " . ($result ? 'SUCCESS' : 'FAILED'));

        return $result;
    }

    private static function logEmailForDevelopment(string $to, string $subject, string $message, string $from, bool $isHtml = false): void
    {
        $logFile = __DIR__ . '/../../development_emails.log';

        $timestamp = date('Y-m-d H:i:s');
        $emailType = $isHtml ? 'HTML' : 'TEXT';
        $emailLog = "\n" . str_repeat('=', 60) . "\n";
        $emailLog .= "📧 {$emailType} EMAIL SENT AT: {$timestamp}\n";
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

    /**
     * Generate HTML for welcome/dashboard email
     * Based on prototype: oldprototype/code/src/app/components/EmailPreview.tsx (dashboard)
     */
    public static function generateWelcomeEmailHtml(string $name, string $dashboardUrl): string
    {
        $html = <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff;">
  <!-- Header with gradient -->
  <div style="background: linear-gradient(to right, #f97316, #ec4899, #f97316); padding: 40px 24px; text-align: center;">
    <div style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 16px;">
      <span style="font-size: 20px;">✨</span>
      <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 24px; font-weight: 700; color: #fbbf24;">One Trillion Smiles</div>
    </div>
    <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: white; font-size: 36px; font-weight: 700; margin: 0 0 12px 0; line-height: 1.2;">Welcome to One Trillion Smiles!</h1>
    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: rgba(255, 255, 255, 0.95); font-size: 18px; margin: 0; line-height: 1.6;">Your private profile is ready to spread happiness</p>
  </div>

  <!-- Body content -->
  <div style="padding: 40px 24px;">
    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #374151; font-size: 16px; line-height: 1.8; margin: 0 0 24px 0;">Hey {$name}!</p>

    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #374151; font-size: 16px; line-height: 1.8; margin: 0 0 32px 0;">
      You're all set to start spreading smiles! Your private profile is ready, where you can send heartfelt messages to coworkers and track all the happiness you create. 🎉
    </p>

    <!-- CTA Button -->
    <div style="text-align: center; margin: 40px 0;">
      <a href="{$dashboardUrl}" style="display: inline-block; background: linear-gradient(to right, #f97316, #ec4899); color: white; text-decoration: none; padding: 18px 48px; border-radius: 9999px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-weight: 600; font-size: 18px; box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);">
        Open My Private Profile 🚀
      </a>
    </div>

    <!-- Feature highlights -->
    <div style="margin: 32px 0;">
      <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; font-size: 18px; font-weight: 600; margin: 0 0 16px 0;">What you can do:</p>

      <div style="margin: 12px 0;">
        <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #374151; font-size: 15px; line-height: 1.6; margin: 0;">
          <span style="color: #f97316; font-size: 20px; margin-right: 8px;">💌</span>
          <strong>Make people smile</strong> - Tell coworkers why you love working with them
        </p>
      </div>

      <div style="margin: 12px 0;">
        <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #374151; font-size: 15px; line-height: 1.6; margin: 0;">
          <span style="color: #f97316; font-size: 20px; margin-right: 8px;">📊</span>
          <strong>Track your impact</strong> - See how many smiles you've created
        </p>
      </div>

      <div style="margin: 12px 0;">
        <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #374151; font-size: 15px; line-height: 1.6; margin: 0;">
          <span style="color: #f97316; font-size: 20px; margin-right: 8px;">🌟</span>
          <strong>Join the movement</strong> - Help reach 1 trillion smiles worldwide
        </p>
      </div>
    </div>

    <!-- Tip box -->
    <div style="background: linear-gradient(to right, #fed7aa, #fbcfe8); border-left: 4px solid #f97316; padding: 20px; border-radius: 12px; margin: 32px 0;">
      <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #78350f; font-size: 14px; line-height: 1.6; margin: 0;">
        <strong>💡 Pro tip:</strong> Bookmark your private profile link to get back anytime! We'll never ask for a password - just use this special link.
      </p>
    </div>

    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #6b7280; font-size: 14px; line-height: 1.6; margin: 24px 0 0 0;">
      Happy creating! Every Smile you send makes work a little brighter for someone. 🌈
    </p>
  </div>

  <!-- Footer -->
  <div style="background: #f9fafb; padding: 32px 24px; text-align: center; border-top: 1px solid #e5e7eb;">
    <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #9ca3af; font-size: 12px; margin: 0 0 8px 0;">
      ✨ <strong>One Trillion Smiles</strong>
    </p>
    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #9ca3af; font-size: 12px; margin: 0; line-height: 1.6;">
      Making work a little happier, one Smile at a time
    </p>
  </div>
</div>
HTML;

        return $html;
    }

    /**
     * Generate HTML for smile notification email
     * Based on prototype: oldprototype/code/src/app/components/EmailPreview.tsx (smile)
     */
    public static function generateSmileNotificationEmailHtml(string $recipientName, string $senderName, string $messageUrl): string
    {
        $html = <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff;">
  <!-- Header with gradient -->
  <div style="background: linear-gradient(to right, #f97316, #ec4899, #f97316); padding: 40px 24px; text-align: center;">
    <div style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 16px;">
      <span style="font-size: 20px;">✨</span>
      <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 24px; font-weight: 700; color: #fbbf24;">One Trillion Smiles</div>
    </div>
    <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: white; font-size: 36px; font-weight: 700; margin: 0 0 12px 0; line-height: 1.2;">{$senderName} wants to make you smile</h1>
    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: rgba(255, 255, 255, 0.95); font-size: 18px; margin: 0; line-height: 1.6;">A coworker took the time to brighten your day</p>
  </div>

  <!-- Body content -->
  <div style="padding: 40px 24px;">
    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #374151; font-size: 16px; line-height: 1.8; margin: 0 0 24px 0;">Hey {$recipientName}!</p>

    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #374151; font-size: 16px; line-height: 1.8; margin: 0 0 32px 0;">
      <strong style="color: #f97316;">{$senderName}</strong> sent you a heartfelt message. Click below to read it and spread the happiness! 🎉
    </p>

    <!-- CTA Button -->
    <div style="text-align: center; margin: 40px 0;">
      <a href="{$messageUrl}" style="display: inline-block; background: linear-gradient(to right, #f97316, #ec4899); color: white; text-decoration: none; padding: 18px 48px; border-radius: 9999px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-weight: 600; font-size: 18px; box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);">
        Read Your Message
      </a>
    </div>

    <!-- Info box -->
    <div style="background: linear-gradient(to right, #fed7aa, #fbcfe8); border-left: 4px solid #f97316; padding: 20px; border-radius: 12px; margin: 32px 0;">
      <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #78350f; font-size: 14px; line-height: 1.6; margin: 0;">
        <strong>💡 What is this?</strong><br/>
        A coworker is using One Trillion Smiles to send heartfelt messages that create happiness and brighten your workday!
      </p>
    </div>

    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #6b7280; font-size: 14px; line-height: 1.6; margin: 24px 0 0 0;">
      After reading your Smile, you can send one back and join the movement to make work a little happier! 🌟
    </p>
  </div>

  <!-- Footer -->
  <div style="background: #f9fafb; padding: 32px 24px; text-align: center; border-top: 1px solid #e5e7eb;">
    <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #9ca3af; font-size: 12px; margin: 0 0 8px 0;">
      ✨ <strong>One Trillion Smiles</strong>
    </p>
    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #9ca3af; font-size: 12px; margin: 0; line-height: 1.6;">
      Making work a little happier, one Smile at a time
    </p>
  </div>
</div>
HTML;

        return $html;
    }

    /**
     * Generate HTML for email confirmation
     * Based on prototype: oldprototype/code/src/app/components/EmailPreview.tsx (confirmation)
     */
    public static function generateConfirmationEmailHtml(string $confirmUrl): string
    {
        $html = <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff;">
  <!-- Header with gradient -->
  <div style="background: linear-gradient(to right, #f97316, #ec4899, #f97316); padding: 40px 24px; text-align: center;">
    <div style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 16px;">
      <span style="font-size: 20px;">✨</span>
      <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 24px; font-weight: 700; color: #fbbf24;">One Trillion Smiles</div>
    </div>
    <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: white; font-size: 36px; font-weight: 700; margin: 0 0 12px 0; line-height: 1.2;">One quick step!</h1>
    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: rgba(255, 255, 255, 0.95); font-size: 18px; margin: 0; line-height: 1.6;">Confirm your email to start spreading smiles</p>
  </div>

  <!-- Body content -->
  <div style="padding: 40px 24px;">
    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #374151; font-size: 16px; line-height: 1.8; margin: 0 0 24px 0;">Hey there!</p>

    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #374151; font-size: 16px; line-height: 1.8; margin: 0 0 32px 0;">
      We're excited to have you join the movement to make work a little happier! Just one quick click to confirm your email address and you'll be spreading smiles in no time. ✨
    </p>

    <!-- CTA Button -->
    <div style="text-align: center; margin: 40px 0;">
      <a href="{$confirmUrl}" style="display: inline-block; background: linear-gradient(to right, #f97316, #ec4899); color: white; text-decoration: none; padding: 18px 48px; border-radius: 9999px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-weight: 600; font-size: 18px; box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);">
        Confirm My Email ✅
      </a>
    </div>

    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #6b7280; font-size: 14px; line-height: 1.6; margin: 32px 0; text-align: center;">
      This link will expire in 24 hours
    </p>

    <!-- What happens next -->
    <div style="background: linear-gradient(to right, #fed7aa, #fbcfe8); border-left: 4px solid #f97316; padding: 20px; border-radius: 12px; margin: 32px 0;">
      <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #78350f; font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">
        What happens next?
      </p>
      <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #78350f; font-size: 14px; line-height: 1.6; margin: 0;">
        After confirming, you'll get instant access to your private profile where you can start sending heartfelt messages to coworkers and join thousands of people making work a little brighter! 🌟
      </p>
    </div>

    <!-- Didn't sign up section -->
    <div style="margin: 32px 0; padding: 20px; background: #f9fafb; border-radius: 12px;">
      <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #6b7280; font-size: 13px; line-height: 1.6; margin: 0; text-align: center;">
        <strong>Didn't sign up for One Trillion Smiles?</strong><br/>
        No worries! You can safely ignore this email. Someone may have entered your email address by mistake.
      </p>
    </div>
  </div>

  <!-- Footer -->
  <div style="background: #f9fafb; padding: 32px 24px; text-align: center; border-top: 1px solid #e5e7eb;">
    <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #9ca3af; font-size: 12px; margin: 0 0 8px 0;">
      ✨ <strong>One Trillion Smiles</strong>
    </p>
    <p style="font-family: Georgia, 'Times New Roman', Times, serif; color: #9ca3af; font-size: 12px; margin: 0; line-height: 1.6;">
      Making work a little happier, one Smile at a time
    </p>
  </div>
</div>
HTML;

        return $html;
    }
}
