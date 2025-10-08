<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

use function in_array;

final class SetLocaleMiddleware {
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response {
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $preferredLanguage = $this->parseAcceptLanguage($acceptLanguage);
            $supportedLocales  = ['en', 'fr', 'de', 'es', 'nl', 'it', 'ar'];
            if (in_array($preferredLanguage, $supportedLocales, true)) {
                App::setLocale($preferredLanguage);
            }
        }

        return $next($request);
    }

    /**
     * Parse Accept-Language
     */
    private function parseAcceptLanguage(string $acceptLanguage): string {
        $languages = array_map('trim', explode(',', $acceptLanguage));

        $parsed = [];
        foreach ($languages as $language) {
            if (str_contains($language, ';')  ) {
                [$lang, $quality] = explode(';', $language, 2);
                $quality          = (float) (str_replace('q=', '', $quality));
            } else {
                $lang    = $language;
                $quality = 1.0;
            }
            $langCode          = mb_strtolower(explode('-', mb_trim($lang))[0]);
            $parsed[$langCode] = $quality;
        }
        arsort($parsed);

        return array_key_first($parsed) ?: 'en';
    }
}
