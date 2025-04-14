#!/usr/bin/env php
<?php

/**
 * Custom Background Job Runner
 *
 * This script accepts a class name, method, and parameters and executes
 * the specified method in the background.
 *
 * Usage: php run-job.php ClassName methodName "param1,param2"
 */

// Bootstrap Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BackgroundJobRunner;
use App\Services\BackgroundJobLogger;
use Illuminate\Support\Facades\Config;

// Check for required arguments
if ($argc < 3) {
    echo "Usage: php run-job.php ClassName methodName \"param1,param2\"\n";
    exit(1);
}

// Parse arguments
$class = $argv[1];
$method = $argv[2];
$params = isset($argv[3]) ? explode(',', $argv[3]) : [];

// Create logger
$logger = new BackgroundJobLogger();

try {
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
        throw new \Exception("Class $class with method $method is not allowed to run as a background job");
    }

    // Create and run the job
    $runner = new BackgroundJobRunner($logger);
    $result = $runner->run($class, $method, $params);

    exit(0);
} catch (\Throwable $e) {
    // Log error
    $logger->logError($class, $method, $e->getMessage(), $params);

    // Handle retry logic
    $maxRetries = Config::get('background-jobs.max_retries', 3);
    $retryDelay = Config::get('background-jobs.retry_delay', 5);
    $currentAttempt = isset($argv[4]) ? (int)$argv[4] : 1;

    if ($currentAttempt < $maxRetries) {
        // Sleep for the retry delay
        sleep($retryDelay);

        // Execute the job again with incremented attempt count
        $nextAttempt = $currentAttempt + 1;
        $command = "php " . __FILE__ . " \"$class\" \"$method\" \"" . implode(',', $params) . "\" $nextAttempt";

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B " . $command, "r"));
        } else {
            exec($command . " > /dev/null 2>&1 &");
        }

        echo "Job failed, retrying (attempt $nextAttempt of $maxRetries)...\n";
    } else {
        echo "Job failed after $maxRetries attempts\n";
    }

    exit(1);
}
