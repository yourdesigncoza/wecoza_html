<?php
declare(strict_types=1);

namespace ClassCRUD\Services;

use ClassCRUD\Models\ClassModel;
use ClassCRUD\Repositories\ClassRepository;

class ClassService
{
    private ClassRepository $classRepository;

    public function __construct(ClassRepository $classRepository)
    {
        $this->classRepository = $classRepository;
    }

    /**
     * Get all classes with optional filtering and pagination
     */
    public function getAllClasses(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        return $this->classRepository->findAll($filters, $limit, $offset);
    }

    /**
     * Get total count of classes with optional filtering
     */
    public function getClassCount(array $filters = []): int
    {
        return $this->classRepository->count($filters);
    }

    /**
     * Get a class by ID
     */
    public function getClassById(int $id): ?ClassModel
    {
        return $this->classRepository->findById($id);
    }

    /**
     * Get a class by class code
     */
    public function getClassByCode(string $classCode): ?ClassModel
    {
        return $this->classRepository->findByClassCode($classCode);
    }

    /**
     * Create a new class
     */
    public function createClass(ClassModel $class): ClassModel
    {
        // Additional business logic validation
        $this->validateClassBusinessRules($class, 'create');
        
        return $this->classRepository->create($class);
    }

    /**
     * Update an existing class
     */
    public function updateClass(ClassModel $class): ClassModel
    {
        // Additional business logic validation
        $this->validateClassBusinessRules($class, 'update');
        
        return $this->classRepository->update($class);
    }

    /**
     * Delete a class
     */
    public function deleteClass(int $id): bool
    {
        // Check if class exists
        $class = $this->getClassById($id);
        if (!$class) {
            return false;
        }

        // Additional business logic checks before deletion
        // For example, check if class has started, has learners enrolled, etc.
        
        return $this->classRepository->delete($id);
    }

    /**
     * Check if a class code already exists
     */
    public function classCodeExists(string $classCode, ?int $excludeId = null): bool
    {
        return $this->classRepository->classCodeExists($classCode, $excludeId);
    }

    /**
     * Get classes by agent ID
     */
    public function getClassesByAgent(int $agentId): array
    {
        return $this->classRepository->findByAgentId($agentId);
    }

    /**
     * Get classes by supervisor ID
     */
    public function getClassesBySupervisor(int $supervisorId): array
    {
        return $this->classRepository->findBySupervisorId($supervisorId);
    }

    /**
     * Get classes by client ID
     */
    public function getClassesByClient(int $clientId): array
    {
        return $this->classRepository->findByClientId($clientId);
    }

    /**
     * Get classes by learner ID
     */
    public function getClassesByLearner(int $learnerId): array
    {
        return $this->classRepository->findByLearnerId($learnerId);
    }

    /**
     * Get classes within a date range
     */
    public function getClassesByDateRange(string $startDate, string $endDate): array
    {
        return $this->classRepository->findByDateRange($startDate, $endDate);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStatistics(): array
    {
        return $this->classRepository->getStatistics();
    }

    /**
     * Validate business rules for class operations
     */
    private function validateClassBusinessRules(ClassModel $class, string $operation): void
    {
        $errors = [];

        // Business rule: Class duration must be reasonable (between 1 and 365 days)
        if ($class->getClassDuration() !== null) {
            if ($class->getClassDuration() < 1 || $class->getClassDuration() > 365) {
                $errors[] = 'Class duration must be between 1 and 365 days';
            }
        }

        // Business rule: Start date cannot be in the past for new classes
        if ($operation === 'create' && $class->getOriginalStartDate()) {
            $startDate = new \DateTime($class->getOriginalStartDate());
            $today = new \DateTime('today');
            
            if ($startDate < $today) {
                $errors[] = 'Start date cannot be in the past';
            }
        }

        // Business rule: If SETA funded, SETA must be specified
        if ($class->isSetaFunded() === true && empty($class->getSeta())) {
            $errors[] = 'SETA must be specified when class is SETA funded';
        }

        // Business rule: If exam class, exam type must be specified
        if ($class->isExamClass() === true && empty($class->getExamType())) {
            $errors[] = 'Exam type must be specified when class is an exam class';
        }

        // Business rule: Class code format validation (example: must be alphanumeric)
        if ($class->getClassCode()) {
            if (!preg_match('/^[A-Z0-9\-_]+$/i', $class->getClassCode())) {
                $errors[] = 'Class code must contain only letters, numbers, hyphens, and underscores';
            }
        }

        // Business rule: Delivery date must be after start date
        if ($class->getOriginalStartDate() && $class->getDeliveryDate()) {
            $startDate = new \DateTime($class->getOriginalStartDate());
            $deliveryDate = new \DateTime($class->getDeliveryDate());
            
            if ($deliveryDate <= $startDate) {
                $errors[] = 'Delivery date must be after the start date';
            }
        }

        // Business rule: Maximum number of learners per class (example: 30)
        if (count($class->getLearnerIds()) > 30) {
            $errors[] = 'Maximum of 30 learners allowed per class';
        }

        // Business rule: Maximum number of backup agents (example: 5)
        if (count($class->getBackupAgentIds()) > 5) {
            $errors[] = 'Maximum of 5 backup agents allowed per class';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode('; ', $errors));
        }
    }

