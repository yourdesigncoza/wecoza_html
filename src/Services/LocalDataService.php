<?php
declare(strict_types=1);

namespace ClassCRUD\Services;

use Exception;

class LocalDataService
{
    private string $dataPath;

    public function __construct(string $dataPath)
    {
        $this->dataPath = rtrim($dataPath, '/');
        
        // Ensure data directory exists
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }
    }

    /**
     * Load data from a JSON file
     */
    private function loadJsonFile(string $filename): array
    {
        $filepath = $this->dataPath . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return [];
        }
        
        $content = file_get_contents($filepath);
        if ($content === false) {
            throw new Exception("Could not read file: {$filepath}");
        }
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in file: {$filepath}");
        }
        
        return $data ?? [];
    }

    /**
     * Save data to a JSON file
     */
    private function saveJsonFile(string $filename, array $data): bool
    {
        $filepath = $this->dataPath . '/' . $filename;
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return file_put_contents($filepath, $content) !== false;
    }

    /**
     * Get all clients
     */
    public function getClients(): array
    {
        return $this->loadJsonFile('clients.json');
    }

    /**
     * Get all sites grouped by client
     */
    public function getSites(): array
    {
        return $this->loadJsonFile('sites.json');
    }

    /**
     * Get all agents
     */
    public function getAgents(): array
    {
        return $this->loadJsonFile('agents.json');
    }

    /**
     * Get all supervisors
     */
    public function getSupervisors(): array
    {
        return $this->loadJsonFile('supervisors.json');
    }

    /**
     * Get all learners
     */
    public function getLearners(): array
    {
        return $this->loadJsonFile('learners.json');
    }

    /**
     * Get all SETA organizations
     */
    public function getSetas(): array
    {
        return $this->loadJsonFile('setas.json');
    }

    /**
     * Get all class types
     */
    public function getClassTypes(): array
    {
        return $this->loadJsonFile('class_types.json');
    }

    /**
     * Get class subjects for a specific type
     */
    public function getClassSubjects(string $classType = null): array
    {
        $subjects = $this->loadJsonFile('class_subjects.json');
        
        if ($classType === null) {
            return $subjects;
        }
        
        return $subjects[$classType] ?? [];
    }

    /**
     * Get yes/no options
     */
    public function getYesNoOptions(): array
    {
        return [
            ['value' => 'Yes', 'label' => 'Yes'],
            ['value' => 'No', 'label' => 'No']
        ];
    }

    /**
     * Get class notes options
     */
    public function getClassNotesOptions(): array
    {
        return $this->loadJsonFile('class_notes_options.json');
    }

    /**
     * Get exam types
     */
    public function getExamTypes(): array
    {
        return $this->loadJsonFile('exam_types.json');
    }

    /**
     * Get South African public holidays
     */
    public function getPublicHolidays(int $year = null): array
    {
        $year = $year ?? date('Y');
        return $this->loadJsonFile("public_holidays_{$year}.json");
    }

    /**
     * Initialize default data files if they don't exist
     */
    public function initializeDefaultData(): void
    {
        $defaultData = [
            'clients.json' => $this->getDefaultClients(),
            'sites.json' => $this->getDefaultSites(),
            'agents.json' => $this->getDefaultAgents(),
            'supervisors.json' => $this->getDefaultSupervisors(),
            'learners.json' => $this->getDefaultLearners(),
            'setas.json' => $this->getDefaultSetas(),
            'class_types.json' => $this->getDefaultClassTypes(),
            'class_subjects.json' => $this->getDefaultClassSubjects(),
            'class_notes_options.json' => $this->getDefaultClassNotesOptions(),
            'exam_types.json' => $this->getDefaultExamTypes(),
            'public_holidays_2025.json' => $this->getDefaultPublicHolidays2025(),
            'public_holidays_2026.json' => $this->getDefaultPublicHolidays2026(),
        ];

        foreach ($defaultData as $filename => $data) {
            $filepath = $this->dataPath . '/' . $filename;
            if (!file_exists($filepath)) {
                $this->saveJsonFile($filename, $data);
            }
        }
    }

    /**
     * Default data methods
     */
    private function getDefaultClients(): array
    {
        return [
            ['id' => 1, 'name' => 'Sasol Limited'],
            ['id' => 2, 'name' => 'Standard Bank Group'],
            ['id' => 3, 'name' => 'Shoprite Holdings'],
            ['id' => 4, 'name' => 'MTN Group'],
            ['id' => 5, 'name' => 'Naspers'],
            ['id' => 6, 'name' => 'Vodacom Group'],
            ['id' => 7, 'name' => 'Woolworths Holdings'],
            ['id' => 8, 'name' => 'FirstRand'],
            ['id' => 9, 'name' => 'Bidvest Group'],
            ['id' => 10, 'name' => 'Sanlam'],
            ['id' => 11, 'name' => 'Aspen Pharmacare'],
            ['id' => 12, 'name' => 'Nedbank Group'],
            ['id' => 13, 'name' => 'Tiger Brands'],
            ['id' => 14, 'name' => 'Barloworld'],
            ['id' => 15, 'name' => 'Multichoice Group']
        ];
    }

    private function getDefaultSites(): array
    {
        return [
            11 => [
                ['id' => '11_1', 'name' => 'Aspen Pharmacare - Head Office', 'address' => '100 Pharma Rd, Durban, 4001'],
                ['id' => '11_2', 'name' => 'Aspen Pharmacare - Production Unit', 'address' => '101 Pharma Rd, Durban, 4001'],
                ['id' => '11_3', 'name' => 'Aspen Pharmacare - Research Centre', 'address' => '102 Pharma Rd, Durban, 4001']
            ],
            14 => [
                ['id' => '14_1', 'name' => 'Barloworld - Northern Branch', 'address' => '10 Northern Ave, Johannesburg, 2001'],
                ['id' => '14_2', 'name' => 'Barloworld - Southern Branch', 'address' => '20 Southern St, Johannesburg, 2002'],
                ['id' => '14_3', 'name' => 'Barloworld - Central Branch', 'address' => '30 Central Blvd, Johannesburg, 2003']
            ]
        ];
    }

    private function getDefaultAgents(): array
    {
        return [
            ['id' => 1, 'name' => 'Michael M. van der Berg'],
            ['id' => 2, 'name' => 'Thandi T. Nkosi'],
            ['id' => 3, 'name' => 'Rajesh R. Patel'],
            ['id' => 4, 'name' => 'Lerato L. Moloi'],
            ['id' => 5, 'name' => 'Johannes J. Pretorius'],
            ['id' => 6, 'name' => 'Nomvula N. Dlamini'],
            ['id' => 7, 'name' => 'David D. O\'Connor'],
            ['id' => 8, 'name' => 'Zanele Z. Mthembu'],
            ['id' => 9, 'name' => 'Pieter P. van Zyl'],
            ['id' => 10, 'name' => 'Fatima F. Ismail']
        ];
    }

    private function getDefaultSupervisors(): array
    {
        return [
            ['id' => 1, 'name' => 'Ethan J. Williams'],
            ['id' => 2, 'name' => 'Aisha K. Mohamed'],
            ['id' => 3, 'name' => 'Carlos M. Rodriguez'],
            ['id' => 4, 'name' => 'Emily R. Thompson'],
            ['id' => 5, 'name' => 'Samuel B. Johnson']
        ];
    }

    private function getDefaultLearners(): array
    {
        return [
            ['id' => 1, 'name' => 'John J.M. Smith'],
            ['id' => 2, 'name' => 'Nosipho N. Dlamini'],
            ['id' => 3, 'name' => 'Ahmed A. Patel'],
            ['id' => 4, 'name' => 'Lerato L. Moloi'],
            ['id' => 5, 'name' => 'Pieter P. van der Merwe']
        ];
    }

    private function getDefaultSetas(): array
    {
        return [
            ['id' => 'HWSETA', 'name' => 'Health and Welfare SETA'],
            ['id' => 'MERSETA', 'name' => 'Manufacturing, Engineering and Related Services SETA'],
            ['id' => 'BANKSETA', 'name' => 'Banking SETA'],
            ['id' => 'INSETA', 'name' => 'Insurance SETA'],
            ['id' => 'FASSET', 'name' => 'Finance and Accounting Services SETA']
        ];
    }

    private function getDefaultClassTypes(): array
    {
        return [
            ['id' => 'employed', 'name' => 'Employed'],
            ['id' => 'community', 'name' => 'Community'],
            ['id' => 'safety', 'name' => 'Safety Training'],
            ['id' => 'skills', 'name' => 'Skills Development']
        ];
    }

    private function getDefaultClassSubjects(): array
    {
        return [
            'employed' => [
                'Basic Computer Skills',
                'Customer Service',
                'Leadership Development',
                'Project Management'
            ],
            'community' => [
                'Adult Basic Education',
                'Life Skills',
                'Entrepreneurship',
                'Financial Literacy'
            ],
            'safety' => [
                'First Aid Level 1',
                'First Aid Level 2',
                'Fire Safety',
                'Occupational Health and Safety'
            ],
            'skills' => [
                'Welding',
                'Electrical Installation',
                'Plumbing',
                'Carpentry'
            ]
        ];
    }

    private function getDefaultClassNotesOptions(): array
    {
        return [
            'Venue Confirmed',
            'Materials Ordered',
            'Learners Contacted',
            'Assessment Scheduled',
            'Certificates Pending'
        ];
    }

    private function getDefaultExamTypes(): array
    {
        return [
            'Written',
            'Practical',
            'Oral',
            'Portfolio of Evidence',
            'Competency Assessment'
        ];
    }

    private function getDefaultPublicHolidays2025(): array
    {
        return [
            ['date' => '2025-01-01', 'name' => 'New Year\'s Day'],
            ['date' => '2025-03-21', 'name' => 'Human Rights Day'],
            ['date' => '2025-04-18', 'name' => 'Good Friday'],
            ['date' => '2025-04-21', 'name' => 'Family Day'],
            ['date' => '2025-04-27', 'name' => 'Freedom Day'],
            ['date' => '2025-04-28', 'name' => 'Public holiday (Freedom Day observed)'],
            ['date' => '2025-05-01', 'name' => 'Workers\' Day'],
            ['date' => '2025-06-16', 'name' => 'Youth Day'],
            ['date' => '2025-08-09', 'name' => 'National Women\'s Day'],
            ['date' => '2025-09-24', 'name' => 'Heritage Day'],
            ['date' => '2025-12-16', 'name' => 'Day of Reconciliation'],
            ['date' => '2025-12-25', 'name' => 'Christmas Day'],
            ['date' => '2025-12-26', 'name' => 'Day of Goodwill']
        ];
    }

    private function getDefaultPublicHolidays2026(): array
    {
        return [
            ['date' => '2026-01-01', 'name' => 'New Year\'s Day'],
            ['date' => '2026-03-21', 'name' => 'Human Rights Day'],
            ['date' => '2026-04-03', 'name' => 'Good Friday'],
            ['date' => '2026-04-06', 'name' => 'Family Day'],
            ['date' => '2026-04-27', 'name' => 'Freedom Day'],
            ['date' => '2026-05-01', 'name' => 'Workers\' Day'],
            ['date' => '2026-06-16', 'name' => 'Youth Day'],
            ['date' => '2026-08-09', 'name' => 'National Women\'s Day'],
            ['date' => '2026-08-10', 'name' => 'Public holiday (National Women\'s Day observed)'],
            ['date' => '2026-09-24', 'name' => 'Heritage Day'],
            ['date' => '2026-12-16', 'name' => 'Day of Reconciliation'],
            ['date' => '2026-12-25', 'name' => 'Christmas Day'],
            ['date' => '2026-12-26', 'name' => 'Day of Goodwill']
        ];
    }
}
