<?php
declare(strict_types=1);

namespace ClassCRUD\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ClassCRUD\Services\ClassService;
use ClassCRUD\Services\LocalDataService;
use ClassCRUD\Models\ClassModel;
use Twig\Environment;

class ClassController
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
     * Display list of classes
     */
    public function index(Request $request, Response $response): Response
    {
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
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Get classes and total count
        $classes = $this->classService->getAllClasses($filters, $limit, $offset);
        $totalClasses = $this->classService->getClassCount($filters);
        $totalPages = ceil($totalClasses / $limit);

        // Get reference data for filters
        $clients = $this->localDataService->getClients();
        $classTypes = $this->localDataService->getClassTypes();
        $agents = $this->localDataService->getAgents();
        $supervisors = $this->localDataService->getSupervisors();

        $html = $this->twig->render('classes/index.twig', [
            'classes' => $classes,
            'filters' => $filters,
            'clients' => $clients,
            'class_types' => $classTypes,
            'agents' => $agents,
            'supervisors' => $supervisors,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalClasses,
                'limit' => $limit,
            ],
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Show create class form
     */
    public function create(Request $request, Response $response): Response
    {
        // Initialize default data files if they don't exist
        $this->localDataService->initializeDefaultData();

        // Get all reference data for the form
        $formData = [
            'clients' => $this->localDataService->getClients(),
            'sites' => $this->localDataService->getSites(),
            'agents' => $this->localDataService->getAgents(),
            'supervisors' => $this->localDataService->getSupervisors(),
            'learners' => $this->localDataService->getLearners(),
            'setas' => $this->localDataService->getSetas(),
            'class_types' => $this->localDataService->getClassTypes(),
            'class_subjects' => $this->localDataService->getClassSubjects(),
            'yes_no_options' => $this->localDataService->getYesNoOptions(),
            'class_notes_options' => $this->localDataService->getClassNotesOptions(),
            'exam_types' => $this->localDataService->getExamTypes(),
            'public_holidays' => $this->localDataService->getPublicHolidays(),
        ];

        $html = $this->twig->render('classes/create.twig', [
            'form_data' => $formData,
            'mode' => 'create',
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Store new class
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        try {
            // Create class model from form data
            $class = new ClassModel($data);
            
            // Validate for CREATE mode
            $errors = $class->validateForCreate();
            
            if (!empty($errors)) {
                // Return to form with errors
                return $this->returnToCreateForm($response, $data, $errors);
            }

            // Check if class code already exists
            if ($this->classService->classCodeExists($class->getClassCode())) {
                $errors['class_code'] = 'Class code already exists';
                return $this->returnToCreateForm($response, $data, $errors);
            }

            // Create the class
            $createdClass = $this->classService->createClass($class);

            // Redirect to class detail page with success message
            $response = $response->withHeader('Location', '/classes/' . $createdClass->getClassId())
                               ->withStatus(302);
            
            return $response;

        } catch (\Exception $e) {
            $errors = ['general' => 'An error occurred while creating the class: ' . $e->getMessage()];
            return $this->returnToCreateForm($response, $data, $errors);
        }
    }

    /**
     * Show class details
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $class = $this->classService->getClassById($id);

        if (!$class) {
            $response = $response->withStatus(404);
            $response->getBody()->write('Class not found');
            return $response;
        }

        // Get reference data for display
        $clients = $this->localDataService->getClients();
        $agents = $this->localDataService->getAgents();
        $supervisors = $this->localDataService->getSupervisors();
        $learners = $this->localDataService->getLearners();

        $html = $this->twig->render('classes/show.twig', [
            'class' => $class,
            'clients' => $clients,
            'agents' => $agents,
            'supervisors' => $supervisors,
            'learners' => $learners,
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Show edit class form
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $class = $this->classService->getClassById($id);

        if (!$class) {
            $response = $response->withStatus(404);
            $response->getBody()->write('Class not found');
            return $response;
        }

        // Get all reference data for the form
        $formData = [
            'clients' => $this->localDataService->getClients(),
            'sites' => $this->localDataService->getSites(),
            'agents' => $this->localDataService->getAgents(),
            'supervisors' => $this->localDataService->getSupervisors(),
            'learners' => $this->localDataService->getLearners(),
            'setas' => $this->localDataService->getSetas(),
            'class_types' => $this->localDataService->getClassTypes(),
            'class_subjects' => $this->localDataService->getClassSubjects(),
            'yes_no_options' => $this->localDataService->getYesNoOptions(),
            'class_notes_options' => $this->localDataService->getClassNotesOptions(),
            'exam_types' => $this->localDataService->getExamTypes(),
            'public_holidays' => $this->localDataService->getPublicHolidays(),
        ];

        $html = $this->twig->render('classes/edit.twig', [
            'class' => $class,
            'form_data' => $formData,
            'mode' => 'update',
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Update existing class
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        
        try {
            $existingClass = $this->classService->getClassById($id);
            
            if (!$existingClass) {
                $response = $response->withStatus(404);
                $response->getBody()->write('Class not found');
                return $response;
            }

            // Update class model with form data
            $class = new ClassModel($data);
            $class->setClassId($id);
            
            // Validate for UPDATE mode
            $errors = $class->validateForUpdate();
            
            if (!empty($errors)) {
                // Return to form with errors
                return $this->returnToEditForm($response, $id, $data, $errors);
            }

            // Check if class code already exists (excluding current class)
            if ($class->getClassCode() && 
                $this->classService->classCodeExists($class->getClassCode(), $id)) {
                $errors['class_code'] = 'Class code already exists';
                return $this->returnToEditForm($response, $id, $data, $errors);
            }

            // Update the class
            $updatedClass = $this->classService->updateClass($class);

            // Redirect to class detail page with success message
            $response = $response->withHeader('Location', '/classes/' . $updatedClass->getClassId())
                               ->withStatus(302);
            
            return $response;

        } catch (\Exception $e) {
            $errors = ['general' => 'An error occurred while updating the class: ' . $e->getMessage()];
            return $this->returnToEditForm($response, $id, $data, $errors);
        }
    }

    /**
     * Delete class
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        
        try {
            $deleted = $this->classService->deleteClass($id);
            
            if ($deleted) {
                // Redirect to classes list with success message
                $response = $response->withHeader('Location', '/classes')
                                   ->withStatus(302);
            } else {
                $response = $response->withStatus(404);
                $response->getBody()->write('Class not found');
            }
            
            return $response;

        } catch (\Exception $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write('An error occurred while deleting the class: ' . $e->getMessage());
            return $response;
        }
    }

    /**
     * Helper method to return to create form with errors
     */
    private function returnToCreateForm(Response $response, array $data, array $errors): Response
    {
        $formData = [
            'clients' => $this->localDataService->getClients(),
            'sites' => $this->localDataService->getSites(),
            'agents' => $this->localDataService->getAgents(),
            'supervisors' => $this->localDataService->getSupervisors(),
            'learners' => $this->localDataService->getLearners(),
            'setas' => $this->localDataService->getSetas(),
            'class_types' => $this->localDataService->getClassTypes(),
            'class_subjects' => $this->localDataService->getClassSubjects(),
            'yes_no_options' => $this->localDataService->getYesNoOptions(),
            'class_notes_options' => $this->localDataService->getClassNotesOptions(),
            'exam_types' => $this->localDataService->getExamTypes(),
            'public_holidays' => $this->localDataService->getPublicHolidays(),
        ];

        $html = $this->twig->render('classes/create.twig', [
            'form_data' => $formData,
            'form_values' => $data,
            'errors' => $errors,
            'mode' => 'create',
        ]);

        $response->getBody()->write($html);
        return $response->withStatus(400);
    }

    /**
     * Helper method to return to edit form with errors
     */
    private function returnToEditForm(Response $response, int $id, array $data, array $errors): Response
    {
        $class = new ClassModel($data);
        $class->setClassId($id);

        $formData = [
            'clients' => $this->localDataService->getClients(),
            'sites' => $this->localDataService->getSites(),
            'agents' => $this->localDataService->getAgents(),
            'supervisors' => $this->localDataService->getSupervisors(),
            'learners' => $this->localDataService->getLearners(),
            'setas' => $this->localDataService->getSetas(),
            'class_types' => $this->localDataService->getClassTypes(),
            'class_subjects' => $this->localDataService->getClassSubjects(),
            'yes_no_options' => $this->localDataService->getYesNoOptions(),
            'class_notes_options' => $this->localDataService->getClassNotesOptions(),
            'exam_types' => $this->localDataService->getExamTypes(),
            'public_holidays' => $this->localDataService->getPublicHolidays(),
        ];

        $html = $this->twig->render('classes/edit.twig', [
            'class' => $class,
            'form_data' => $formData,
            'errors' => $errors,
            'mode' => 'update',
        ]);

        $response->getBody()->write($html);
        return $response->withStatus(400);
    }
}
