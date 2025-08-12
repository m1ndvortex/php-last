<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ApiCacheTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_api_cache_statistics_endpoint()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/cache/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'api_cache_keys',
                    'service_cache_keys',
                    'total_cache_keys',
                    'cache_patterns'
                ]
            ]);
    }

    public function test_api_cache_clear_all_endpoint()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cache/clear-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'All API cache cleared successfully'
            ]);
    }

    public function test_api_cache_clear_data_type_endpoint()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cache/clear-data-type', [
                'data_type' => 'dashboard'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cache cleared for data type: dashboard'
            ]);
    }

    public function test_api_cache_clear_data_type_validates_input()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cache/clear-data-type', [
                'data_type' => 'invalid_type'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data_type']);
    }

    public function test_api_cache_warm_up_endpoint()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cache/warm-up');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cache warmed up successfully'
            ]);
    }

    public function test_dashboard_kpis_caching_headers()
    {
        // First request should be a cache miss
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/kpis');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');

        // Second request should be a cache hit
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/kpis');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'HIT');
    }
}