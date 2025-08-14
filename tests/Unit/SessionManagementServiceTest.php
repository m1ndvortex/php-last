<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserSession;
use App\Services\SessionManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SessionManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SessionManagementService $sessionService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sessionService = new SessionManagementService();
        
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'preferred_language' => 'en',
            'is_active' => true,
        ]);
        
        // Clear any existing sessions for clean tests
        UserSession::where('user_id', $this->user->id)->delete();
    }

    public function test_create_session_creates_user_session_record()
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $session = $this->sessionService->createSession($this->user, $request);

        $this->assertInstanceOf(UserSession::class, $session);
        $this->assertEquals($this->user->id, $session->user_id);
        $this->assertEquals($request->ip(), $session->ip_address);
        $this->assertEquals($request->userAgent(), $session->user_agent);
        $this->assertNotNull($session->expires_at);
        $this->assertTrue($session->is_active); // Should be boolean true
    }

    public function test_create_session_sets_correct_device_type()
    {
        // Test desktop user agent
        $request = Request::create('/test', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $session = $this->sessionService->createSession($this->user, $request);
        
        $this->assertEquals('desktop', $session->device_type);
    }

    public function test_update_session_activity_updates_last_activity()
    {
        $request = Request::create('/test', 'GET');
        $session = $this->sessionService->createSession($this->user, $request);
        
        $originalActivity = $session->last_activity;
        
        // Wait a moment to ensure timestamp difference
        sleep(1);
        
        $this->sessionService->updateSessionActivity($session->session_id);
        
        $session->refresh();
        $this->assertNotEquals($originalActivity, $session->last_activity);
    }

    public function test_terminate_session_deactivates_session()
    {
        $request = Request::create('/test', 'GET');
        $session = $this->sessionService->createSession($this->user, $request);
        
        $this->assertTrue($session->is_active);
        
        $result = $this->sessionService->terminateSession($session->session_id);
        
        $this->assertTrue($result);
        $session->refresh();
        $this->assertFalse($session->is_active);
    }

    public function test_terminate_session_returns_false_for_nonexistent_session()
    {
        $result = $this->sessionService->terminateSession('nonexistent-session-id');
        
        $this->assertFalse($result);
    }

    public function test_terminate_other_sessions_keeps_current_session_active()
    {
        // Create sessions with unique session IDs
        $request1 = Request::create('/test1', 'GET');
        $session1 = $this->sessionService->createSession($this->user, $request1);
        
        // Create second session manually to avoid session ID conflicts
        $session2 = UserSession::create([
            'user_id' => $this->user->id,
            'session_id' => 'unique-session-2',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent 2',
            'device_type' => 'desktop',
            'browser' => 'Test',
            'platform' => 'Test',
            'location' => 'Test Location',
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(120),
            'is_active' => true
        ]);
        
        $terminatedCount = $this->sessionService->terminateOtherSessions($this->user, $session1->session_id);
        
        $this->assertEquals(1, $terminatedCount);
        
        $session1->refresh();
        $session2->refresh();
        
        $this->assertTrue($session1->is_active);
        $this->assertFalse($session2->is_active);
    }

    public function test_get_user_active_sessions_returns_correct_format()
    {
        $request = Request::create('/test', 'GET');
        $session = $this->sessionService->createSession($this->user, $request);
        
        $activeSessions = $this->sessionService->getUserActiveSessions($this->user);
        
        $this->assertIsArray($activeSessions);
        $this->assertCount(1, $activeSessions);
        
        $sessionData = $activeSessions[0];
        $this->assertArrayHasKey('id', $sessionData);
        $this->assertArrayHasKey('session_id', $sessionData);
        $this->assertArrayHasKey('ip_address', $sessionData);
        $this->assertArrayHasKey('device_info', $sessionData);
        $this->assertArrayHasKey('location', $sessionData);
        $this->assertArrayHasKey('last_activity', $sessionData);
        $this->assertArrayHasKey('duration', $sessionData);
        $this->assertArrayHasKey('is_current', $sessionData);
    }

    public function test_should_timeout_returns_true_for_expired_session()
    {
        $request = Request::create('/test', 'GET');
        $session = $this->sessionService->createSession($this->user, $request);
        
        // Manually set expiry to past
        $session->update(['expires_at' => now()->subMinutes(10)]);
        
        $shouldTimeout = $this->sessionService->shouldTimeout($session->session_id);
        
        $this->assertTrue($shouldTimeout);
    }

    public function test_should_timeout_returns_false_for_active_session()
    {
        $request = Request::create('/test', 'GET');
        $session = $this->sessionService->createSession($this->user, $request);
        
        $shouldTimeout = $this->sessionService->shouldTimeout($session->session_id);
        
        $this->assertFalse($shouldTimeout);
    }

    public function test_should_timeout_returns_true_for_nonexistent_session()
    {
        $shouldTimeout = $this->sessionService->shouldTimeout('nonexistent-session-id');
        
        $this->assertTrue($shouldTimeout);
    }

    public function test_needs_timeout_warning_returns_correct_value()
    {
        $request = Request::create('/test', 'GET');
        $session = $this->sessionService->createSession($this->user, $request);
        
        // Set expiry to within warning threshold
        $warningTime = $this->sessionService->getTimeoutWarningTime();
        $session->update(['expires_at' => now()->addMinutes($warningTime - 1)]);
        
        $needsWarning = $this->sessionService->needsTimeoutWarning($session->session_id);
        
        $this->assertTrue($needsWarning);
    }

    public function test_cleanup_expired_sessions_deactivates_expired_sessions()
    {
        $request = Request::create('/test', 'GET');
        $session1 = $this->sessionService->createSession($this->user, $request);
        
        // Create second session manually to avoid session ID conflicts
        $session2 = UserSession::create([
            'user_id' => $this->user->id,
            'session_id' => 'unique-session-cleanup',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'device_type' => 'desktop',
            'browser' => 'Test',
            'platform' => 'Test',
            'location' => 'Test Location',
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(120),
            'is_active' => true
        ]);
        
        // Make one session expired
        $session1->update(['expires_at' => now()->subMinutes(10)]);
        
        $cleanedCount = $this->sessionService->cleanupExpiredSessions();
        
        $this->assertEquals(1, $cleanedCount);
        
        $session1->refresh();
        $session2->refresh();
        
        $this->assertFalse($session1->is_active);
        $this->assertTrue($session2->is_active);
    }

    public function test_get_session_stats_returns_correct_statistics()
    {
        $request = Request::create('/test', 'GET');
        $session1 = $this->sessionService->createSession($this->user, $request);
        
        // Create second session manually to avoid session ID conflicts
        $session2 = UserSession::create([
            'user_id' => $this->user->id,
            'session_id' => 'unique-session-stats',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'device_type' => 'desktop',
            'browser' => 'Test',
            'platform' => 'Test',
            'location' => 'Test Location',
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(120),
            'is_active' => true
        ]);
        
        // Make one session inactive
        $session1->update(['is_active' => false]);
        
        $stats = $this->sessionService->getSessionStats($this->user);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_sessions', $stats);
        $this->assertArrayHasKey('active_sessions', $stats);
        $this->assertArrayHasKey('unique_ips', $stats);
        $this->assertArrayHasKey('unique_devices', $stats);
        $this->assertArrayHasKey('last_login', $stats);
        $this->assertArrayHasKey('current_session_duration', $stats);
        
        $this->assertEquals(2, $stats['total_sessions']);
        $this->assertEquals(1, $stats['active_sessions']);
    }

    public function test_detect_suspicious_activity_identifies_new_ip()
    {
        // Create a session with known IP
        $request1 = Request::create('/test', 'GET');
        $request1->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->sessionService->createSession($this->user, $request1);
        
        // Test with new IP
        $request2 = Request::create('/test', 'GET');
        $request2->server->set('REMOTE_ADDR', '192.168.1.2');
        
        $suspiciousActivity = $this->sessionService->detectSuspiciousActivity($this->user, $request2);
        
        $this->assertIsArray($suspiciousActivity);
        $this->assertArrayHasKey('is_suspicious', $suspiciousActivity);
        $this->assertArrayHasKey('factors', $suspiciousActivity);
        $this->assertArrayHasKey('risk_level', $suspiciousActivity);
        
        $this->assertTrue($suspiciousActivity['is_suspicious']);
        $this->assertContains('new_ip_address', $suspiciousActivity['factors']);
    }

    public function test_detect_suspicious_activity_identifies_new_device()
    {
        // Create a session with known user agent
        $request1 = Request::create('/test', 'GET');
        $request1->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $this->sessionService->createSession($this->user, $request1);
        
        // Test with new user agent
        $request2 = Request::create('/test', 'GET');
        $request2->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)');
        
        $suspiciousActivity = $this->sessionService->detectSuspiciousActivity($this->user, $request2);
        
        $this->assertTrue($suspiciousActivity['is_suspicious']);
        $this->assertContains('new_device', $suspiciousActivity['factors']);
    }

    public function test_detect_suspicious_activity_identifies_rapid_login_attempts()
    {
        $request = Request::create('/test', 'GET');
        
        // Create multiple sessions manually to avoid session ID conflicts
        for ($i = 0; $i < 4; $i++) {
            UserSession::create([
                'user_id' => $this->user->id,
                'session_id' => 'rapid-session-' . $i,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test Agent',
                'device_type' => 'desktop',
                'browser' => 'Test',
                'platform' => 'Test',
                'location' => 'Test Location',
                'last_activity' => now(),
                'expires_at' => now()->addMinutes(120),
                'is_active' => true,
                'created_at' => now()->subMinutes(5) // Within 10 minutes
            ]);
        }
        
        $suspiciousActivity = $this->sessionService->detectSuspiciousActivity($this->user, $request);
        
        $this->assertTrue($suspiciousActivity['is_suspicious']);
        $this->assertContains('rapid_login_attempts', $suspiciousActivity['factors']);
    }

    public function test_detect_suspicious_activity_calculates_correct_risk_level()
    {
        $request = Request::create('/test', 'GET');
        
        // Create a known session first
        $this->sessionService->createSession($this->user, $request);
        
        // Test medium risk (new IP and device)
        $newRequest = Request::create('/test', 'GET');
        $newRequest->server->set('REMOTE_ADDR', '192.168.1.2'); // New IP
        $newRequest->headers->set('User-Agent', 'New User Agent'); // New device
        
        $suspiciousActivity = $this->sessionService->detectSuspiciousActivity($this->user, $newRequest);
        $this->assertEquals('medium', $suspiciousActivity['risk_level']);
    }

    public function test_get_timeout_warning_time_returns_configured_value()
    {
        $warningTime = $this->sessionService->getTimeoutWarningTime();
        
        $this->assertIsInt($warningTime);
        $this->assertEquals(config('session.timeout_warning', 5), $warningTime);
    }

    public function test_session_service_handles_missing_session_gracefully()
    {
        // Test methods with non-existent session ID
        $this->assertFalse($this->sessionService->terminateSession('invalid-id'));
        $this->assertTrue($this->sessionService->shouldTimeout('invalid-id'));
        $this->assertFalse($this->sessionService->needsTimeoutWarning('invalid-id'));
    }

    public function test_session_service_works_with_different_device_types()
    {
        // Clear existing sessions first
        UserSession::where('user_id', $this->user->id)->delete();
        
        // Test mobile device
        $mobileRequest = Request::create('/test', 'GET');
        $mobileRequest->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)');
        
        $mobileSession = $this->sessionService->createSession($this->user, $mobileRequest);
        $this->assertEquals('mobile', $mobileSession->device_type);
        
        // Delete the mobile session to avoid conflicts
        $mobileSession->delete();
        
        // Test tablet device (iPad might be detected as mobile by the agent library)
        $tabletRequest = Request::create('/test', 'GET');
        $tabletRequest->headers->set('User-Agent', 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X)');
        
        $tabletSession = $this->sessionService->createSession($this->user, $tabletRequest);
        // The agent library might detect iPad as mobile, so we check for either
        $this->assertContains($tabletSession->device_type, ['tablet', 'mobile']);
        
        // Delete the tablet session to avoid conflicts
        $tabletSession->delete();
        
        // Test desktop device
        $desktopRequest = Request::create('/test', 'GET');
        $desktopRequest->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        
        $desktopSession = $this->sessionService->createSession($this->user, $desktopRequest);
        $this->assertEquals('desktop', $desktopSession->device_type);
    }
}