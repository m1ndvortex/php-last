<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImageSecurityService
{
    /**
     * Perform comprehensive security validation on uploaded image.
     */
    public function validateImageSecurity(UploadedFile $file): array
    {
        $errors = [];
        
        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Invalid file extension. Only JPG, PNG, GIF, and WebP files are allowed.';
        }
        
        // Check MIME type
        $allowedMimes = [
            'image/jpeg',
            'image/png', 
            'image/gif',
            'image/webp'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'Invalid MIME type. File must be a valid image.';
        }
        
        // Check if file is actually an image
        $imageInfo = @getimagesize($file->getPathname());
        if (!$imageInfo) {
            $errors[] = 'File is not a valid image.';
            return $errors; // Return early if not an image
        }
        
        // Check image dimensions
        [$width, $height] = $imageInfo;
        
        if ($width < 50 || $height < 50) {
            $errors[] = 'Image dimensions must be at least 50x50 pixels.';
        }
        
        if ($width > 4000 || $height > 4000) {
            $errors[] = 'Image dimensions must not exceed 4000x4000 pixels.';
        }
        
        // Check file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            $errors[] = 'Image file size must be less than 5MB.';
        }
        
        // Check for embedded PHP code or suspicious content
        if ($this->containsSuspiciousContent($file)) {
            $errors[] = 'Image contains suspicious content and cannot be uploaded.';
        }
        
        // Validate image type matches extension
        $detectedType = $this->getImageTypeFromMime($file->getMimeType());
        $expectedType = $this->getImageTypeFromExtension($extension);
        
        if ($detectedType !== $expectedType) {
            $errors[] = 'File extension does not match the actual image type.';
        }
        
        return $errors;
    }
    
    /**
     * Check for suspicious content in the image file.
     */
    private function containsSuspiciousContent(UploadedFile $file): bool
    {
        // Read first 1KB of file to check for suspicious patterns
        $handle = fopen($file->getPathname(), 'rb');
        if (!$handle) {
            return true; // Assume suspicious if can't read
        }
        
        $content = fread($handle, 1024);
        fclose($handle);
        
        // Check for PHP tags
        $suspiciousPatterns = [
            '<?php',
            '<?=',
            '<script',
            'javascript:',
            'eval(',
            'base64_decode',
            'exec(',
            'system(',
            'shell_exec',
            'passthru',
            'file_get_contents',
            'file_put_contents',
            'fopen',
            'fwrite',
            'include',
            'require',
        ];
        
        $contentLower = strtolower($content);
        
        foreach ($suspiciousPatterns as $pattern) {
            if (strpos($contentLower, strtolower($pattern)) !== false) {
                Log::warning('Suspicious content detected in uploaded image', [
                    'pattern' => $pattern,
                    'filename' => $file->getClientOriginalName()
                ]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get image type from MIME type.
     */
    private function getImageTypeFromMime(string $mimeType): string
    {
        $mimeToType = [
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        
        return $mimeToType[$mimeType] ?? 'unknown';
    }
    
    /**
     * Get expected image type from file extension.
     */
    private function getImageTypeFromExtension(string $extension): string
    {
        $extensionToType = [
            'jpg' => 'jpeg',
            'jpeg' => 'jpeg',
            'png' => 'png',
            'gif' => 'gif',
            'webp' => 'webp'
        ];
        
        return $extensionToType[strtolower($extension)] ?? 'unknown';
    }
    
    /**
     * Sanitize filename to prevent directory traversal and other attacks.
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove directory traversal attempts
        $filename = str_replace(['../', '..\\', '../', '..\\'], '', $filename);
        
        // Remove null bytes
        $filename = str_replace("\0", '', $filename);
        
        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit filename length
        if (strlen($filename) > 100) {
            $pathInfo = pathinfo($filename);
            $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
            $basename = substr($pathInfo['filename'], 0, 100 - strlen($extension));
            $filename = $basename . $extension;
        }
        
        return $filename;
    }
    
    /**
     * Generate secure random filename.
     */
    public function generateSecureFilename(string $originalExtension): string
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($originalExtension);
        
        if (!in_array($extension, $allowedExtensions)) {
            $extension = 'jpg'; // Default to jpg if invalid extension
        }
        
        // Generate cryptographically secure random filename
        $randomBytes = random_bytes(16);
        $filename = bin2hex($randomBytes);
        
        return $filename . '.' . $extension;
    }
}