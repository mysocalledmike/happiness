<?php

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Create App
$app = AppFactory::create();

// Create Twig
$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Add routing middleware (required in Slim 4)
$app->addRoutingMiddleware();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// ========================================
// PUBLIC ROUTES
// ========================================

// Homepage
$app->get('/', function ($request, $response, $args) {
    $view = Twig::fromRequest($request);

    $params = $request->getQueryParams();
    $company = $params['company'] ?? null;

    $globalSmiles = \App\Services\StatsService::getSmileCount();
    $totalCompanies = \App\Services\StatsService::getTotalCompanies();
    $topCompanies = \App\Services\StatsService::getTopCompanies(5);

    $data = [
        'title' => 'One Trillion Smiles',
        'global_smiles' => $globalSmiles,
        'global_progress' => number_format(($globalSmiles / 1000000000000) * 100, 7),
        'total_companies' => $totalCompanies,
        'top_companies' => $topCompanies,
        'featured_company' => null,
        'featured_company_name' => null,
        'featured_company_smiles' => 0,
        'featured_company_top_senders' => []
    ];

    // Only get featured company stats if company parameter is provided
    if ($company) {
        $companyStats = \App\Services\StatsService::getCompanyStats($company);
        $data['featured_company'] = $company;
        $data['featured_company_name'] = ucfirst(explode('.', $company)[0]);
        $data['featured_company_smiles'] = $companyStats['smile_count'];
        $data['featured_company_top_senders'] = $companyStats['top_senders'];
    }

    return $view->render($response, 'homepage.twig', $data);
});

