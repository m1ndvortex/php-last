<?php

namespace Tests\Unit;

use App\Http\Middleware\AuthenticateApi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;
use Mockery;

class AuthenticateApiMiddlewareTest extends TestCase
{
    protected AuthenticateApi $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuthenticateApi();
    }

    public function test_middleware_allows_authenticated_active_user()
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('is_active')->andReturn(true);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);

        $next = function ($request) {
            return response()->json(['success' => true]);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['success' => true], $response->getData(true));
    }

    public function test_middleware_rejects_unauthenticated_user()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn(null);

        $next = function ($request) {
            return response()->json(['success' => true]);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('UNAUTHENTICATED', $data['error']['code']);
        $this->assertEquals('Authentication required', $data['error']['message']);
    }

    public function test_middleware_rejects_inactive_user()
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('is_active')->andReturn(false);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);

        $next = function ($request) {
            return response()->json(['success' => true]);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('ACCOUNT_INACTIVE', $data['error']['code']);
        $this->assertEquals('Account is inactive', $data['error']['message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}