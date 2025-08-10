<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BusinessConfiguration;

return new class extends Migration
{
    public function up(): void
    {
        // Insert default pricing percentage configurations
        $defaultConfigurations = [
            [
                'key' => 'default_labor_percentage',
                'value' => json_encode(10.0),
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Default labor cost percentage for gold pricing calculations',
                'is_encrypted' => false
            ],
            [
                'key' => 'default_profit_percentage',
                'value' => json_encode(15.0),
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Default profit percentage for gold pricing calculations',
                'is_encrypted' => false
            ],
            [
                'key' => 'default_tax_percentage',
                'value' => json_encode(9.0),
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Default tax percentage for gold pricing calculations',
                'is_encrypted' => false
            ]
        ];

        foreach ($defaultConfigurations as $config) {
            BusinessConfiguration::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }

    public function down(): void
    {
        // Remove the default pricing percentage configurations
        BusinessConfiguration::whereIn('key', [
            'default_labor_percentage',
            'default_profit_percentage',
            'default_tax_percentage'
        ])->delete();
    }
};