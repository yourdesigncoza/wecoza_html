<?php
declare(strict_types=1);

namespace ClassCRUD\Repositories;

use ClassCRUD\Models\ClassModel;
use ClassCRUD\Services\DatabaseService;
use PDO;

class ClassRepository
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    /**
     * Find all classes with optional filtering and pagination
     */
    public function findAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM classes WHERE 1=1";
        $params = [];

        // Add filters
        if (!empty($filters['client_id'])) {
            $sql .= " AND client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }

        if (!empty($filters['class_type'])) {
            $sql .= " AND class_type = :class_type";
            $params['class_type'] = $filters['class_type'];
        }

        if (!empty($filters['class_agent'])) {
            $sql .= " AND class_agent = :class_agent";
            $params['class_agent'] = $filters['class_agent'];
        }

        if (!empty($filters['project_supervisor_id'])) {
            $sql .= " AND project_supervisor_id = :project_supervisor_id";
            $params['project_supervisor_id'] = $filters['project_supervisor_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (class_code ILIKE :search OR class_subject ILIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $rows = $this->db->queryAll($sql, $params);
        
        return array_map(function ($row) {
            return new ClassModel($row);
        }, $rows);
    }

    /**
     * Count total classes with optional filtering
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM classes WHERE 1=1";
        $params = [];

        // Add same filters as findAll
        if (!empty($filters['client_id'])) {
            $sql .= " AND client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }

        if (!empty($filters['class_type'])) {
            $sql .= " AND class_type = :class_type";
            $params['class_type'] = $filters['class_type'];
        }

        if (!empty($filters['class_agent'])) {
            $sql .= " AND class_agent = :class_agent";
            $params['class_agent'] = $filters['class_agent'];
        }

        if (!empty($filters['project_supervisor_id'])) {
            $sql .= " AND project_supervisor_id = :project_supervisor_id";
            $params['project_supervisor_id'] = $filters['project_supervisor_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (class_code ILIKE :search OR class_subject ILIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $result = $this->db->queryOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Find a class by ID
     */
    public function findById(int $id): ?ClassModel
    {
        $sql = "SELECT * FROM classes WHERE class_id = :id";
        $row = $this->db->queryOne($sql, ['id' => $id]);
        
        return $row ? new ClassModel($row) : null;
    }

    /**
     * Find a class by class code
     */
    public function findByClassCode(string $classCode): ?ClassModel
    {
        $sql = "SELECT * FROM classes WHERE class_code = :class_code";
        $row = $this->db->queryOne($sql, ['class_code' => $classCode]);
        
        return $row ? new ClassModel($row) : null;
    }

    /**
     * Create a new class
     */
    public function create(ClassModel $class): ClassModel
    {
        $data = $class->toInsertArray();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = sprintf(
            "INSERT INTO classes (%s) VALUES (%s) RETURNING class_id",
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $result = $this->db->queryOne($sql, $data);
        $class->setClassId((int)$result['class_id']);
        $class->setCreatedAt($data['created_at']);
        $class->setUpdatedAt($data['updated_at']);

        return $class;
    }

    /**
     * Update an existing class
     */
    public function update(ClassModel $class): ClassModel
    {
        $data = $class->toUpdateArray();
        $data['updated_at'] = date('Y-m-d H:i:s');

        $setParts = array_map(fn($col) => "$col = :$col", array_keys($data));
        $sql = sprintf(
            "UPDATE classes SET %s WHERE class_id = :class_id",
            implode(', ', $setParts)
        );

        $data['class_id'] = $class->getClassId();
        $this->db->query($sql, $data);
        $class->setUpdatedAt($data['updated_at']);

        return $class;
    }

    /**
     * Delete a class by ID
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM classes WHERE class_id = :id";
        $stmt = $this->db->query($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if a class code already exists (for validation)
     */
    public function classCodeExists(string $classCode, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM classes WHERE class_code = :class_code";
        $params = ['class_code' => $classCode];

        if ($excludeId !== null) {
            $sql .= " AND class_id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $result = $this->db->queryOne($sql, $params);
        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Get classes by agent ID
     */
    public function findByAgentId(int $agentId): array
    {
        $sql = "SELECT * FROM classes WHERE class_agent = :agent_id OR initial_class_agent = :agent_id
                OR backup_agent_ids::jsonb ? :agent_id_str ORDER BY created_at DESC";

        $params = [
            'agent_id' => $agentId,
            'agent_id_str' => (string)$agentId
        ];

        $rows = $this->db->queryAll($sql, $params);

        return array_map(function ($row) {
            return new ClassModel($row);
        }, $rows);
    }

    /**
     * Get classes by supervisor ID
     */
    public function findBySupervisorId(int $supervisorId): array
    {
        $sql = "SELECT * FROM classes WHERE project_supervisor_id = :supervisor_id ORDER BY created_at DESC";
        $rows = $this->db->queryAll($sql, ['supervisor_id' => $supervisorId]);
        
        return array_map(function ($row) {
            return new ClassModel($row);
        }, $rows);
    }

    /**
     * Get classes by client ID
     */
    public function findByClientId(int $clientId): array
    {
        $sql = "SELECT * FROM classes WHERE client_id = :client_id ORDER BY created_at DESC";
        $rows = $this->db->queryAll($sql, ['client_id' => $clientId]);
        
        return array_map(function ($row) {
            return new ClassModel($row);
        }, $rows);
    }

    /**
     * Get classes that have a specific learner
     */
    public function findByLearnerId(int $learnerId): array
    {
        $sql = "SELECT * FROM classes WHERE learner_ids::jsonb ? :learner_id_str ORDER BY created_at DESC";
        $rows = $this->db->queryAll($sql, ['learner_id_str' => (string)$learnerId]);

        return array_map(function ($row) {
            return new ClassModel($row);
        }, $rows);
    }

    /**
     * Get classes starting within a date range
     */
    public function findByDateRange(string $startDate, string $endDate): array
    {
        $sql = "SELECT * FROM classes WHERE original_start_date BETWEEN :start_date AND :end_date 
                ORDER BY original_start_date ASC";
        
        $rows = $this->db->queryAll($sql, [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return array_map(function ($row) {
            return new ClassModel($row);
        }, $rows);
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Total classes
        $result = $this->db->queryOne("SELECT COUNT(*) as total FROM classes");
        $stats['total_classes'] = (int)($result['total'] ?? 0);

        // Classes by type
        $result = $this->db->queryAll("SELECT class_type, COUNT(*) as count FROM classes GROUP BY class_type");
        $stats['by_type'] = array_column($result, 'count', 'class_type');

        // Classes by month (last 12 months)
        $sql = "SELECT DATE_TRUNC('month', created_at) as month, COUNT(*) as count 
                FROM classes 
                WHERE created_at >= NOW() - INTERVAL '12 months' 
                GROUP BY month 
                ORDER BY month";
        $result = $this->db->queryAll($sql);
        $stats['by_month'] = $result;

        // SETA funded vs non-funded
        $result = $this->db->queryAll("SELECT seta_funded, COUNT(*) as count FROM classes GROUP BY seta_funded");
        $stats['seta_funded'] = array_column($result, 'count', 'seta_funded');

        return $stats;
    }
}
