<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    /**
     * Show the configurable payment gateways.
     */
    public function index()
    {
        $gateways = [];

        foreach ((array) config('payments', []) as $name => $config) {
            $gateways[] = [
                'name' => $name,
                'config' => $config,
            ];
        }

        return view('theme::pages.admin.payments.index', compact('gateways'));
    }

    /**
     * Persist updated gateway credentials/status to config/payments.php.
     */
    public function update(Request $request)
    {
        $submitted = (array) $request->input('gateway', []);
        $current = (array) config('payments', []);

        foreach ($submitted as $name => $options) {
            if (! isset($current[$name]) || ! is_array($options)) {
                continue;
            }

            foreach ($options as $key => $value) {
                // Only update keys that already exist for this gateway so the
                // configuration shape stays consistent.
                if (array_key_exists($key, $current[$name])) {
                    $current[$name][$key] = $value;
                }
            }
        }

        $this->writeConfig($current);

        return backWithFlash('success', __('Payment gateways updated successfully.'));
    }

    /**
     * Write the payments configuration array back to its config file.
     *
     * @param  array<string, mixed>  $config
     */
    protected function writeConfig(array $config): void
    {
        $contents = "<?php return " . var_export($config, true) . ";\n";

        file_put_contents(config_path('payments.php'), $contents);

        // Refresh the runtime config so subsequent reads in this request match.
        config(['payments' => $config]);
    }
}
