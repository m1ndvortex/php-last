<?php

namespace Tests\Unit;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SetLocaleMiddlewareTest extends TestCase
{
    protected SetLocale $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SetLocale();
    }

    public function test_sets_locale_from_request_parameter()
    {
        $request = Request::create('/test', 'GET', ['locale' => 'fa']);
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('fa', App::getLocale());
        $this->assertEquals('fa', Session::get('locale'));
    }

    public function test_ignores_invalid_locale_parameter()
    {
        $originalLocale = App::getLocale();
        $request = Request::create('/test', 'GET', ['locale' => 'invalid']);
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals($originalLocale, App::getLocale());
    }

    public function test_uses_session_locale_when_available()
    {
        Session::put('locale', 'fa');
        $request = Request::create('/test', 'GET');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('fa', App::getLocale());
    }

    public function test_parses_accept_language_header()
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'fa,en;q=0.9');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('fa', App::getLocale());
    }

    public function test_falls_back_to_default_locale()
    {
        $request = Request::create('/test', 'GET');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(config('app.locale'), App::getLocale());
    }

    public function test_prioritizes_request_parameter_over_session()
    {
        Session::put('locale', 'en');
        $request = Request::create('/test', 'GET', ['locale' => 'fa']);
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('fa', App::getLocale());
    }

    public function test_prioritizes_session_over_accept_language()
    {
        Session::put('locale', 'fa');
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('fa', App::getLocale());
    }

    public function test_handles_complex_accept_language_header()
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9,fa;q=0.8,fr;q=0.7');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Should pick 'en' as it has higher priority than 'fa'
        $this->assertEquals('en', App::getLocale());
    }

    public function test_handles_persian_language_codes()
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'fa-IR,fa;q=0.9');
        
        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('fa', App::getLocale());
    }

    public function test_middleware_returns_response()
    {
        $request = Request::create('/test', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }
}