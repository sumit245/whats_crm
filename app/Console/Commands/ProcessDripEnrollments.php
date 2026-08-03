<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDripStepJob;
use App\Models\DripEnrollment;
use Illuminate\Console\Command;

class ProcessDripEnrollments extends Command
{
    protected $signature   = 'drip:process';
    protected $description = 'Dispatch pending drip sequence steps';

    public function handle(): void
    {
        $count = 0;

        DripEnrollment::where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->chunkById(100, function ($enrollments) use (&$count) {
                foreach ($enrollments as $enrollment) {
                    ProcessDripStepJob::dispatch($enrollment->id);
                    $count++;
                }
            });

        if ($count > 0) {
            $this->info("Dispatched {$count} drip step job(s).");
        }
    }
}
