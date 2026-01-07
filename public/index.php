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
        'show_company_stats' => false
    ];
    
    // Add company stats if provided
    if ($company) {
        $companyStats = \App\Services\StatsService::getCompanyStats($company);
        $data['show_company_stats'] = true;
        $data['company'] = $company;
        $data['company_name'] = ucfirst(explode('.', $company)[0]);
        $data['company_smiles'] = $companyStats['smile_count'];
        $data['company_top_senders'] = $companyStats['top_senders'];
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
        $dashboardUrl = \App\Services\SignupService::createUser($name, $email, $avatar);
        $response->getBody()->write(json_encode([
            'success' => true,
            'dashboard_url' => $dashboardUrl
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// Email confirmation
$app->get('/confirm/{token}', function ($request, $response, $args) {
    $token = $args['token'];
    
    $success = \App\Services\SignupService::confirmEmail($token);
    
    if ($success) {
        $response->getBody()->write('<html><body><h1>Email Confirmed!</h1><p>Your email has been confirmed. You can now send unlimited Smiles!</p><p><a href="/">Go to homepage</a></p></body></html>');
    } else {
        $response->getBody()->write('<html><body><h1>Invalid Token</h1><p>This confirmation link is invalid or has expired.</p><p><a href="/">Go to homepage</a></p></body></html>');
    }
    
    return $response;
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
        return $response->withStatus(404)->write('Dashboard not found');
    }
    
    $messages = \App\Services\MessageService::getMessagesBySender($sender['id']);
    $smileCount = \App\Services\StatsService::getSenderSmileCount($sender['id']);
    $messageCount = count($messages);
    
    $globalSmiles = \App\Services\StatsService::getSmileCount();
    $company = \App\Services\StatsService::getCompanyFromEmail($sender['email']);
    
    $params = $request->getQueryParams();
    $firstVisit = isset($params['first_visit']) && $params['first_visit'] == '1';
    
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
        'dashboard_url' => $fullDashboardUrl,
        'global_smiles' => $globalSmiles,
        'global_progress' => number_format(($globalSmiles / 1000000000000) * 100, 7),
        'company' => null
    ];
    
    if ($company) {
        $companyStats = \App\Services\StatsService::getCompanyStats($company);
        $data['company'] = $company;
        $data['company_smiles'] = $companyStats['smile_count'];
        $data['company_top_senders'] = $companyStats['top_senders'];
    }
    
    return $view->render($response, 'dashboard.twig', $data);
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
        $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
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
        return $response->withStatus(404)->write('Message not found');
    }
    
    // Mark as read
    \App\Services\MessageService::markAsRead($messageUrl);
    
    $senderSmileCount = \App\Services\StatsService::getSenderSmileCount($messageData['sender_id']);
    $globalSmiles = \App\Services\StatsService::getSmileCount();
    
    // Get sender full info
    $db = \App\Database::getInstance();
    $sender = $db->fetchOne('SELECT * FROM senders WHERE id = ?', [$messageData['sender_id']]);
    
    // Detect company from recipient email
    $company = \App\Services\StatsService::getCompanyFromEmail($messageData['recipient_email']);
    
    $data = [
        'title' => 'A Smile from ' . $messageData['sender_name'],
        'message' => $messageData,
        'sender' => $sender,
        'sender_smile_count' => $senderSmileCount,
        'global_smiles' => $globalSmiles,
        'global_progress' => number_format(($globalSmiles / 1000000000000) * 100, 7),
        'company' => null
    ];
    
    if ($company) {
        $companyStats = \App\Services\StatsService::getCompanyStats($company);
        $data['company'] = $company;
        $data['company_smiles'] = $companyStats['smile_count'];
        $data['company_top_senders'] = $companyStats['top_senders'];
    }
    
    return $view->render($response, 'message.twig', $data);
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

// Development email viewer (localhost only)
$app->get('/dev/emails', function ($request, $response, $args) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!in_array($host, ['localhost', '127.0.0.1', 'localhost:8080'])) {
        return $response->withStatus(403)->write('Access denied');
    }
    
    $view = Twig::fromRequest($request);
    
    $logFile = __DIR__ . '/../development_emails.log';
    $emails = [];
    
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        $emailBlocks = explode("\n---\n", $content);
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
    if (!in_array($host, ['localhost', '127.0.0.1', 'localhost:8080'])) {
        $response->getBody()->write(json_encode(['success' => false]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }
    
    $logFile = __DIR__ . '/../development_emails.log';
    file_put_contents($logFile, '');
    
    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
