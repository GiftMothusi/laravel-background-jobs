<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class BackgroundJobRunner
{
    /**
     * @var BackgroundJobLogger
     */
    protected $logger;

    /**
     * Create a new BackgroundJobRunner instance.
     *
     * @param BackgroundJobLogger $logger
     */
    public function __construct(BackgroundJobLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Run a background job.
     *
     * @param string $class
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function run(string $class, string $method, array $params = [])
    {
        // Sanitize inputs
        $class = $this->sanitizeClassName($class);
        $method = $this->sanitizeMethodName($method);

        // Validate class exists
        if (!class_exists($class)) {
            throw new Exception("Class {$class} does not exist");
        }

        // Validate method exists
        if (!method_exists($class, $method)) {
            throw new Exception("Method {$method} does not exist in class {$class}");
        }

        // Log job start
        $this->logger->logStart($class, $method, $params);

        try {
            // Instantiate class and execute method
            $instance = new $class();
            $result = call_user_func_array([$instance, $method], $params);

            // Log job completion
            $this->logger->logSuccess($class, $method, $params);

            return $result;
        } catch (\Throwable $e) {
            // Log job failure
            $this->logger->logFailure($class, $method, $e->getMessage(), $params);

            // Re-throw exception for retry mechanism in run-job.php
            throw $e;
        }
    }

    /**
     * Sanitize class name to prevent injection.
     *
     * @param string $class
     * @return string
     */
    protected function sanitizeClassName(string $class): string
    {
        // Remove any potentially harmful characters
        return preg_replace('/[^a-zA-Z0-9_\\\\]/', '', $class);
    }

    /**
     * Sanitize method name to prevent injection.
     *
     * @param string $method
     * @return string
     */
    protected function sanitizeMethodName(string $method): string
    {
        // Remove any potentially harmful characters
        return preg_replace('/[^a-zA-Z0-9_]/', '', $method);
    }
}
