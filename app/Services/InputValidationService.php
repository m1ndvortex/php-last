<?php

namespace App\Services;

class InputValidationService
{
    /**
     * Sanitize input data
     */
    public static function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }

        if (!is_string($input)) {
            return $input;
        }

        // Remove HTML tags first
        $input = strip_tags($input);
        
        // Decode any existing HTML entities first
        $input = html_entity_decode($input, ENT_QUOTES, 'UTF-8');
        
        // Remove script content completely
        $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $input);
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        // Additional cleanup for common XSS patterns
        $input = preg_replace('/javascript:/i', '', $input);
        $input = preg_replace('/on\w+\s*=/i', '', $input);
        
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Trim whitespace
        $input = trim($input);

        return $input;
    }

    /**
     * Validate email format
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check for SQL injection patterns
     */
    public static function containsSQLInjection(string $input): bool
    {
        $patterns = [
            '/(\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bCREATE\b|\bALTER\b)/i',
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bOR\b.*=.*\bOR\b)/i',
            '/(\bAND\b.*=.*\bAND\b)/i',
            '/(\'|\")(\s*)(;|\||&)/i',
            '/(\-\-|\#|\/\*)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for XSS patterns
     */
    public static function containsXSS(string $input): bool
    {
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/<link/i',
            '/<meta/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate password strength
     */
    public static function isStrongPassword(string $password): bool
    {
        // At least 8 characters, one uppercase, one lowercase, one number
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
    }

    /**
     * Clean filename for uploads
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove special characters except dots and dashes
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Prevent multiple dots
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Ensure it doesn't start with a dot
        $filename = ltrim($filename, '.');
        
        return $filename;
    }

    /**
     * Validate login credentials with comprehensive checks
     */
    public function validateLoginCredentials(array $data): array
    {
        $errors = [];
        $sanitizedData = [];

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = ['Email is required.'];
        } else {
            $email = self::sanitize($data['email']);
            if (!self::isValidEmail($email)) {
                $errors['email'] = ['Please enter a valid email address.'];
            } elseif (self::containsXSS($email) || self::containsSQLInjection($email)) {
                $errors['email'] = ['Email contains invalid characters.'];
            } else {
                $sanitizedData['email'] = strtolower($email);
            }
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = ['Password is required.'];
        } else {
            $password = $data['password']; // Don't sanitize passwords
            if (strlen($password) < 6) {
                $errors['password'] = ['Password must be at least 6 characters long.'];
            } elseif (strlen($password) > 255) {
                $errors['password'] = ['Password is too long.'];
            } else {
                $sanitizedData['password'] = $password;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $sanitizedData
        ];
    }

    /**
     * Validate profile update data
     */
    public function validateProfileUpdate(array $data): array
    {
        $errors = [];
        $sanitizedData = [];

        // Name validation
        if (isset($data['name'])) {
            if (empty($data['name'])) {
                $errors['name'] = ['Name is required when provided.'];
            } else {
                $name = self::sanitize($data['name']);
                if (strlen($name) > 255) {
                    $errors['name'] = ['Name must not exceed 255 characters.'];
                } elseif (self::containsXSS($name) || self::containsSQLInjection($name)) {
                    $errors['name'] = ['Name contains invalid characters.'];
                } else {
                    $sanitizedData['name'] = $name;
                }
            }
        }

        // Language validation
        if (isset($data['preferred_language'])) {
            $language = self::sanitize($data['preferred_language']);
            $allowedLanguages = ['en', 'fa'];
            
            if (!in_array($language, $allowedLanguages)) {
                $errors['preferred_language'] = ['Please select a valid language (English or Persian).'];
            } else {
                $sanitizedData['preferred_language'] = $language;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $sanitizedData
        ];
    }

    /**
     * Validate password change data
     */
    public function validatePasswordChange(array $data): array
    {
        $errors = [];
        $sanitizedData = [];

        // Current password validation
        if (empty($data['current_password'])) {
            $errors['current_password'] = ['Current password is required.'];
        } else {
            $sanitizedData['current_password'] = $data['current_password'];
        }

        // New password validation
        if (empty($data['new_password'])) {
            $errors['new_password'] = ['New password is required.'];
        } else {
            $newPassword = $data['new_password'];
            
            if (strlen($newPassword) < 8) {
                $errors['new_password'] = ['New password must be at least 8 characters long.'];
            } elseif (strlen($newPassword) > 255) {
                $errors['new_password'] = ['New password is too long.'];
            } elseif (!self::isStrongPassword($newPassword)) {
                $errors['new_password'] = ['New password must contain at least one uppercase letter, one lowercase letter, and one number.'];
            } else {
                $sanitizedData['new_password'] = $newPassword;
            }
        }

        // Password confirmation validation
        if (empty($data['new_password_confirmation'])) {
            $errors['new_password_confirmation'] = ['Password confirmation is required.'];
        } elseif (isset($data['new_password']) && $data['new_password'] !== $data['new_password_confirmation']) {
            $errors['new_password_confirmation'] = ['Password confirmation does not match the new password.'];
        }

        // Check if new password is different from current
        if (isset($data['current_password']) && isset($data['new_password']) && 
            $data['current_password'] === $data['new_password']) {
            $errors['new_password'] = ['New password must be different from the current password.'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $sanitizedData
        ];
    }
}