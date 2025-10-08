<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

use function in_array;

class TranslationController extends Controller {
    /**
     * Get a specific translation
     */
    public function getTranslation(Request $request, $locale, $key) {
        // Validate locale
        $supportedLocales = ['en', 'fr', 'de', 'es', 'nl'];
        if (! in_array($locale, $supportedLocales, true)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return response()->json([
            'key'         => $key,
            'translation' => __($key),
            'locale'      => $locale,
        ]);
    }

    /**
     * Get translations for a specific locale
     */
    public function getTranslations(Request $request, $locale = 'en') {
        // Validate locale
        $supportedLocales = ['en', 'fr', 'de', 'es', 'nl'];
        if (! in_array($locale, $supportedLocales, true)) {
            $locale = 'en';
        }

        // Set the locale
        App::setLocale($locale);

        // Get all translation keys you want to expose to frontend
        $translations = [
            // Buttons
            'btn.save'   => __('btn.save'),
            'btn.cancel' => __('btn.cancel'),
            'btn.edit'   => __('btn.edit'),
            'btn.delete' => __('btn.delete'),
            'btn.create' => __('btn.create'),
            'btn.update' => __('btn.update'),

            // Labels
            'lbl.name'        => __('lbl.name'),
            'lbl.email'       => __('lbl.email'),
            'lbl.password'    => __('lbl.password'),
            'lbl.confirm_pwd' => __('lbl.confirm_pwd'),
            'lbl.role'        => __('lbl.role'),
            'lbl.org'         => __('lbl.org'),
            'lbl.status'      => __('lbl.status'),
            'lbl.active'      => __('lbl.active'),
            'lbl.inactive'    => __('lbl.inactive'),

            // Messages
            'msg.welcome' => __('msg.welcome'),
            'msg.goodbye' => __('msg.goodbye'),

            // Success messages
            'ok.user_created' => __('ok.user_created'),
            'ok.user_updated' => __('ok.user_updated'),
            'ok.user_deleted' => __('ok.user_deleted'),

            // Common errors (you might want to expose some)
            'err.unauthorized' => __('err.unauthorized'),
            'err.not_found'    => __('err.not_found'),
            'err.validation'   => __('err.validation'),
        ];

        return response()->json([
            'locale'       => $locale,
            'translations' => $translations,
        ]);
    }
}
