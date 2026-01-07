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

// Routes

// Homepage
$app->get('/', function ($request, $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'homepage.twig', [
        'title' => \App\Config::getAppName() . ' - ' . \App\Config::getAppDescription(),
        'smile_count' => \App\Services\StatsService::getSmileCount(),
        'leaderboard' => \App\Services\StatsService::getLeaderboard()
    ]);
});

// Signup (AJAX)
$app->post('/api/signup', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $email = trim($data['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Valid email required']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    try {
        $creationUrl = \App\Services\SignupService::createPage($email);
        $response->getBody()->write(json_encode([
            'success' => true,
            'creation_url' => $creationUrl
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// Admin dashboard
$app->get('/admin', function ($request, $response, $args) {
    $view = Twig::fromRequest($request);
    $users = \App\Services\AdminService::getAllUsers();
    return $view->render($response, 'admin.twig', [
        'title' => 'Admin Dashboard',
        'users' => $users,
        'container_class' => 'admin-container'
    ]);
});

// Admin actions (AJAX)
$app->post('/api/admin/{action}', function ($request, $response, $args) {
    $action = $args['action'];
    $data = $request->getParsedBody();
    
    try {
        switch ($action) {
            case 'allow-user':
                \App\Services\AdminService::allowUser($data['email']);
                $response->getBody()->write(json_encode(['success' => true]));
                return $response->withHeader('Content-Type', 'application/json');
                
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
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// Development email viewer (only accessible in development)
$app->get('/dev/emails', function ($request, $response, $args) {
    // Only allow in development
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isDevelopment = strpos($host, 'localhost') !== false || 
                     strpos($host, '127.0.0.1') !== false ||
                     strpos($host, 'dreamhost') !== false ||
                     strpos($host, 'happiness.mikesorvillo.com') !== false;
    
    if (!$isDevelopment) {
        return $response->withStatus(404);
    }
    
    $view = Twig::fromRequest($request);
    $emails = \App\Services\EmailService::getRecentDevelopmentEmails(50);
    return $view->render($response, 'dev-emails.twig', [
        'title' => 'Development Emails',
        'emails' => $emails
    ]);
});

// Clear development emails (AJAX)
$app->post('/api/dev/clear-emails', function ($request, $response, $args) {
    // Only allow in development
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (strpos($host, 'localhost') === false && strpos($host, '127.0.0.1') === false) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Not allowed in production']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }
    
    try {
        \App\Services\EmailService::clearDevelopmentEmails();
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// Creation flow
$app->get('/create/{creation_url}', function ($request, $response, $args) {
    $creationUrl = $args['creation_url'];
    
    try {
        $sender = \App\Services\SenderService::getSenderByCreationUrl($creationUrl);
        $view = Twig::fromRequest($request);
        return $view->render($response, 'creation.twig', [
            'title' => 'Create Your Happiness Page',
            'sender' => $sender,
            'themes' => \App\Services\ThemeService::getAllThemes(),
            'domain' => \App\Config::getDomain()
        ]);
    } catch (\Exception $e) {
        return $response->withStatus(404);
    }
});

// Creation flow save (AJAX)
// New creation API endpoint (matches the redesigned template)
$app->post('/api/create/{creation_url}', function ($request, $response, $args) {
    $creationUrl = $args['creation_url'];
    $data = $request->getParsedBody();
    
    try {
        \App\Services\SenderService::saveCreationData($creationUrl, $data);
        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Saved!']));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// Legacy creation API endpoint (for backward compatibility)
$app->post('/api/create/{creation_url}/save', function ($request, $response, $args) {
    $creationUrl = $args['creation_url'];
    $data = $request->getParsedBody();
    
    try {
        \App\Services\SenderService::saveCreationData($creationUrl, $data);
        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Saved!']));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// Happiness page
$app->get('/{slug}', function ($request, $response, $args) {
    $slug = $args['slug'];
    
    try {
        $sender = \App\Services\SenderService::getSenderBySlug($slug);
        $view = Twig::fromRequest($request);
        return $view->render($response, 'happiness.twig', [
            'title' => $sender['overall_message'] ?? 'Happiness!',
            'sender' => $sender
        ]);
    } catch (\Exception $e) {
        return $response->withStatus(404);
    }
});

// Happiness page lookup (AJAX)
$app->post('/api/{slug}/lookup', function ($request, $response, $args) {
    $slug = $args['slug'];
    $data = $request->getParsedBody();
    $email = trim($data['email'] ?? '');
    
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Valid email required']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    try {
        $result = \App\Services\MessageService::lookupMessage($slug, $email);
        
        if ($result) {
            // Increment global smile count
            \App\Services\StatsService::incrementSmileCount();

            // Increment per-page smile count
            $sender = \App\Services\SenderService::getSenderBySlug($slug);
            \App\Services\SenderService::incrementPageSmileCount($sender['id']);

            $response->getBody()->write(json_encode([
                'success' => true,
                'name' => $result['recipient_name'],
                'message' => $result['message'],
                'emotion' => $result['emotion']
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $sender = \App\Services\SenderService::getSenderBySlug($slug);
            $response->getBody()->write(json_encode([
                'success' => true,
                'name' => null,
                'message' => null,
                'not_found_message' => $sender['not_found_message']
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// Publish happiness page (send emails)
$app->post('/api/publish/{creation_url}', function ($request, $response, $args) {
    $creationUrl = $args['creation_url'];

    try {
        $result = \App\Services\SenderService::publishPage($creationUrl);
        $response->getBody()->write(json_encode([
            'success' => true,
            'emails_sent' => $result['emails_sent']
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

$app->run();