<?php
declare(strict_types=1);

namespace ClassCRUD\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ClassCRUD\Services\ClassService;
use ClassCRUD\Services\LocalDataService;
use Twig\Environment;

class HomeController
{
    private ClassService $classService;
    private LocalDataService $localDataService;
    private Environment $twig;

    public function __construct(
        ClassService $classService,
        LocalDataService $localDataService,
        Environment $twig
    ) {
        $this->classService = $classService;
        $this->localDataService = $localDataService;
        $this->twig = $twig;
    }

    /**
     * Home page - redirect to dashboard
     */
    public function index(Request $request, Response $response): Response
    {
        // Use relative redirect to dashboard
        return $response->withHeader('Location', 'dashboard')->withStatus(302);
    }

    /**
     * Dashboard with statistics and recent classes
     */
    public function dashboard(Request $request, Response $response): Response
    {
        try {
            // For now, let's test with minimal data to isolate the issue
            $html = $this->twig->render('dashboard.twig', [
                'stats' => [
                    'total_classes' => 0,
                    'active_classes' => 0,
                    'completed_classes' => 0,
                    'upcoming_classes' => 0
                ],
                'recent_classes' => [],
                'upcoming_classes' => [],
                'client_lookup' => [],
                'agent_lookup' => [],
                'supervisor_lookup' => [],
            ]);

            $response->getBody()->write($html);
            return $response;

        } catch (\Exception $e) {
            // Log error and show error page
            error_log('Dashboard error: ' . $e->getMessage());

            // For debugging, let's show a simple error message
            $response->getBody()->write('<h1>Dashboard Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>');
            return $response->withStatus(500);
        }
    }
}
