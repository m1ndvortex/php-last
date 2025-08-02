<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from various sources in order of priority
        $locale = $this->getLocaleFromRequest($request);
        
        // Validate and set locale
        if ($this->isValidLocale($locale)) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        }

        return $next($request);
    }

    /**
     * Get locale from request sources
     *
     * @param Request $request
     * @return string
     */
    private function getLocaleFromRequest(Request $request): string
    {
        // 1. Check for explicit locale parameter in request
        if ($request->has('locale') && $this->isValidLocale($request->get('locale'))) {
            return $request->get('locale');
        }

        // 2. Check session for stored locale
        if (Session::has('locale') && $this->isValidLocale(Session::get('locale'))) {
            return Session::get('locale');
        }

        // 3. Check Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $preferredLocale = $this->parseAcceptLanguage($acceptLanguage);
            if ($this->isValidLocale($preferredLocale)) {
                return $preferredLocale;
            }
        }

        // 4. Check for user preference (if authenticated)
        if ($request->user() && isset($request->user()->preferred_language)) {
            $userLocale = $request->user()->preferred_language;
            if ($this->isValidLocale($userLocale)) {
                return $userLocale;
            }
        }

        // 5. Fall back to application default
        return config('app.locale', 'en');
    }

    /**
     * Parse Accept-Language header to get preferred locale
     *
     * @param string $acceptLanguage
     * @return string
     */
    private function parseAcceptLanguage(string $acceptLanguage): string
    {
        $languages = [];
        
        // Parse the Accept-Language header
        $parts = explode(',', $acceptLanguage);
        
        foreach ($parts as $part) {
            $part = trim($part);
            
            if (strpos($part, ';') !== false) {
                [$lang, $quality] = explode(';', $part, 2);
                $quality = (float) str_replace('q=', '', $quality);
            } else {
                $lang = $part;
                $quality = 1.0;
            }
            
            $lang = trim($lang);
            
            // Convert language codes to our supported locales
            if (strpos($lang, 'fa') === 0 || strpos($lang, 'per') === 0) {
                $languages['fa'] = $quality;
            } elseif (strpos($lang, 'en') === 0) {
                $languages['en'] = $quality;
            }
        }
        
        // Sort by quality and return the highest
        arsort($languages);
        
        return array_key_first($languages) ?: 'en';
    }

    /**
     * Check if locale is valid
     *
     * @param string|null $locale
     * @return bool
     */
    private function isValidLocale(?string $locale): bool
    {
        if (!$locale) {
            return false;
        }

        $supportedLocales = config('app.supported_locales', ['en', 'fa']);
        
        return in_array($locale, $supportedLocales);
    }
}