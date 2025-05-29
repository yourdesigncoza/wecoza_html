<?php
declare(strict_types=1);

namespace ClassCRUD\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ClassCRUD\Services\ClassService;
use ClassCRUD\Services\LocalDataService;
use ClassCRUD\Models\ClassModel;

class ApiController
{
    private ClassService $classService;
    private LocalDataService $localDataService;

    public function __construct(ClassService $classService, LocalDataService $localDataService)
    {
        $this->classService = $classService;
        $this->localDataService = $localDataService;
    }

    /**
     * Get all classes (JSON API)
     */
    public function getClasses(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            
            // Get filters from query parameters
            $filters = [
                'client_id' => $queryParams['client_id'] ?? null,
                'class_type' => $queryParams['class_type'] ?? null,
                'class_agent' => $queryParams['class_agent'] ?? null,
                'project_supervisor_id' => $queryParams['project_supervisor_id'] ?? null,
                'search' => $queryParams['search'] ?? null,
            ];

            // Remove empty filters
            $filters = array_filter($filters, fn($value) => !empty($value));

            // Pagination
            $page = max(1, (int)($queryParams['page'] ?? 1));
            $limit = min(100, max(1, (int)($queryParams['limit'] ?? 20))); // Max 100, min 1
            $offset = ($page - 1) * $limit;

            // Get classes and total count
            $classes = $this->classService->getAllClasses($filters, $limit, $offset);
            $totalClasses = $this->classService->getClassCount($filters);
            $totalPages = ceil($totalClasses / $limit);

            $data = [
                'success' => true,
                'data' => $classes,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalClasses,
                    'limit' => $limit,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1,
                ],
                'filters' => $filters,
            ];

            return $this->jsonResponse($response, $data);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to retrieve classes', $e->getMessage());
        }
    }

    /**
     * Get single class by ID (JSON API)
     */
    public function getClass(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)$args['id'];
            $class = $this->classService->getClassById($id);

            if (!$class) {
                return $this->errorResponse($response, 'Class not found', null, 404);
            }

            $data = [
                'success' => true,
                'data' => $class,
            ];

            return $this->jsonResponse($response, $data);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to retrieve class', $e->getMessage());
        }
    }

    /**
     * Create new class (JSON API)
     */
    public function createClass(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Create class model from JSON data
            $class = new ClassModel($data);
            
            // Validate for CREATE mode
            $errors = $class->validateForCreate();
            
            if (!empty($errors)) {
                return $this->errorResponse($response, 'Validation failed', $errors, 400);
            }

            // Check if class code already exists
            if ($this->classService->classCodeExists($class->getClassCode())) {
                return $this->errorResponse($response, 'Class code already exists', null, 400);
            }

            // Create the class
            $createdClass = $this->classService->createClass($class);

            $responseData = [
                'success' => true,
                'message' => 'Class created successfully',
                'data' => $createdClass,
            ];

            return $this->jsonResponse($response, $responseData, 201);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to create class', $e->getMessage());
        }
    }

    /**
     * Update existing class (JSON API)
     */
    public function updateClass(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)$args['id'];
            $data = $request->getParsedBody();
            
            $existingClass = $this->classService->getClassById($id);
            
            if (!$existingClass) {
                return $this->errorResponse($response, 'Class not found', null, 404);
            }

            // Update class model with JSON data
            $class = new ClassModel($data);
            $class->setClassId($id);
            
            // Validate for UPDATE mode
            $errors = $class->validateForUpdate();
            
            if (!empty($errors)) {
                return $this->errorResponse($response, 'Validation failed', $errors, 400);
            }

            // Check if class code already exists (excluding current class)
            if ($class->getClassCode() && 
                $this->classService->classCodeExists($class->getClassCode(), $id)) {
                return $this->errorResponse($response, 'Class code already exists', null, 400);
            }

            // Update the class
            $updatedClass = $this->classService->updateClass($class);

            $responseData = [
                'success' => true,
                'message' => 'Class updated successfully',
                'data' => $updatedClass,
            ];

            return $this->jsonResponse($response, $responseData);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to update class', $e->getMessage());
        }
    }

    /**
     * Delete class (JSON API)
     */
    public function deleteClass(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)$args['id'];
            
            $deleted = $this->classService->deleteClass($id);
            
            if (!$deleted) {
                return $this->errorResponse($response, 'Class not found', null, 404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Class deleted successfully',
            ];

            return $this->jsonResponse($response, $responseData);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to delete class', $e->getMessage());
        }
    }

    /**
     * Get clients (JSON API)
     */
    public function getClients(Request $request, Response $response): Response
    {
        try {
            $clients = $this->localDataService->getClients();
            
            $data = [
                'success' => true,
                'data' => $clients,
            ];

            return $this->jsonResponse($response, $data);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to retrieve clients', $e->getMessage());
        }
    }

    /**
     * Get agents (JSON API)
     */
    public function getAgents(Request $request, Response $response): Response
    {
        try {
            $agents = $this->localDataService->getAgents();
            
            $data = [
                'success' => true,
                'data' => $agents,
            ];

            return $this->jsonResponse($response, $data);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to retrieve agents', $e->getMessage());
        }
    }

    /**
     * Get supervisors (JSON API)
     */
    public function getSupervisors(Request $request, Response $response): Response
    {
        try {
            $supervisors = $this->localDataService->getSupervisors();
            
            $data = [
                'success' => true,
                'data' => $supervisors,
            ];

            return $this->jsonResponse($response, $data);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to retrieve supervisors', $e->getMessage());
        }
    }

    /**
     * Get learners (JSON API)
     */
    public function getLearners(Request $request, Response $response): Response
    {
        try {
            $learners = $this->localDataService->getLearners();
            
            $data = [
                'success' => true,
                'data' => $learners,
            ];

            return $this->jsonResponse($response, $data);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to retrieve learners', $e->getMessage());
        }
    }

    /**
     * Get class types (JSON API)
     */
    public function getClassTypes(Request $request, Response $response): Response
    {
        try {
            $classTypes = $this->localDataService->getClassTypes();
            
            $data = [
                'success' => true,
                'data' => $classTypes,
            ];

            return $this->jsonResponse($response, $data);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to retrieve class types', $e->getMessage());
        }
    }

    /**
     * Get class subjects by type (JSON API)
     */
    public function getClassSubjects(Request $request, Response $response, array $args): Response
    {
        try {
            $type = $args['type'] ?? null;
            $subjects = $this->localDataService->getClassSubjects($type);
            
            $data = [
                'success' => true,
                'data' => $subjects,
                'type' => $type,
            ];

            return $this->jsonResponse($response, $data);

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to retrieve class subjects', $e->getMessage());
        }
    }

    /**
     * Helper method to return JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($json);
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * Helper method to return error response
     */
    private function errorResponse(Response $response, string $message, $details = null, int $status = 500): Response
    {
        $data = [
            'success' => false,
            'error' => $message,
        ];

        if ($details !== null) {
            $data['details'] = $details;
        }

        if ($_ENV['APP_DEBUG'] === 'true' && $details instanceof \Exception) {
            $data['debug'] = [
                'file' => $details->getFile(),
                'line' => $details->getLine(),
                'trace' => $details->getTraceAsString(),
            ];
        }

        return $this->jsonResponse($response, $data, $status);
    }
}
