<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class RunBackgroundJobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:run
                            {class : The fully qualified class name}
                            {method : The method to call}
                            {--params= : Optional comma-separated parameters}
                            {--priority=0 : Job priority (lower is higher priority)}
                            {--delay=0 : Delay in seconds before running the job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a background job';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $class = $this->argument('class');
        $method = $this->argument('method');
        $paramsString = $this->option('params');
        $priority = (int) $this->option('priority');
        $delay = (int) $this->option('delay');

        // Parse parameters
        $params = [];
        if (!empty($paramsString)) {
            $params = explode(',', $paramsString);
        }

        // Check if the job is allowed
        $allowedJobs = Config::get('background-jobs.allowed_jobs', []);

        $isAllowed = false;
        foreach ($allowedJobs as $allowedClass => $allowedMethods) {
            if ($class === $allowedClass && (in_array($method, $allowedMethods) || in_array('*', $allowedMethods))) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            $this->error("Class $class with method $method is not allowed to run as a background job");
            return 1;
        }

        // Run the background job
        $result = runBackgroundJob($class, $method, $params, $priority, $delay);

        if ($result) {
            $delayMsg = $delay > 0 ? " (with {$delay}s delay)" : '';
            $this->info("Background job dispatched{$delayMsg}: {$class}::{$method}");
            return 0;
        } else {
            $this->error("Failed to dispatch background job: {$class}::{$method}");
            return 1;
        }
    }
}