// Signup API
$app->post('/api/signup', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $avatar = trim($data['avatar'] ?? '');

    if (!$name || !$email || !$avatar) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'All fields required']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'Valid email required']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    try {
        $result = \App\Services\SignupService::createUser($name, $email, $avatar);

        if ($result['existing']) {
            // Email already exists - don't redirect, just show message
            $response->getBody()->write(json_encode([
                'success' => true,
                'existing' => true,
                'message' => 'This email already started sending smiles - check your email for a link to your dashboard'
            ]));
        } else {
            // New user - redirect to dashboard
            $response->getBody()->write(json_encode([
                'success' => true,
                'existing' => false,
                'dashboard_url' => $result['dashboard_url']
            ]));
        }

        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// Email confirmation
$app->get('/confirm/{token}', function ($request, $response, $args) {
    $token = $args['token'];

    $db = \App\Database::getInstance();
    $sender = $db->fetchOne(
        'SELECT dashboard_url, email_confirmed FROM senders WHERE email_confirmation_token = ?',
        [$token]
    );

    if (!$sender) {
        // Invalid token - redirect to homepage
        return $response->withHeader('Location', '/?error=invalid_token')->withStatus(302);
    }

    $alreadyConfirmed = $sender['email_confirmed'];

    // Confirm the email (will do nothing if already confirmed)
    \App\Services\SignupService::confirmEmail($token);

    // Redirect to dashboard with appropriate message
    $queryParam = $alreadyConfirmed ? 'already_confirmed=1' : 'confirmed=1';
    return $response->withHeader('Location', '/dashboard/' . $sender['dashboard_url'] . '?' . $queryParam)->withStatus(302);
});

// ========================================
// DASHBOARD ROUTES
// ========================================

// Dashboard page
$app->get('/dashboard/{dashboard_url}', function ($request, $response, $args) {
    $dashboardUrl = $args['dashboard_url'];
    $view = Twig::fromRequest($request);
    
    $db = \App\Database::getInstance();
    $sender = $db->fetchOne('SELECT * FROM senders WHERE dashboard_url = ?', [$dashboardUrl]);
    
    if (!$sender) {
        $response->getBody()->write('Dashboard not found');
        return $response->withStatus(404);
    }
    
    $messages = \App\Services\MessageService::getMessagesBySender($sender['id']);
    $smileCount = \App\Services\StatsService::getSenderSmileCount($sender['id']);
    $messageCount = count($messages);
    
    $globalSmiles = \App\Services\StatsService::getSmileCount();
    $totalCompanies = \App\Services\StatsService::getTotalCompanies();
    $topCompanies = \App\Services\StatsService::getTopCompanies(10);
    $company = \App\Services\StatsService::getCompanyFromEmail($sender['email']);

    $params = $request->getQueryParams();
    $firstVisit = isset($params['first_visit']) && $params['first_visit'] == '1';
    $confirmed = isset($params['confirmed']) && $params['confirmed'] == '1';
    $alreadyConfirmed = isset($params['already_confirmed']) && $params['already_confirmed'] == '1';
    $smileSent = isset($params['smile_sent']) && $params['smile_sent'] == '1';
    $recipientName = $params['recipient_name'] ?? null;

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
    $fullDashboardUrl = "{$protocol}://{$host}/dashboard/{$dashboardUrl}";

    $data = [
        'title' => $sender['name'] . ' - Dashboard',
        'sender' => $sender,
        'messages' => $messages,
        'smile_count' => $smileCount,
        'message_count' => $messageCount,
        'first_visit' => $firstVisit,
        'confirmed' => $confirmed,
        'already_confirmed' => $alreadyConfirmed,
        'smile_sent' => $smileSent,
        'recipient_name' => $recipientName,
        'dashboard_url' => $fullDashboardUrl,
        'global_smiles' => $globalSmiles,
        'global_progress' => number_format(($globalSmiles / 1000000000000) * 100, 7),
        'total_companies' => $totalCompanies,
        'top_companies' => $topCompanies,
        'company' => null,
        'company_name' => null
    ];

    if ($company) {
        $companyStats = \App\Services\StatsService::getCompanyStats($company);
        $data['company'] = $company;
        $data['company_name'] = ucfirst(explode('.', $company)[0]);
        $data['company_smiles'] = $companyStats['smile_count'];
        $data['company_top_senders'] = $companyStats['top_senders'];
    }
    
    return $view->render($response, 'dashboard.twig', $data);
});

// Request email confirmation
$app->post('/api/dashboard/{dashboard_url}/request-confirmation', function ($request, $response, $args) {
    $dashboardUrl = $args['dashboard_url'];

    $db = \App\Database::getInstance();
    $sender = $db->fetchOne('SELECT id, name, email, email_confirmation_token FROM senders WHERE dashboard_url = ?', [$dashboardUrl]);

    if (!$sender) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'Dashboard not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Send confirmation email
    \App\Services\SignupService::sendConfirmationOnlyEmail(
        $sender['name'],
        $sender['email'],
        $sender['email_confirmation_token'],
        $dashboardUrl
    );

    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Send message API
$app->post('/api/dashboard/{dashboard_url}/send', function ($request, $response, $args) {
    $dashboardUrl = $args['dashboard_url'];
    $data = $request->getParsedBody();

    // Handle JSON body if getParsedBody returns null
    if ($data === null) {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);
    }

    $db = \App\Database::getInstance();
    $sender = $db->fetchOne('SELECT id FROM senders WHERE dashboard_url = ?', [$dashboardUrl]);

    if (!$sender) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'Dashboard not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    try {
        $result = \App\Services\MessageService::createMessage(
            $sender['id'],
            $data['recipient_name'] ?? '',
            $data['recipient_email'] ?? '',
            $data['message'] ?? ''
        );

        $response->getBody()->write(json_encode([
            'success' => true,
            'message_url' => $result['message_url']
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
        $needsConfirmation = strpos($errorMessage, 'confirm your email') !== false;

        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $errorMessage,
            'needs_confirmation' => $needsConfirmation
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// ========================================
// MESSAGE ROUTES
// ========================================

// Message page
$app->get('/s/{message_url}', function ($request, $response, $args) {
    $messageUrl = $args['message_url'];
    $view = Twig::fromRequest($request);

    $messageData = \App\Services\MessageService::getMessageByUrl($messageUrl);

    if (!$messageData) {
        $response->getBody()->write('Message not found');
        return $response->withStatus(404);
    }

    // Don't auto-increment smile count anymore - user must click the button

    $senderSmileCount = \App\Services\StatsService::getSenderSmileCount($messageData['sender_id']);
    $globalSmiles = \App\Services\StatsService::getSmileCount();
    $totalCompanies = \App\Services\StatsService::getTotalCompanies();
    $topCompanies = \App\Services\StatsService::getTopCompanies(10);

    // Get sender full info
    $db = \App\Database::getInstance();
    $sender = $db->fetchOne('SELECT * FROM senders WHERE id = ?', [$messageData['sender_id']]);

    // Detect company from sender email (stats show sender's company impact)
    $company = \App\Services\StatsService::getCompanyFromEmail($sender['email']);

    $data = [
        'title' => 'A Smile from ' . $messageData['sender_name'],
        'message' => $messageData,
        'sender' => $sender,
        'sender_smile_count' => $senderSmileCount,
        'global_smiles' => $globalSmiles,
        'global_progress' => number_format(($globalSmiles / 1000000000000) * 100, 7),
        'total_companies' => $totalCompanies,
        'top_companies' => $topCompanies,
        'company' => null,
        'company_name' => null
    ];

    if ($company) {
        $companyStats = \App\Services\StatsService::getCompanyStats($company);
        $data['company'] = $company;
        $data['company_name'] = ucfirst(explode('.', $company)[0]);
        $data['company_smiles'] = $companyStats['smile_count'];
        $data['company_top_senders'] = $companyStats['top_senders'];
    }
    
    return $view->render($response, 'message.twig', $data);
});

// Record smile (button click)
$app->post('/api/messages/{message_url}/smile', function ($request, $response, $args) {
    $messageUrl = $args['message_url'];

    try {
        // Mark as read and increment counters
        $success = \App\Services\MessageService::markAsRead($messageUrl);

        if ($success) {
            // Get updated stats
            $messageData = \App\Services\MessageService::getMessageByUrl($messageUrl);
            $senderSmileCount = \App\Services\StatsService::getSenderSmileCount($messageData['sender_id']);
            $globalSmiles = \App\Services\StatsService::getSmileCount();

            // Get sender info to determine company
            $senderData = \App\Database::getInstance()->fetchOne('SELECT email FROM senders WHERE id = ?', [$messageData['sender_id']]);
            $company = $senderData ? \App\Services\StatsService::getCompanyFromEmail($senderData['email']) : null;
            $companySmiles = 0;
            if ($company) {
                $companyStats = \App\Services\StatsService::getCompanyStats($company);
                $companySmiles = $companyStats['smile_count'];
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'sender_smile_count' => $senderSmileCount,
                'global_smiles' => $globalSmiles,
                'company_smiles' => $companySmiles
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Failed to record smile'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// Quick send - Create account and send message in one step (from message page)
$app->post('/api/messages/quick-send', function ($request, $response, $args) {
    $data = $request->getParsedBody();

    // Handle JSON body if getParsedBody returns null
    if ($data === null) {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);
    }

    $senderName = trim($data['sender_name'] ?? '');
    $senderEmail = trim($data['sender_email'] ?? '');
    $recipientName = trim($data['recipient_name'] ?? '');
    $recipientEmail = trim($data['recipient_email'] ?? '');
    $message = trim($data['message'] ?? '');

    if (!$senderName || !$senderEmail || !$recipientName || !$recipientEmail || !$message) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'All fields required']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'Valid emails required']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    try {
        // Use QuickSendService to handle account creation and message sending
        $result = \App\Services\QuickSendService::quickSend(
            $senderName,
            $senderEmail,
            $recipientName,
            $recipientEmail,
            $message
        );

        $response->getBody()->write(json_encode([
            'success' => true,
            'dashboard_url' => $result['dashboard_url'],
            'existing_user' => $result['existing_user']
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// Company stats API
$app->get('/api/company-stats/{company}', function ($request, $response, $args) {
    $company = $args['company'];

    try {
        $stats = \App\Services\StatsService::getCompanyStats($company);

        $response->getBody()->write(json_encode($stats));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'error' => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// Lookup other messages from sender API
$app->post('/api/sender/{sender_id}/lookup', function ($request, $response, $args) {
    $senderId = (int) $args['sender_id'];
    $data = $request->getParsedBody();
    $email = trim($data['email'] ?? '');

    if (!$email) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'Email required']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $messages = \App\Services\MessageService::getOtherMessagesBySender($senderId, $email);

    $response->getBody()->write(json_encode([
        'success' => true,
        'messages' => $messages
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Delete message API
$app->delete('/api/messages/{message_id}', function ($request, $response, $args) {
    $messageId = (int) $args['message_id'];
    
    // For now, we'll accept any delete request
    // In production, you'd want to verify dashboard_url or some auth
    $db = \App\Database::getInstance();
    $message = $db->fetchOne('SELECT sender_id FROM messages WHERE id = ?', [$messageId]);
    
    if (!$message) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'Message not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $success = \App\Services\MessageService::deleteMessage($messageId, $message['sender_id']);
    
    $response->getBody()->write(json_encode(['success' => $success]));
    return $response->withHeader('Content-Type', 'application/json');
});

// ========================================
// ADMIN ROUTES
// ========================================

// Admin dashboard
$app->get('/admin', function ($request, $response, $args) {
    $view = Twig::fromRequest($request);
    $users = \App\Services\AdminService::getAllUsers();
    return $view->render($response, 'admin.twig', [
        'title' => 'Admin Dashboard',
        'users' => $users
    ]);
});

// Admin actions (AJAX)
$app->post('/api/admin/{action}', function ($request, $response, $args) {
    $action = $args['action'];
    $data = $request->getParsedBody();
    
    try {
        switch ($action) {
            case 'send-reminder':
                \App\Services\AdminService::sendReminder($data['email']);
                $response->getBody()->write(json_encode(['success' => true]));
                return $response->withHeader('Content-Type', 'application/json');
                
            case 'reset-creation':
                \App\Services\AdminService::resetCreationPage($data['email']);
                $response->getBody()->write(json_encode(['success' => true]));
                return $response->withHeader('Content-Type', 'application/json');
                
            case 'delete-user':
                \App\Services\AdminService::deleteUser($data['email']);
                $response->getBody()->write(json_encode(['success' => true]));
                return $response->withHeader('Content-Type', 'application/json');
                
            default:
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid action']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// ========================================
// DEV ROUTES
// ========================================

// Simple test route
$app->get('/test', function ($request, $response, $args) {
    $response->getBody()->write('Test route works!');
    return $response;
});

// Development email viewer (localhost only)
$app->get('/dev/emails', function ($request, $response, $args) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!in_array($host, ['localhost', '127.0.0.1', 'localhost:8080', 'happiness.mikesorvillo.com', 'onetrillionsmiles.com'])) {
        $response->getBody()->write('Access denied');
        return $response->withStatus(403);
    }
    
    $view = Twig::fromRequest($request);
    
    $logFile = __DIR__ . '/../development_emails.log';
    $emails = [];

    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        $separator = str_repeat('=', 60);
        $emailBlocks = explode($separator, $content);
        foreach ($emailBlocks as $block) {
            if (trim($block)) {
                $emails[] = $block;
            }
        }
    }
    
    return $view->render($response, 'dev-emails.twig', [
        'title' => 'Development Emails',
        'emails' => array_reverse($emails)
    ]);
});

// Clear development emails (localhost only)
$app->post('/api/dev/clear-emails', function ($request, $response, $args) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!in_array($host, ['localhost', '127.0.0.1', 'localhost:8080', 'happiness.mikesorvillo.com', 'onetrillionsmiles.com'])) {
        $response->getBody()->write(json_encode(['success' => false]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }
    
    $logFile = __DIR__ . '/../development_emails.log';
    file_put_contents($logFile, '');
    
    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
