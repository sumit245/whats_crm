<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SettingController extends Controller
{
    /**
     * Show the admin server / environment settings page.
     */
    public function index()
    {
        $port = env('PORT_NODE');
        $host = request()->getHost();
        $waUrl = env('WA_URL_SERVER');

        $isConnected = $this->isNodeServerConnected($waUrl, $host, $port);

        $appSecure = request()->isSecure();
        $nodeSecure = is_string($waUrl) && str_starts_with($waUrl, 'https');

        $protocolMatch = $appSecure === $nodeSecure
            ? '<span class="text-success">' . __('Protocol matched') . '</span>'
            : '<span class="text-danger">' . __('Protocol mismatch (HTTP/HTTPS)') . '</span>';

        $allEnv = $this->readEnv();

        return view('theme::pages.admin.settings', compact(
            'port',
            'isConnected',
            'protocolMatch',
            'host',
            'allEnv'
        ));
    }

    /**
     * Save the Node server connection settings.
     */
    public function setServer(Request $request)
    {
        $request->validate([
            'typeServer' => 'required|string',
            'portnode' => 'required|numeric',
        ]);

        $type = $request->input('typeServer');
        $port = $request->input('portnode');

        setEnv('TYPE_SERVER', $type);
        setEnv('PORT_NODE', $port);

        if ($type === 'other') {
            setEnv('WA_URL_SERVER', $request->input('urlnode', ''));
        } elseif ($type === 'localhost') {
            setEnv('WA_URL_SERVER', 'http://localhost:' . $port);
        } else {
            // Hosting shared: derive the URL from the current host.
            setEnv('WA_URL_SERVER', request()->getSchemeAndHttpHost() . ':' . $port);
        }

        return backWithFlash('success', __('Server settings updated successfully'));
    }

    /**
     * Request an SSL certificate for the companion Node server.
     *
     * NOTE: The actual certificate issuance (ACME / Let's Encrypt) is performed
     * by the companion Node service; here we validate the input and forward the
     * request to it, reporting the outcome back to the admin.
     */
    public function generateSslCertificate(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'email' => 'required|email',
        ]);

        if ($request->input('domain') === 'localhost') {
            return backWithFlash('danger', __("You Can't Generate SSL For Localhost"));
        }

        try {
            $response = Http::withOptions(['verify' => false])
                ->asForm()
                ->post(env('WA_URL_SERVER') . '/generate-ssl', [
                    'domain' => $request->input('domain'),
                    'email' => $request->input('email'),
                ]);

            if ($response->successful()) {
                return backWithFlash('success', __('SSL certificate generated successfully'));
            }
        } catch (\Throwable $e) {
            // fall through to the error response below
        }

        return backWithFlash('danger', __('Failed to generate SSL certificate'));
    }

    /**
     * Bulk update arbitrary .env values.
     */
    public function setEnvAll(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            setEnv($key, is_array($value) ? '' : (string) $value);
        }

        return backWithFlash('success', __('Environment settings updated successfully'));
    }

    /**
     * Show the cronjob instructions page.
     */
    public function cronJob()
    {
        $cron_path = $this->detectCronBinary();

        return view('theme::pages.admin.cronjob', compact('cron_path'));
    }

    /**
     * Application installer entry point.
     *
     * NOTE: The original packaged installer view is not present in this build.
     * If the app is already installed we send the admin to the login page;
     * otherwise we accept the posted environment and mark the app installed.
     */
    public function install(Request $request)
    {
        if (env('APP_INSTALLED') === 'yes') {
            return redirect()->route('login');
        }

        if ($request->isMethod('post')) {
            foreach ($request->except(['_token', '_method']) as $key => $value) {
                if (is_string($key) && ! is_array($value)) {
                    setEnv($key, (string) $value);
                }
            }

            setEnv('APP_INSTALLED', 'yes');

            return redirect()->route('login')->with('alert', [
                'type' => 'success',
                'msg' => __('Application installed successfully'),
            ]);
        }

        if (view()->exists('theme::pages.install')) {
            return view('theme::pages.install');
        }

        return redirect()->route('login');
    }

    /**
     * Test a database connection with the supplied credentials (AJAX).
     */
    public function test_database_connection(Request $request)
    {
        $host = $request->input('DB_HOST', $request->input('host', '127.0.0.1'));
        $port = $request->input('DB_PORT', $request->input('port', '3306'));
        $database = $request->input('DB_DATABASE', $request->input('database'));
        $username = $request->input('DB_USERNAME', $request->input('username'));
        $password = $request->input('DB_PASSWORD', $request->input('password', ''));

        $connectionName = 'install_test';

        Config::set("database.connections.$connectionName", [
            'driver' => 'mysql',
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        try {
            DB::connection($connectionName)->getPdo();

            return response()->json([
                'success' => true,
                'message' => __('Database connection successful'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('Database connection failed') . ': ' . $e->getMessage(),
            ], 422);
        } finally {
            DB::purge($connectionName);
        }
    }

    /**
     * Activate the application license (AJAX).
     *
     * NOTE: This build performs an offline activation: the supplied license key
     * and buyer email are stored in the environment file.
     */
    public function activate_license(Request $request)
    {
        $licenseKey = $request->input('LICENSE_KEY', $request->input('license_key', $request->input('purchase_code')));
        $email = $request->input('BUYER_EMAIL', $request->input('email'));

        if (! $licenseKey) {
            return response()->json([
                'success' => false,
                'message' => __('License key is required'),
            ], 422);
        }

        setEnv('LICENSE_KEY', (string) $licenseKey);
        if ($email) {
            setEnv('BUYER_EMAIL', (string) $email);
        }

        return response()->json([
            'success' => true,
            'message' => __('License activated successfully'),
        ]);
    }

    /**
     * Read the .env file into an associative array.
     *
     * @return array<string, string>
     */
    protected function readEnv(): array
    {
        $env = [];
        $path = base_path('.env');

        if (! file_exists($path)) {
            return $env;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = $value;
        }

        return $env;
    }

    /**
     * Determine whether the Node server port is reachable.
     */
    protected function isNodeServerConnected(?string $waUrl, string $host, $port): bool
    {
        if (! $port) {
            return false;
        }

        $targetHost = $host;
        if (is_string($waUrl) && $waUrl !== '') {
            $parsed = parse_url($waUrl, PHP_URL_HOST);
            if ($parsed) {
                $targetHost = $parsed;
            }
        }

        $connection = @fsockopen($targetHost, (int) $port, $errno, $errstr, 2);
        if ($connection) {
            fclose($connection);

            return true;
        }

        return false;
    }

    /**
     * Best-effort detection of the binary used to invoke cron URLs.
     */
    protected function detectCronBinary(): string
    {
        if (function_exists('shell_exec')) {
            $path = @shell_exec('command -v curl');
            if (is_string($path) && trim($path) !== '') {
                return trim($path);
            }
        }

        return 'curl';
    }
}
