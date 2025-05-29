<?php
declare(strict_types=1);

namespace ClassCRUD\Utilities;

class Validator
{
    /**
     * Validate required fields
     */
    public function required(array $data, array $requiredFields): array
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim((string)$data[$field]))) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        return $errors;
    }

    /**
     * Validate email format
     */
    public function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate date format (Y-m-d)
     */
    public function date(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate integer
     */
    public function integer($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate positive integer
     */
    public function positiveInteger($value): bool
    {
        return $this->integer($value) && (int)$value > 0;
    }

    /**
     * Validate string length
     */
    public function length(string $value, int $min = 0, int $max = null): bool
    {
        $length = strlen($value);
        
        if ($length < $min) {
            return false;
        }
        
        if ($max !== null && $length > $max) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate alphanumeric with allowed characters
     */
    public function alphanumeric(string $value, string $allowedChars = ''): bool
    {
        $pattern = '/^[a-zA-Z0-9' . preg_quote($allowedChars, '/') . ']+$/';
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate URL
     */
    public function url(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate phone number (basic)
     */
    public function phone(string $phone): bool
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/\D/', '', $phone);
        
        // Check if it's between 10-15 digits
        return strlen($cleaned) >= 10 && strlen($cleaned) <= 15;
    }

    /**
     * Validate that a value is in an array of allowed values
     */
    public function inArray($value, array $allowedValues): bool
    {
        return in_array($value, $allowedValues, true);
    }

    /**
     * Validate JSON string
     */
    public function json(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate password strength
     */
    public function password(string $password, int $minLength = 8): array
    {
        $errors = [];
        
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/\d/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }

    /**
     * Sanitize string input
     */
    public function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize integer input
     */
    public function sanitizeInt($input): int
    {
        return (int)filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize email input
     */
    public function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Validate file upload
     */
    public function file(array $file, array $allowedTypes = [], int $maxSize = 5242880): array
    {
        $errors = [];
        
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
            return $errors;
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        if (!empty($allowedTypes)) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedTypes)) {
                $errors[] = 'File type not allowed';
            }
        }
        
        return $errors;
    }

    /**
     * Validate CSRF token (basic implementation)
     */
    public function csrfToken(string $token, string $sessionToken): bool
    {
        return hash_equals($sessionToken, $token);
    }

    /**
     * Validate multiple fields with rules
     */
    public function validateFields(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule => $ruleValue) {
                switch ($rule) {
                    case 'required':
                        if ($ruleValue && (is_null($value) || trim((string)$value) === '')) {
                            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                        }
                        break;
                        
                    case 'email':
                        if ($ruleValue && $value && !$this->email($value)) {
                            $errors[$field] = 'Invalid email format';
                        }
                        break;
                        
                    case 'min_length':
                        if ($value && !$this->length($value, $ruleValue)) {
                            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$ruleValue} characters";
                        }
                        break;
                        
                    case 'max_length':
                        if ($value && !$this->length($value, 0, $ruleValue)) {
                            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$ruleValue} characters";
                        }
                        break;
                        
                    case 'integer':
                        if ($ruleValue && $value && !$this->integer($value)) {
                            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid number';
                        }
                        break;
                        
                    case 'positive':
                        if ($ruleValue && $value && !$this->positiveInteger($value)) {
                            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a positive number';
                        }
                        break;
                        
                    case 'date':
                        if ($ruleValue && $value && !$this->date($value)) {
                            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid date (YYYY-MM-DD)';
                        }
                        break;
                        
                    case 'in':
                        if (is_array($ruleValue) && $value && !$this->inArray($value, $ruleValue)) {
                            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' contains an invalid value';
                        }
                        break;
                }
                
                // Break on first error for this field
                if (isset($errors[$field])) {
                    break;
                }
            }
        }
        
        return $errors;
    }
}
