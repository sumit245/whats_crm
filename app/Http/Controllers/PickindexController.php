<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PickindexController extends Controller
{
    /**
     * The editable CSS custom properties of the welcome page.
     *
     * @var array<int, string>
     */
    protected array $cssColorKeys = [
        '--bs-primary',
        '--bs-footer-bg',
        '--bs-footer-alt-bg',
    ];

    /**
     * Show the welcome-page editor (texts + colors + enable toggle).
     */
    public function editSettings()
    {
        $supportedLocales = config('laravellocalization.supportedLocales', []);

        $translations = [];
        $languages = [];

        foreach (glob($this->indexLangPath() . '/*.json') as $file) {
            $lang = basename($file, '.json');
            $translations[$lang] = json_decode(file_get_contents($file), true) ?: [];
            $languages[$lang] = [
                'name' => $supportedLocales[$lang]['name'] ?? strtoupper($lang),
            ];
        }

        $cssVariables = $this->readCssVariables();

        return view('theme::pages.admin.index', compact('translations', 'languages', 'cssVariables'));
    }

    /**
     * Save the translated welcome-page texts.
     */
    public function updateSettings(Request $request)
    {
        $translations = (array) $request->input('translations', []);

        foreach ($translations as $lang => $texts) {
            $file = $this->indexLangPath() . '/' . basename($lang) . '.json';

            if (! file_exists($file)) {
                continue;
            }

            file_put_contents(
                $file,
                json_encode((array) $texts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        return backWithFlash('success', __('Welcome page updated successfully'));
    }

    /**
     * Save the welcome-page color variables into the index theme stylesheets.
     */
    public function updateColor(Request $request)
    {
        $colors = (array) $request->input('colors', []);

        foreach ($this->cssFiles() as $cssFile) {
            if (! file_exists($cssFile)) {
                continue;
            }

            $css = file_get_contents($cssFile);

            foreach ($colors as $key => $value) {
                if (! in_array($key, $this->cssColorKeys, true)) {
                    continue;
                }

                $css = preg_replace(
                    '/(' . preg_quote($key, '/') . '\s*:\s*)([^;]+)(;)/',
                    '${1}' . $value . '${3}',
                    $css
                );
            }

            file_put_contents($cssFile, $css);
        }

        return backWithFlash('success', __('Colors updated successfully'));
    }

    /**
     * Enable or disable the public welcome page.
     */
    public function enableIndex(Request $request)
    {
        $value = $request->input('enableindex') === 'yes' ? 'yes' : 'no';

        setEnv('ENABLE_INDEX', $value);

        return backWithFlash('success', __('Welcome page setting updated'));
    }

    /**
     * Path to the index theme translation files.
     */
    protected function indexLangPath(): string
    {
        return resource_path('lang/index');
    }

    /**
     * The active index theme name.
     */
    protected function indexTheme(): string
    {
        return env('THEME_INDEX') ?: 'lezir';
    }

    /**
     * The stylesheets that hold the welcome-page color variables.
     *
     * @return array<int, string>
     */
    protected function cssFiles(): array
    {
        $base = public_path('index/' . $this->indexTheme() . '/css');

        return [
            $base . '/style.ltr.min.css',
            $base . '/style.rtl.min.css',
        ];
    }

    /**
     * Read the current color variable values from the index stylesheet.
     *
     * @return array<string, string>
     */
    protected function readCssVariables(): array
    {
        $variables = [];
        $files = $this->cssFiles();
        $cssFile = $files[0] ?? null;

        if ($cssFile && file_exists($cssFile)) {
            $css = file_get_contents($cssFile);

            foreach ($this->cssColorKeys as $key) {
                if (preg_match('/' . preg_quote($key, '/') . '\s*:\s*([^;]+);/', $css, $matches)) {
                    $variables[$key] = trim($matches[1]);
                }
            }
        }

        return $variables;
    }
}
