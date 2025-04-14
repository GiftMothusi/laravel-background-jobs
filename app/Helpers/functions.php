<?php

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

if (!function_exists('runBackgroundJob')) {
    /**
     * Run a background job.
     *
     * @param string $class The fully qualified class name
     * @param string $method The method to call
     * @param array $params Optional parameters to pass to the method
     * @param int $priority Optional priority (lower number = higher priority)
     * @param int $delay Optional delay in seconds
     * @return bool True if the job was successfully dispatched
     */
    function runBackgroundJob(string $class, string $method, array $params = [], int $priority = 0, int $delay = 0): bool
    {
        // Prepare parameters as comma-separated string
        $paramsString = implode(',', $params);

        // Base command
        $scriptPath = base_path('run-job.php');
        $command = "php {$scriptPath} \"{$class}\" \"{$method}\" \"{$paramsString}\"";

        // Handle delay if enabled and requested
        if (config('background-jobs.enable_delays', false) && $delay > 0) {
            // Use the sleep command for Unix systems or timeout for Windows
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = "timeout /t {$delay} > nul && {$command}";
            } else {
                $command = "sleep {$delay} && {$command}";
            }
        }

        try {
            // For better control, use Symfony Process component if available
            if (class_exists(Process::class)) {
                $process = Process::fromShellCommandline($command);
                $process->disableOutput();
                $process->start();

                return true;
            }

            // Fallback to basic system commands
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - use start command to run in background
                pclose(popen("start /B " . $command, "r"));
            } else {
                // Unix-based - use exec with & to run in background
                exec($command . " > /dev/null 2>&1 &");
            }

            return true;
        } catch (\Throwable $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error("Failed to run background job: " . $e->getMessage());
            return false;
        }
    }
}
