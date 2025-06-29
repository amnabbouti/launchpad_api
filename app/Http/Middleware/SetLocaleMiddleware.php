<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        
        if ($acceptLanguage) {
            // Parse the Accept-Language header to get the preferred language
            $preferredLanguage = $this->parseAcceptLanguage($acceptLanguage);
            
            // Set of supported locales
            $supportedLocales = ['en', 'fr', 'de', 'es', 'nl']; // Add more as needed
            
            // Check if the preferred language is supported
            if (in_array($preferredLanguage, $supportedLocales)) {
                App::setLocale($preferredLanguage);
            }
        }

        return $next($request);
    }

    /**
     * Parse Accept-Language header and return the most preferred supported language
     */
    private function parseAcceptLanguage(string $acceptLanguage): string
    {
        // Remove spaces and split by comma
        $languages = array_map('trim', explode(',', $acceptLanguage));
        
        $parsed = [];
        foreach ($languages as $language) {
            // Handle quality values (q=0.9)
            if (strpos($language, ';') !== false) {
                [$lang, $quality] = explode(';', $language, 2);
                $quality = floatval(str_replace('q=', '', $quality));
            } else {
                $lang = $language;
                $quality = 1.0;
            }
            
            // Extract just the language code (e.g., 'fr' from 'fr-FR')
            $langCode = strtolower(explode('-', trim($lang))[0]);
            
            $parsed[$langCode] = $quality;
        }
        
        // Sort by quality (highest first)
        arsort($parsed);
        
        // Return the first (highest quality) language
        return array_key_first($parsed) ?: 'en';
    }
} 