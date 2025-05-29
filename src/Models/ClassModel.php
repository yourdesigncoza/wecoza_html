<?php
declare(strict_types=1);

namespace ClassCRUD\Models;

use JsonSerializable;

class ClassModel implements JsonSerializable
{
    private ?int $classId = null;
    private ?int $clientId = null;
    private ?int $siteId = null;
    private ?string $classAddressLine = null;
    private ?string $classType = null;
    private ?string $classSubject = null;
    private ?string $classCode = null;
    private ?int $classDuration = null;
    private ?string $originalStartDate = null;
    private ?bool $setaFunded = null;
    private ?string $seta = null;
    private ?bool $examClass = null;
    private ?string $examType = null;
    private ?string $qaVisitDates = null;
    private ?int $classAgent = null;
    private ?int $initialClassAgent = null;
    private ?string $initialAgentStartDate = null;
    private ?int $projectSupervisorId = null;
    private ?string $deliveryDate = null;
    private array $learnerIds = [];
    private array $backupAgentIds = [];
    private array $scheduleData = [];
    private array $stopRestartDates = [];
    private array $classNotesData = [];
    private array $qaReports = [];
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    /**
     * Hydrate model from database row or form data
     */
    private function hydrate(array $data): void
    {
        $this->classId = isset($data['class_id']) ? (int)$data['class_id'] : null;
        $this->clientId = isset($data['client_id']) ? (int)$data['client_id'] : null;
        $this->siteId = isset($data['site_id']) ? (int)$data['site_id'] : null;
        $this->classAddressLine = $data['class_address_line'] ?? null;
        $this->classType = $data['class_type'] ?? null;
        $this->classSubject = $data['class_subject'] ?? null;
        $this->classCode = $data['class_code'] ?? null;
        $this->classDuration = isset($data['class_duration']) ? (int)$data['class_duration'] : null;
        $this->originalStartDate = $data['original_start_date'] ?? null;
        $this->setaFunded = isset($data['seta_funded']) ? (bool)$data['seta_funded'] : null;
        $this->seta = $data['seta'] ?? null;
        $this->examClass = isset($data['exam_class']) ? (bool)$data['exam_class'] : null;
        $this->examType = $data['exam_type'] ?? null;
        $this->qaVisitDates = $data['qa_visit_dates'] ?? null;
        $this->classAgent = isset($data['class_agent']) ? (int)$data['class_agent'] : null;
        $this->initialClassAgent = isset($data['initial_class_agent']) ? (int)$data['initial_class_agent'] : null;
        $this->initialAgentStartDate = $data['initial_agent_start_date'] ?? null;
        $this->projectSupervisorId = isset($data['project_supervisor_id']) ? (int)$data['project_supervisor_id'] : null;
        $this->deliveryDate = $data['delivery_date'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;

        // Handle JSONB fields
        $this->learnerIds = $this->parseJsonField($data['learner_ids'] ?? []);
        $this->backupAgentIds = $this->parseJsonField($data['backup_agent_ids'] ?? []);
        $this->scheduleData = $this->parseJsonField($data['schedule_data'] ?? []);
        $this->stopRestartDates = $this->parseJsonField($data['stop_restart_dates'] ?? []);
        $this->classNotesData = $this->parseJsonField($data['class_notes_data'] ?? []);
        $this->qaReports = $this->parseJsonField($data['qa_reports'] ?? []);
    }

    /**
     * Parse JSON field from database or form data
     */
    private function parseJsonField(mixed $field): array
    {
        if (is_string($field)) {
            $decoded = json_decode($field, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($field) ? $field : [];
    }

    /**
     * Convert model to array for database storage
     */
    public function toArray(): array
    {
        return [
            'class_id' => $this->classId,
            'client_id' => $this->clientId,
            'site_id' => $this->siteId,
            'class_address_line' => $this->classAddressLine,
            'class_type' => $this->classType,
            'class_subject' => $this->classSubject,
            'class_code' => $this->classCode,
            'class_duration' => $this->classDuration,
            'original_start_date' => $this->originalStartDate,
            'seta_funded' => $this->setaFunded,
            'seta' => $this->seta,
            'exam_class' => $this->examClass,
            'exam_type' => $this->examType,
            'qa_visit_dates' => $this->qaVisitDates,
            'class_agent' => $this->classAgent,
            'initial_class_agent' => $this->initialClassAgent,
            'initial_agent_start_date' => $this->initialAgentStartDate,
            'project_supervisor_id' => $this->projectSupervisorId,
            'delivery_date' => $this->deliveryDate,
            'learner_ids' => json_encode($this->learnerIds),
            'backup_agent_ids' => json_encode($this->backupAgentIds),
            'schedule_data' => json_encode($this->scheduleData),
            'stop_restart_dates' => json_encode($this->stopRestartDates),
            'class_notes_data' => json_encode($this->classNotesData),
            'qa_reports' => json_encode($this->qaReports),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Convert model to array for database INSERT (without ID and timestamps)
     */
    public function toInsertArray(): array
    {
        $data = $this->toArray();
        unset($data['class_id'], $data['created_at'], $data['updated_at']);
        return $data;
    }

    /**
     * Convert model to array for database UPDATE (without ID and created_at)
     */
    public function toUpdateArray(): array
    {
        $data = $this->toArray();
        unset($data['class_id'], $data['created_at']);
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    /**
     * JSON serialization
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Validate model data for CREATE mode
     */
    public function validateForCreate(): array
    {
        $errors = [];

        // Required fields for CREATE mode (based on WEC-74)
        if (empty($this->clientId)) {
            $errors['client_id'] = 'Client is required';
        }

        if (empty($this->classType)) {
            $errors['class_type'] = 'Class type is required';
        }

        if (empty($this->classSubject)) {
            $errors['class_subject'] = 'Class subject is required';
        }

        if (empty($this->classCode)) {
            $errors['class_code'] = 'Class code is required';
        }

        if (empty($this->classDuration)) {
            $errors['class_duration'] = 'Class duration is required';
        }

        if (empty($this->originalStartDate)) {
            $errors['original_start_date'] = 'Start date is required';
        }

        if (empty($this->classAgent)) {
            $errors['class_agent'] = 'Class agent is required';
        }

        if (empty($this->projectSupervisorId)) {
            $errors['project_supervisor_id'] = 'Project supervisor is required';
        }

        return $errors;
    }

    /**
     * Validate model data for UPDATE mode
     */
    public function validateForUpdate(): array
    {
        $errors = [];

        // For UPDATE mode, most fields are optional
        // Only validate if values are provided

        if ($this->classDuration !== null && $this->classDuration <= 0) {
            $errors['class_duration'] = 'Class duration must be greater than 0';
        }

        if ($this->originalStartDate !== null && !$this->isValidDate($this->originalStartDate)) {
            $errors['original_start_date'] = 'Invalid start date format';
        }

        return $errors;
    }

    /**
     * Check if a date string is valid
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    // Getters and Setters
    public function getClassId(): ?int { return $this->classId; }
    public function setClassId(?int $classId): self { $this->classId = $classId; return $this; }

    public function getClientId(): ?int { return $this->clientId; }
    public function setClientId(?int $clientId): self { $this->clientId = $clientId; return $this; }

    public function getSiteId(): ?int { return $this->siteId; }
    public function setSiteId(?int $siteId): self { $this->siteId = $siteId; return $this; }

    public function getClassAddressLine(): ?string { return $this->classAddressLine; }
    public function setClassAddressLine(?string $classAddressLine): self { $this->classAddressLine = $classAddressLine; return $this; }

    public function getClassType(): ?string { return $this->classType; }
    public function setClassType(?string $classType): self { $this->classType = $classType; return $this; }

    public function getClassSubject(): ?string { return $this->classSubject; }
    public function setClassSubject(?string $classSubject): self { $this->classSubject = $classSubject; return $this; }

    public function getClassCode(): ?string { return $this->classCode; }
    public function setClassCode(?string $classCode): self { $this->classCode = $classCode; return $this; }

    public function getClassDuration(): ?int { return $this->classDuration; }
    public function setClassDuration(?int $classDuration): self { $this->classDuration = $classDuration; return $this; }

    public function getOriginalStartDate(): ?string { return $this->originalStartDate; }
    public function setOriginalStartDate(?string $originalStartDate): self { $this->originalStartDate = $originalStartDate; return $this; }

    public function isSetaFunded(): ?bool { return $this->setaFunded; }
    public function setSetaFunded(?bool $setaFunded): self { $this->setaFunded = $setaFunded; return $this; }

    public function getSeta(): ?string { return $this->seta; }
    public function setSeta(?string $seta): self { $this->seta = $seta; return $this; }

    public function isExamClass(): ?bool { return $this->examClass; }
    public function setExamClass(?bool $examClass): self { $this->examClass = $examClass; return $this; }

    public function getExamType(): ?string { return $this->examType; }
    public function setExamType(?string $examType): self { $this->examType = $examType; return $this; }

    public function getQaVisitDates(): ?string { return $this->qaVisitDates; }
    public function setQaVisitDates(?string $qaVisitDates): self { $this->qaVisitDates = $qaVisitDates; return $this; }

    public function getClassAgent(): ?int { return $this->classAgent; }
    public function setClassAgent(?int $classAgent): self { $this->classAgent = $classAgent; return $this; }

    public function getInitialClassAgent(): ?int { return $this->initialClassAgent; }
    public function setInitialClassAgent(?int $initialClassAgent): self { $this->initialClassAgent = $initialClassAgent; return $this; }

    public function getInitialAgentStartDate(): ?string { return $this->initialAgentStartDate; }
    public function setInitialAgentStartDate(?string $initialAgentStartDate): self { $this->initialAgentStartDate = $initialAgentStartDate; return $this; }

    public function getProjectSupervisorId(): ?int { return $this->projectSupervisorId; }
    public function setProjectSupervisorId(?int $projectSupervisorId): self { $this->projectSupervisorId = $projectSupervisorId; return $this; }

    public function getDeliveryDate(): ?string { return $this->deliveryDate; }
    public function setDeliveryDate(?string $deliveryDate): self { $this->deliveryDate = $deliveryDate; return $this; }

    public function getLearnerIds(): array { return $this->learnerIds; }
    public function setLearnerIds(array $learnerIds): self { $this->learnerIds = $learnerIds; return $this; }

    public function getBackupAgentIds(): array { return $this->backupAgentIds; }
    public function setBackupAgentIds(array $backupAgentIds): self { $this->backupAgentIds = $backupAgentIds; return $this; }

    public function getScheduleData(): array { return $this->scheduleData; }
    public function setScheduleData(array $scheduleData): self { $this->scheduleData = $scheduleData; return $this; }

    public function getStopRestartDates(): array { return $this->stopRestartDates; }
    public function setStopRestartDates(array $stopRestartDates): self { $this->stopRestartDates = $stopRestartDates; return $this; }

    public function getClassNotesData(): array { return $this->classNotesData; }
    public function setClassNotesData(array $classNotesData): self { $this->classNotesData = $classNotesData; return $this; }

    public function getQaReports(): array { return $this->qaReports; }
    public function setQaReports(array $qaReports): self { $this->qaReports = $qaReports; return $this; }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}
