<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Console;

use App\Jobs\CheckDeviceHealthJob;
use App\Models\Device;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ScheduleCron::class,
        Commands\StartBlast::class,
        Commands\CheckSlaTimers::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('schedule:cron')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('subscription:check')->daily()->withoutOverlapping();
        $schedule->command('start:blast')->everyMinute()->withoutOverlapping();
        $schedule->command('templates:sync-statuses')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('sessions:expire')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('chat:check-sla')->everyMinute()->withoutOverlapping();

        // Poll Meta API for device quality rating once per day per connected device
        $schedule->call(function () {
            Device::where('status', 'Connected')
                ->whereNotNull('phone_number_id')
                ->whereNotNull('access_token')
                ->pluck('id')
                ->each(fn ($id) => CheckDeviceHealthJob::dispatch($id)->onQueue('broadcasts'));
        })->dailyAt('08:00')->name('check-device-health')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
