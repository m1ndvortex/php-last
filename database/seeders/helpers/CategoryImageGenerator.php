<?php

namespace Database\Seeders\Helpers;

/**
 * Helper class to generate sample category images for testing.
 * In production, these would be replaced with actual jewelry photos.
 */
class CategoryImageGenerator
{
    /**
     * Generate a sample SVG image for a category.
     */
    public static function generateSVG(string $categoryName, string $color = '#3B82F6'): string
    {
        $width = 400;
        $height = 300;
        
        // Create different patterns based on category type
        $pattern = self::getPatternForCategory($categoryName);
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:' . $color . ';stop-opacity:0.1" />
      <stop offset="100%" style="stop-color:' . $color . ';stop-opacity:0.3" />
    </linearGradient>
    <linearGradient id="accent" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" style="stop-color:' . $color . ';stop-opacity:0.8" />
      <stop offset="100%" style="stop-color:' . $color . ';stop-opacity:0.6" />
    </linearGradient>
  </defs>
  
  <!-- Background -->
  <rect width="' . $width . '" height="' . $height . '" fill="url(#bg)"/>
  
  <!-- Border -->
  <rect x="10" y="10" width="' . ($width - 20) . '" height="' . ($height - 20) . '" 
        fill="none" stroke="' . $color . '" stroke-width="2" rx="8"/>
  
  ' . $pattern . '
  
  <!-- Category Name -->
  <rect x="50" y="' . ($height - 80) . '" width="' . ($width - 100) . '" height="50" 
        fill="url(#accent)" rx="4"/>
  <text x="' . ($width / 2) . '" y="' . ($height - 55) . '" text-anchor="middle" 
        font-family="Arial, sans-serif" font-size="18" font-weight="bold" fill="white">
    ' . htmlspecialchars($categoryName) . '
  </text>
  <text x="' . ($width / 2) . '" y="' . ($height - 35) . '" text-anchor="middle" 
        font-family="Arial, sans-serif" font-size="12" fill="white" opacity="0.9">
    Sample Category Image
  </text>
</svg>';

        return $svg;
    }

    /**
     * Get a decorative pattern based on category type.
     */
    private static function getPatternForCategory(string $categoryName): string
    {
        $lowerName = strtolower($categoryName);
        
        if (strpos($lowerName, 'ring') !== false) {
            return self::getRingPattern();
        } elseif (strpos($lowerName, 'necklace') !== false) {
            return self::getNecklacePattern();
        } elseif (strpos($lowerName, 'bracelet') !== false) {
            return self::getBraceletPattern();
        } elseif (strpos($lowerName, 'earring') !== false) {
            return self::getEarringPattern();
        } elseif (strpos($lowerName, 'watch') !== false) {
            return self::getWatchPattern();
        } elseif (strpos($lowerName, 'diamond') !== false) {
            return self::getDiamondPattern();
        } else {
            return self::getGenericJewelryPattern();
        }
    }

    private static function getRingPattern(): string
    {
        return '
  <!-- Ring shape -->
  <circle cx="200" cy="120" r="40" fill="none" stroke="#D97706" stroke-width="8"/>
  <circle cx="200" cy="120" r="25" fill="none" stroke="#D97706" stroke-width="4"/>
  <!-- Gem -->
  <circle cx="200" cy="80" r="8" fill="#EF4444"/>
        ';
    }

    private static function getNecklacePattern(): string
    {
        return '
  <!-- Necklace chain -->
  <path d="M 80 100 Q 200 60 320 100" fill="none" stroke="#D97706" stroke-width="4"/>
  <path d="M 100 120 Q 200 80 300 120" fill="none" stroke="#D97706" stroke-width="3"/>
  <!-- Pendant -->
  <circle cx="200" cy="140" r="12" fill="#EF4444"/>
        ';
    }

    private static function getBraceletPattern(): string
    {
        return '
  <!-- Bracelet links -->
  <ellipse cx="150" cy="120" rx="15" ry="8" fill="none" stroke="#D97706" stroke-width="3"/>
  <ellipse cx="180" cy="120" rx="15" ry="8" fill="none" stroke="#D97706" stroke-width="3"/>
  <ellipse cx="210" cy="120" rx="15" ry="8" fill="none" stroke="#D97706" stroke-width="3"/>
  <ellipse cx="240" cy="120" rx="15" ry="8" fill="none" stroke="#D97706" stroke-width="3"/>
        ';
    }

    private static function getEarringPattern(): string
    {
        return '
  <!-- Earring pair -->
  <circle cx="160" cy="100" r="8" fill="#D97706"/>
  <rect x="156" y="108" width="8" height="20" fill="#D97706"/>
  <circle cx="156" cy="135" r="6" fill="#EF4444"/>
  
  <circle cx="240" cy="100" r="8" fill="#D97706"/>
  <rect x="236" y="108" width="8" height="20" fill="#D97706"/>
  <circle cx="236" cy="135" r="6" fill="#EF4444"/>
        ';
    }

    private static function getWatchPattern(): string
    {
        return '
  <!-- Watch case -->
  <circle cx="200" cy="120" r="35" fill="none" stroke="#374151" stroke-width="6"/>
  <circle cx="200" cy="120" r="25" fill="#F3F4F6"/>
  <!-- Watch hands -->
  <line x1="200" y1="120" x2="200" y2="105" stroke="#374151" stroke-width="3"/>
  <line x1="200" y1="120" x2="215" y2="120" stroke="#374151" stroke-width="2"/>
  <!-- Watch band -->
  <rect x="190" y="80" width="20" height="15" fill="#374151"/>
  <rect x="190" y="145" width="20" height="15" fill="#374151"/>
        ';
    }

    private static function getDiamondPattern(): string
    {
        return '
  <!-- Diamond shape -->
  <polygon points="200,80 220,120 200,160 180,120" fill="#E5E7EB" stroke="#9CA3AF" stroke-width="2"/>
  <polygon points="200,80 210,100 200,120 190,100" fill="#F3F4F6"/>
  <!-- Sparkles -->
  <circle cx="170" cy="90" r="2" fill="#FDE047"/>
  <circle cx="230" cy="110" r="2" fill="#FDE047"/>
  <circle cx="180" cy="150" r="2" fill="#FDE047"/>
        ';
    }

    private static function getGenericJewelryPattern(): string
    {
        return '
  <!-- Generic jewelry elements -->
  <circle cx="180" cy="100" r="8" fill="#D97706"/>
  <circle cx="220" cy="100" r="8" fill="#EF4444"/>
  <circle cx="200" cy="130" r="10" fill="#10B981"/>
  <path d="M 160 140 Q 200 120 240 140" fill="none" stroke="#D97706" stroke-width="3"/>
        ';
    }

    /**
     * Get color scheme for different jewelry types.
     */
    public static function getColorForCategory(string $categoryName): string
    {
        $lowerName = strtolower($categoryName);
        
        if (strpos($lowerName, 'gold') !== false) {
            return '#D97706'; // Gold color
        } elseif (strpos($lowerName, 'silver') !== false) {
            return '#6B7280'; // Silver color
        } elseif (strpos($lowerName, 'diamond') !== false) {
            return '#E5E7EB'; // Diamond color
        } elseif (strpos($lowerName, 'ruby') !== false) {
            return '#DC2626'; // Ruby red
        } elseif (strpos($lowerName, 'emerald') !== false) {
            return '#059669'; // Emerald green
        } elseif (strpos($lowerName, 'watch') !== false) {
            return '#374151'; // Watch color
        } else {
            return '#3B82F6'; // Default blue
        }
    }
}