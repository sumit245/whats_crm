<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class LanguageController extends Controller
{
    /**
     * Per-page size when editing translation keys.
     */
    protected int $perPage = 50;

    /**
     * Show the list of available languages with translation progress.
     */
    public function index()
    {
        $supportedLocales = config('laravellocalization.supportedLocales', []);
        $baseLang = $this->baseLang();

        $existingLanguages = $this->existingLanguages();
        $languages = $existingLanguages;

        $baseTranslations = $this->loadLang($baseLang);
        $total = max(count($baseTranslations), 1);

        $progressData = [];
        foreach ($existingLanguages as $lang) {
            $translations = $this->loadLang($lang);

            $translated = 0;
            foreach (array_keys($baseTranslations) as $key) {
                if (isset($translations[$key]) && trim((string) $translations[$key]) !== '') {
                    $translated++;
                }
            }

            $progressData[$lang] = [
                'translated' => $translated,
                'remaining' => max(count($baseTranslations) - $translated, 0),
                'percentage' => (int) round(($translated / $total) * 100),
            ];
        }

        $filteredLanguages = [];
        foreach ($supportedLocales as $code => $locale) {
            $filteredLanguages[$code] = $locale['name'] ?? strtoupper($code);
        }

        return view('theme::pages.admin.languages.index', compact(
            'languages',
            'supportedLocales',
            'progressData',
            'baseLang',
            'filteredLanguages',
            'existingLanguages'
        ));
    }

    /**
     * Show the translation editor for a single language.
     */
    public function edit($lang)
    {
        $supportedLocales = config('laravellocalization.supportedLocales', []);
        $getName = $supportedLocales[$lang]['name'] ?? strtoupper($lang);

        $baseTranslations = $this->loadLang($this->baseLang());
        $translationsForLang = $this->loadLang($lang);

        $items = [];
        foreach ($baseTranslations as $key => $baseValue) {
            $value = $translationsForLang[$key] ?? '';
            $items[] = [
                'key' => $key,
                'value' => $value,
                'is_translated' => trim((string) $value) !== '',
            ];
        }

        $page = Paginator::resolveCurrentPage('page');
        $slice = array_slice($items, ($page - 1) * $this->perPage, $this->perPage);

        $paginatedTranslations = new LengthAwarePaginator(
            $slice,
            count($items),
            $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return view('theme::pages.admin.languages.edit', compact('paginatedTranslations', 'lang', 'getName'));
    }

    /**
     * Persist edited translations for a language (AJAX).
     */
    public function update(Request $request, $lang)
    {
        $file = $this->langFile($lang);

        if (! file_exists($file)) {
            return response()->json(['message' => __('Language file not found')], 404);
        }

        $existing = $this->loadLang($lang);
        $submitted = (array) $request->input('translations', []);

        $merged = array_merge($existing, $submitted);

        $this->writeLang($lang, $merged);

        return response()->json(['message' => __('Translations updated successfully')]);
    }

    /**
     * Create a new language file seeded from the base language (AJAX).
     */
    public function add(Request $request)
    {
        $language = $request->input('language');

        if (! $language || ! preg_match('/^[A-Za-z\-]+$/', $language)) {
            return response()->json(['success' => false, 'message' => __('Invalid language code')]);
        }

        if (file_exists($this->langFile($language))) {
            return response()->json(['success' => false, 'message' => __('Language already exists')]);
        }

        // Seed the new file from the base language so all keys are present.
        $this->writeLang($language, $this->loadLang($this->baseLang()));

        return response()->json(['success' => true, 'message' => __('Language added successfully')]);
    }

    /**
     * Delete a language file.
     */
    public function destroy($lang)
    {
        if (strtolower($lang) === $this->baseLang()) {
            return backWithFlash('danger', __('You can not delete the base language'));
        }

        $file = $this->langFile($lang);
        if (file_exists($file)) {
            @unlink($file);
        }

        return backWithFlash('success', __('Language deleted successfully'));
    }

    /**
     * The base / source language code.
     */
    protected function baseLang(): string
    {
        return config('app.fallback_locale', 'en');
    }

    /**
     * Absolute path to a language json file.
     */
    protected function langFile($lang): string
    {
        return resource_path('lang/' . basename($lang) . '.json');
    }

    /**
     * Load a language file as an associative array.
     *
     * @return array<string, mixed>
     */
    protected function loadLang($lang): array
    {
        $file = $this->langFile($lang);

        if (! file_exists($file)) {
            return [];
        }

        return json_decode(file_get_contents($file), true) ?: [];
    }

    /**
     * Write an associative array to a language json file.
     *
     * @param  array<string, mixed>  $translations
     */
    protected function writeLang($lang, array $translations): void
    {
        file_put_contents(
            $this->langFile($lang),
            json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * The language codes that currently have a translation file.
     *
     * @return array<int, string>
     */
    protected function existingLanguages(): array
    {
        $codes = [];

        foreach (glob(resource_path('lang/*.json')) as $file) {
            $codes[] = basename($file, '.json');
        }

        return $codes;
    }
}
