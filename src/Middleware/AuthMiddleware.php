<?php
declare(strict_types=1);

namespace ClassCRUD\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // For MVP, we'll implement basic session-based authentication
        // In production, you'd want to use JWT or more robust authentication
        
        session_start();
        
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            // Redirect to login page
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        
        // Add user info to request attributes for use in controllers
        $request = $request->withAttribute('user_id', $_SESSION['user_id']);
        $request = $request->withAttribute('user_name', $_SESSION['user_name'] ?? 'Unknown');
        
        return $handler->handle($request);
    }
}
