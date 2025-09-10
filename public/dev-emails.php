<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Only allow this in development
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isDevelopment = strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;

if (!$isDevelopment) {
    http_response_code(404);
    exit('Not found');
}

$emails = \App\Services\EmailService::getRecentDevelopmentEmails(20);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Development Emails - Happiness</title>
    <style>
        body {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            margin: 0;
            padding: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
            line-height: 1.4;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 {
            color: #4fc3f7;
            border-bottom: 2px solid #4fc3f7;
            padding-bottom: 10px;
        }
        .email {
            background: #2d2d2d;
            border: 1px solid #404040;
            border-radius: 8px;
            margin: 20px 0;
            padding: 20px;
        }
        .email-header {
            color: #81c784;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .email-meta {
            color: #ffb74d;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .email-content {
            background: #1e1e1e;
            border: 1px solid #404040;
            border-radius: 4px;
            padding: 15px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .no-emails {
            text-align: center;
            color: #9e9e9e;
            font-style: italic;
            margin: 40px 0;
        }
        .refresh-btn {
            background: #4fc3f7;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .refresh-btn:hover {
            background: #29b6f6;
        }
        .instructions {
            background: #2d2d2d;
            border: 1px solid #404040;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .instructions h3 {
            color: #81c784;
            margin-top: 0;
        }
        .instructions code {
            background: #1e1e1e;
            padding: 2px 6px;
            border-radius: 3px;
            color: #ffb74d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Development Emails</h1>
        
        <div class="instructions">
            <h3>📝 How This Works</h3>
            <p>In development mode, emails are logged to a file instead of being sent. This lets you see exactly what emails would be sent to users without needing a real mail server.</p>
            <p>Try these actions in the admin dashboard:</p>
            <ul>
                <li><strong>Allow User on Site:</strong> Creates account and sends creation link</li>
                <li><strong>Send Reminder:</strong> Sends reminder email with creation link</li>
                <li><strong>Reset Creation Page:</strong> Generates new creation URL and sends email</li>
            </ul>
            <p>Visit: <code><a href="/admin" style="color: #4fc3f7;">localhost:8080/admin</a></code></p>
        </div>
        
        <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
        
        <?php if (empty($emails)): ?>
            <div class="no-emails">
                <p>📭 No emails sent yet</p>
                <p>Go to the <a href="/admin" style="color: #4fc3f7;">admin dashboard</a> and try allowing a user on the site to see emails appear here!</p>
            </div>
        <?php else: ?>
            <?php foreach ($emails as $email): ?>
                <div class="email">
                    <?php 
                    $lines = explode("\n", trim($email));
                    $timestamp = '';
                    $to = '';
                    $from = '';
                    $subject = '';
                    $messageStart = 0;
                    
                    foreach ($lines as $i => $line) {
                        if (strpos($line, '📧 EMAIL SENT AT:') !== false) {
                            $timestamp = str_replace('📧 EMAIL SENT AT: ', '', $line);
                        } elseif (strpos($line, 'To:') === 0) {
                            $to = str_replace('To: ', '', $line);
                        } elseif (strpos($line, 'From:') === 0) {
                            $from = str_replace('From: ', '', $line);
                        } elseif (strpos($line, 'Subject:') === 0) {
                            $subject = str_replace('Subject: ', '', $line);
                        } elseif ($line === 'Message:') {
                            $messageStart = $i + 1;
                            break;
                        }
                    }
                    
                    $messageLines = array_slice($lines, $messageStart);
                    $messageContent = implode("\n", $messageLines);
                    ?>
                    
                    <div class="email-header">📧 <?php echo htmlspecialchars($subject); ?></div>
                    <div class="email-meta">
                        <strong>To:</strong> <?php echo htmlspecialchars($to); ?> &nbsp;&nbsp;
                        <strong>From:</strong> <?php echo htmlspecialchars($from); ?> &nbsp;&nbsp;
                        <strong>Time:</strong> <?php echo htmlspecialchars($timestamp); ?>
                    </div>
                    <div class="email-content"><?php echo htmlspecialchars($messageContent); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>