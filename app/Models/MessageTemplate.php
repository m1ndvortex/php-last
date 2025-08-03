<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'category',
        'language',
        'subject',
        'content',
        'variables',
        'is_active'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for template type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for template category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for template language
     */
    public function scopeLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Render template with variables
     */
    public function render(array $variables = []): array
    {
        $content = $this->content;
        $subject = $this->subject;

        // Replace variables in content
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
            
            if ($subject) {
                $subject = str_replace($placeholder, $value, $subject);
            }
        }

        return [
            'subject' => $subject,
            'content' => $content,
            'type' => $this->type,
            'language' => $this->language
        ];
    }

    /**
     * Get available variables for template
     */
    public function getAvailableVariables(): array
    {
        return $this->variables ?? [];
    }

    /**
     * Extract variables from template content
     */
    public static function extractVariables(string $content, ?string $subject = null): array
    {
        $text = $content . ' ' . ($subject ?? '');
        preg_match_all('/\{\{([^}]+)\}\}/', $text, $matches);
        
        return array_unique($matches[1] ?? []);
    }

    /**
     * Validate template variables
     */
    public function validateVariables(array $variables): array
    {
        $availableVars = $this->getAvailableVariables();
        $missingVars = [];

        foreach ($availableVars as $var) {
            if (!isset($variables[$var])) {
                $missingVars[] = $var;
            }
        }

        return $missingVars;
    }
}