<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;

class BackgroundJobLogger
{
    /**
     * Log file paths
     */
    protected $logPath;
    protected $errorLogPath;

    /**
     * Create a new logger instance.
     */
    public function __construct()
    {
        $this->logPath = Config::get('background-jobs.log_path', storage_path('logs/background_jobs.log'));
        $this->errorLogPath = Config::get('background-jobs.error_log_path', storage_path('logs/background_jobs_errors.log'));

        // Ensure log directories exist
        $this->ensureLogDirectoryExists($this->logPath);
        $this->ensureLogDirectoryExists($this->errorLogPath);
    }

    /**
     * Log job start.
     *
     * @param string $class
     * @param string $method
     * @param array $params
     * @return void
     */
    public function logStart(string $class, string $method, array $params = []): void
    {
        $this->logToFile($this->logPath, "RUNNING", $class, $method, $params);
    }

    /**
     * Log job success.
     *
     * @param string $class
     * @param string $method
     * @param array $params
     * @return void
     */
    public function logSuccess(string $class, string $method, array $params = []): void
    {
        $this->logToFile($this->logPath, "COMPLETED", $class, $method, $params);
    }

    /**
     * Log job failure.
     *
     * @param string $class
     * @param string $method
     * @param string $error
     * @param array $params
     * @return void
     */
    public function logFailure(string $class, string $method, string $error, array $params = []): void
    {
        $this->logToFile($this->logPath, "FAILED", $class, $method, $params);
    }

    /**
     * Log job error.
     *
     * @param string $class
     * @param string $method
     * @param string $error
     * @param array $params
     * @return void
     */
    public function logError(string $class, string $method, string $error, array $params = []): void
    {
        $timestamp = Carbon::now()->toDateTimeString();
        $paramsStr = json_encode($params);

        $message = "[{$timestamp}] [{$class}::{$method}] Error: {$error} | Params: {$paramsStr}" . PHP_EOL;
        File::append($this->errorLogPath, $message);
    }

    /**
     * Write a log entry to the specified file.
     *
     * @param string $filePath
     * @param string $status
     * @param string $class
     * @param string $method
     * @param array $params
     * @return void
     */
    protected function logToFile(string $filePath, string $status, string $class, string $method, array $params = []): void
    {
        $timestamp = Carbon::now()->toDateTimeString();
        $paramsStr = json_encode($params);

        $message = "[{$timestamp}] [{$status}] [{$class}::{$method}] Params: {$paramsStr}" . PHP_EOL;
        File::append($filePath, $message);
    }

    /**
     * Ensure the log directory exists.
     *
     * @param string $filePath
     * @return void
     */
    protected function ensureLogDirectoryExists(string $filePath): void
    {
        $directory = dirname($filePath);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}
