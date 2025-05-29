<?php
declare(strict_types=1);

namespace ClassCRUD\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CSRFMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // For MVP, we'll implement a basic CSRF protection
        // In production, you'd want to use a more robust solution
        
        $method = $request->getMethod();
        
        // Only check CSRF for state-changing methods
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // For now, we'll skip CSRF validation in MVP
            // TODO: Implement proper CSRF token validation
        }
        
        return $handler->handle($request);
    }
}
