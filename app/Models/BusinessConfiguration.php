<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class BusinessConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'description',
        'is_encrypted'
    ];

    protected $casts = [
        'value' => 'json',
        'is_encrypted' => 'boolean'
    ];

    /**
     * Get the decrypted value if encrypted
     */
    public function getDecryptedValueAttribute()
    {
        if ($this->is_encrypted && is_string($this->value)) {
            try {
                return Crypt::decrypt($this->value);
            } catch (\Exception $e) {
                return $this->value;
            }
        }
        
        return $this->value;
    }

    /**
     * Set encrypted value if needed
     */
    public function setValueAttribute($value)
    {
        if ($this->is_encrypted && !is_null($value)) {
            $this->attributes['value'] = json_encode(Crypt::encrypt($value));
        } else {
            $this->attributes['value'] = json_encode($value);
        }
    }

    /**
     * Scope for configuration category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get configuration by key
     */
    public static function getValue($key, $default = null)
    {
        $config = static::where('key', $key)->first();
        
        if (!$config) {
            return $default;
        }
        
        return $config->is_encrypted ? $config->decrypted_value : $config->value;
    }

    /**
     * Set configuration value
     */
    public static function setValue($key, $value, $type = 'string', $category = 'general', $isEncrypted = false)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'category' => $category,
                'is_encrypted' => $isEncrypted
            ]
        );
    }

    /**
     * Get default pricing percentages for gold pricing calculations
     */
    public static function getDefaultPricingPercentages(): array
    {
        return [
            'labor_percentage' => static::getValue('default_labor_percentage', 10.0),
            'profit_percentage' => static::getValue('default_profit_percentage', 15.0),
            'tax_percentage' => static::getValue('default_tax_percentage', 9.0)
        ];
    }

    /**
     * Get all business configuration as key-value pairs
     */
    public static function getAllConfigurations(): array
    {
        $configurations = static::all();
        $result = [];
        
        foreach ($configurations as $config) {
            $result[$config->key] = $config->is_encrypted ? $config->decrypted_value : $config->value;
        }
        
        return $result;
    }
}