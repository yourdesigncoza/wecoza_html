<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use DI\Container;
use ClassCRUD\Middleware\AuthMiddleware;
use ClassCRUD\Middleware\CSRFMiddleware;
use ClassCRUD\Middleware\CorsMiddleware;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Create DI container
$container = new Container();

// Configure container dependencies
$dependencies = require_once __DIR__ . '/../config/dependencies.php';
$dependencies($container);

// Create Slim app
AppFactory::setContainer($container);
$app = AppFactory::create();

// Set base path for the application
// For LAMPP setup, we need to detect the base path dynamically
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = dirname($scriptName);
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}
$app->setBasePath($basePath);

// Add middleware
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Temporarily disable custom middleware for debugging
// $app->add(new CorsMiddleware());
// $app->add(new CSRFMiddleware());

// Add routes
$routes = require_once __DIR__ . '/../config/routes.php';
$routes($app);

// Add error middleware
$errorMiddleware = $app->addErrorMiddleware(
    $_ENV['APP_DEBUG'] === 'true',
    true,
    true
);

// Run app
$app->run();
