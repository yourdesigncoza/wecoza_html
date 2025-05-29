<?php
declare(strict_types=1);

use DI\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use ClassCRUD\Services\DatabaseService;
use ClassCRUD\Services\AuthService;
use ClassCRUD\Services\ClassService;
use ClassCRUD\Services\FileUploadService;
use ClassCRUD\Repositories\ClassRepository;
use ClassCRUD\Repositories\UserRepository;
use ClassCRUD\Utilities\Validator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

return function (Container $container) {
    // Logger
    $container->set(Logger::class, function () {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($_ENV['LOG_PATH'] ?? 'logs/app.log', $_ENV['LOG_LEVEL'] ?? 'debug'));
        return $logger;
    });

    // Database
    $container->set(PDO::class, function () {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME'],
            $_ENV['DB_SSLMODE'] ?? 'require'
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_TIMEOUT => 30,
        ];

        return new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
    });

    // Database Service
    $container->set(DatabaseService::class, function (Container $c) {
        return new DatabaseService($c->get(PDO::class));
    });

    // Twig Template Engine
    $container->set(Environment::class, function () {
        $loader = new FilesystemLoader(__DIR__ . '/../templates');
        $twig = new Environment($loader, [
            'cache' => $_ENV['APP_ENV'] === 'production' ? __DIR__ . '/../cache/twig' : false,
            'debug' => $_ENV['APP_DEBUG'] === 'true',
        ]);

        // Calculate base path for URLs
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }

        // Add global variables
        $twig->addGlobal('app_name', $_ENV['APP_NAME']);
        $twig->addGlobal('app_url', $_ENV['APP_URL']);
        $twig->addGlobal('base_path', $basePath);

        return $twig;
    });

    // Repositories
    $container->set(ClassRepository::class, function (Container $c) {
        return new ClassRepository($c->get(DatabaseService::class));
    });

    $container->set(UserRepository::class, function (Container $c) {
        return new UserRepository($c->get(DatabaseService::class));
    });

    // Services
    $container->set(AuthService::class, function (Container $c) {
        return new AuthService($c->get(UserRepository::class));
    });

    $container->set(ClassService::class, function (Container $c) {
        return new ClassService($c->get(ClassRepository::class));
    });

    $container->set(FileUploadService::class, function () {
        return new FileUploadService($_ENV['UPLOAD_PATH'], $_ENV['UPLOAD_MAX_SIZE']);
    });

    // Local Data Services (for form reference data)
    $container->set(\ClassCRUD\Services\LocalDataService::class, function () {
        return new \ClassCRUD\Services\LocalDataService(__DIR__ . '/../data');
    });

    // Controllers
    $container->set(\ClassCRUD\Controllers\HomeController::class, function (Container $c) {
        return new \ClassCRUD\Controllers\HomeController(
            $c->get(ClassService::class),
            $c->get(\ClassCRUD\Services\LocalDataService::class),
            $c->get(Environment::class)
        );
    });

    $container->set(\ClassCRUD\Controllers\AuthController::class, function (Container $c) {
        return new \ClassCRUD\Controllers\AuthController(
            $c->get(AuthService::class),
            $c->get(Environment::class)
        );
    });

    $container->set(\ClassCRUD\Controllers\ClassController::class, function (Container $c) {
        return new \ClassCRUD\Controllers\ClassController(
            $c->get(ClassService::class),
            $c->get(\ClassCRUD\Services\LocalDataService::class),
            $c->get(Environment::class)
        );
    });

    $container->set(\ClassCRUD\Controllers\ApiController::class, function (Container $c) {
        return new \ClassCRUD\Controllers\ApiController(
            $c->get(ClassService::class),
            $c->get(\ClassCRUD\Services\LocalDataService::class)
        );
    });

    // Utilities
    $container->set(Validator::class, function () {
        return new Validator();
    });
};