    /**
     * Generate a unique class code based on client and class type
     */
    public function generateClassCode(int $clientId, string $classType): string
    {
        $prefix = strtoupper(substr($classType, 0, 3));
        $clientPrefix = str_pad((string)$clientId, 3, '0', STR_PAD_LEFT);
        
        // Find the next available sequence number
        $sequence = 1;
        do {
            $classCode = sprintf('%s-%s-%04d', $prefix, $clientPrefix, $sequence);
            $sequence++;
        } while ($this->classCodeExists($classCode));
        
        return $classCode;
    }

    /**
     * Calculate class end date based on start date and duration
     */
    public function calculateEndDate(string $startDate, int $durationDays): string
    {
        $start = new \DateTime($startDate);
        $start->add(new \DateInterval("P{$durationDays}D"));
        return $start->format('Y-m-d');
    }

    /**
     * Get class progress percentage (example calculation)
     */
    public function getClassProgress(ClassModel $class): float
    {
        if (!$class->getOriginalStartDate() || !$class->getClassDuration()) {
            return 0.0;
        }

        $startDate = new \DateTime($class->getOriginalStartDate());
        $today = new \DateTime();
        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P' . $class->getClassDuration() . 'D'));

        if ($today < $startDate) {
            return 0.0; // Not started yet
        }

        if ($today >= $endDate) {
            return 100.0; // Completed
        }

        $totalDays = $startDate->diff($endDate)->days;
        $elapsedDays = $startDate->diff($today)->days;

        return ($elapsedDays / $totalDays) * 100;
    }

    /**
     * Check if a class is currently active
     */
    public function isClassActive(ClassModel $class): bool
    {
        if (!$class->getOriginalStartDate() || !$class->getClassDuration()) {
            return false;
        }

        $startDate = new \DateTime($class->getOriginalStartDate());
        $today = new \DateTime();
        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P' . $class->getClassDuration() . 'D'));

        return $today >= $startDate && $today < $endDate;
    }

    /**
     * Get upcoming classes (starting within next 30 days)
     */
    public function getUpcomingClasses(): array
    {
        $today = date('Y-m-d');
        $futureDate = date('Y-m-d', strtotime('+30 days'));
        
        return $this->getClassesByDateRange($today, $futureDate);
    }

    /**
     * Export classes to array format for CSV/Excel export
     */
    public function exportClassesToArray(array $filters = []): array
    {
        $classes = $this->getAllClasses($filters, 1000, 0); // Get up to 1000 classes
        $exportData = [];

        foreach ($classes as $class) {
            $exportData[] = [
                'Class ID' => $class->getClassId(),
                'Client ID' => $class->getClientId(),
                'Site ID' => $class->getSiteId(),
                'Class Type' => $class->getClassType(),
                'Class Subject' => $class->getClassSubject(),
                'Class Code' => $class->getClassCode(),
                'Duration (Days)' => $class->getClassDuration(),
                'Start Date' => $class->getOriginalStartDate(),
                'SETA Funded' => $class->isSetaFunded() ? 'Yes' : 'No',
                'SETA' => $class->getSeta(),
                'Exam Class' => $class->isExamClass() ? 'Yes' : 'No',
                'Exam Type' => $class->getExamType(),
                'Class Agent' => $class->getClassAgent(),
                'Supervisor' => $class->getProjectSupervisorId(),
                'Delivery Date' => $class->getDeliveryDate(),
                'Learner Count' => count($class->getLearnerIds()),
                'Created At' => $class->getCreatedAt(),
                'Updated At' => $class->getUpdatedAt(),
            ];
        }

        return $exportData;
    }
}
