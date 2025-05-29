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
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    /**
     * Dashboard with statistics and recent classes
     */
    public function dashboard(Request $request, Response $response): Response
    {
        try {
            // Get dashboard statistics
            $stats = $this->classService->getDashboardStatistics();
            
            // Get recent classes (last 10)
            $recentClasses = $this->classService->getAllClasses([], 10, 0);
            
            // Get upcoming classes
            $upcomingClasses = $this->classService->getUpcomingClasses();
            
            // Get reference data for display
            $clients = $this->localDataService->getClients();
            $agents = $this->localDataService->getAgents();
            $supervisors = $this->localDataService->getSupervisors();
            
            // Create lookup arrays for easy reference
            $clientLookup = array_column($clients, 'name', 'id');
            $agentLookup = array_column($agents, 'name', 'id');
            $supervisorLookup = array_column($supervisors, 'name', 'id');

            $html = $this->twig->render('dashboard.twig', [
                'stats' => $stats,
                'recent_classes' => $recentClasses,
                'upcoming_classes' => $upcomingClasses,
                'client_lookup' => $clientLookup,
                'agent_lookup' => $agentLookup,
                'supervisor_lookup' => $supervisorLookup,
            ]);

            $response->getBody()->write($html);
            return $response;

        } catch (\Exception $e) {
            // Log error and show error page
            error_log('Dashboard error: ' . $e->getMessage());
            
            $html = $this->twig->render('error.twig', [
                'error_message' => 'Unable to load dashboard data. Please try again later.',
                'error_details' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null,
            ]);

            $response->getBody()->write($html);
            return $response->withStatus(500);
        }
    }
}
