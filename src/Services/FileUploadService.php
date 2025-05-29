<?php
declare(strict_types=1);

namespace ClassCRUD\Services;

use Psr\Http\Message\UploadedFileInterface;

class FileUploadService
{
    private string $uploadPath;
    private int $maxFileSize;
    private array $allowedTypes;

    public function __construct(string $uploadPath, int $maxFileSize = 5242880) // 5MB default
    {
        $this->uploadPath = rtrim($uploadPath, '/');
        $this->maxFileSize = $maxFileSize;
        $this->allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'];
        
        // Ensure upload directory exists
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Upload a file
     */
    public function uploadFile(UploadedFileInterface $uploadedFile, string $subDirectory = ''): array
    {
        // Validate file
        $validation = $this->validateFile($uploadedFile);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['error']);
        }

        // Create subdirectory if specified
        $targetDir = $this->uploadPath;
        if (!empty($subDirectory)) {
            $targetDir .= '/' . trim($subDirectory, '/');
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        // Generate unique filename
        $originalName = $uploadedFile->getClientFilename();
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $storedName = $this->generateUniqueFilename($baseName, $extension);
        $targetPath = $targetDir . '/' . $storedName;

        // Move uploaded file
        $uploadedFile->moveTo($targetPath);

        return [
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $targetPath,
            'relative_path' => str_replace($this->uploadPath . '/', '', $targetPath),
            'file_size' => $uploadedFile->getSize(),
            'mime_type' => $uploadedFile->getClientMediaType(),
        ];
    }

    /**
     * Upload multiple files
     */
    public function uploadMultipleFiles(array $uploadedFiles, string $subDirectory = ''): array
    {
        $results = [];
        
        foreach ($uploadedFiles as $file) {
            if ($file instanceof UploadedFileInterface && $file->getError() === UPLOAD_ERR_OK) {
                try {
                    $results[] = $this->uploadFile($file, $subDirectory);
                } catch (\Exception $e) {
                    $results[] = [
                        'error' => $e->getMessage(),
                        'original_filename' => $file->getClientFilename(),
                    ];
                }
            }
        }
        
        return $results;
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFileInterface $uploadedFile): array
    {
        // Check for upload errors
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => $this->getUploadErrorMessage($uploadedFile->getError())
            ];
        }

        // Check file size
        if ($uploadedFile->getSize() > $this->maxFileSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of ' . $this->formatBytes($this->maxFileSize)
            ];
        }

        // Check file type
        $filename = $uploadedFile->getClientFilename();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $this->allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed. Allowed types: ' . implode(', ', $this->allowedTypes)
            ];
        }

        // Additional security checks
        if (empty($filename) || strpos($filename, '..') !== false) {
            return [
                'valid' => false,
                'error' => 'Invalid filename'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(string $baseName, string $extension): string
    {
        // Sanitize base name
        $baseName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $baseName);
        $baseName = substr($baseName, 0, 50); // Limit length
        
        // Add timestamp and random string for uniqueness
        $timestamp = date('Y-m-d_H-i-s');
        $random = substr(md5(uniqid()), 0, 8);
        
        return $baseName . '_' . $timestamp . '_' . $random . '.' . $extension;
    }

    /**
     * Delete a file
     */
    public function deleteFile(string $filePath): bool
    {
        $fullPath = $this->uploadPath . '/' . ltrim($filePath, '/');
        
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }

    /**
     * Get file info
     */
    public function getFileInfo(string $filePath): ?array
    {
        $fullPath = $this->uploadPath . '/' . ltrim($filePath, '/');
        
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            return null;
        }
        
        return [
            'filename' => basename($fullPath),
            'size' => filesize($fullPath),
            'modified' => filemtime($fullPath),
            'mime_type' => mime_content_type($fullPath),
            'path' => $fullPath,
            'relative_path' => $filePath,
        ];
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive in HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string)$bytes) - 1) / 3);
        
        return sprintf("%.1f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Set allowed file types
     */
    public function setAllowedTypes(array $types): void
    {
        $this->allowedTypes = array_map('strtolower', $types);
    }

    /**
     * Get allowed file types
     */
    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    /**
     * Set maximum file size
     */
    public function setMaxFileSize(int $size): void
    {
        $this->maxFileSize = $size;
    }

    /**
     * Get maximum file size
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * Get upload path
     */
    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    /**
     * Check if file exists
     */
    public function fileExists(string $filePath): bool
    {
        $fullPath = $this->uploadPath . '/' . ltrim($filePath, '/');
        return file_exists($fullPath) && is_file($fullPath);
    }

    /**
     * Get file URL for download
     */
    public function getFileUrl(string $filePath): string
    {
        return '/uploads/' . ltrim($filePath, '/');
    }
}
